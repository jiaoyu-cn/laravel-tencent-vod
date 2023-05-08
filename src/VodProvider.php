<?php

namespace Githen\LaravelTencentVod;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * 自动注册服务
 */
class VodProvider extends ServiceProvider
{
    /**
     * 服务注册
     * @return void
     */
    public function register()
    {
        // 发布文件
        $this->updateFile();
    }

    /**
     * 服务启动
     * @return void
     */
    public function boot()
    {
        // 注册签名方法
        $this->app->singleton('jiaoyu.tencent.vod', function (){
            return $this;
        });

        // 注册api获取上传签名
        Route::middleware('web')->get( 'jiaoyu/tencent/vod/sign/{param}', '\Githen\LaravelTencentVod\Controllers\VodController@getSignature')
            ->name('jiaoyu.tencent.vod.sign');
        // 视频存放时长
        Route::middleware('web')->get( 'jiaoyu/tencent/vod/modify/{param}', '\Githen\LaravelTencentVod\Controllers\VodController@modify')
            ->name('jiaoyu.tencent.vod.modify');
    }

    /**
     * 发布文件
     * @return void
     */
    private function updateFile()
    {
        // 发布配置文件
        $this->publishes([__DIR__.'/config/vod.php' => config_path('vod.php')]);

        // 发布JS
        $this->publishes([__DIR__.'/js/vod-js-sdk-v6.js' => public_path('app-assets/js/scripts/qcloud/vod-js-sdk-v6.js')]);
        $this->publishes([__DIR__.'/js/vod.upload.js' => public_path('app-assets/js/scripts/qcloud/vod.upload.js')]);
    }


    /**
     * ============================
     * 以下为逻辑代码
     */
    public function getSignature($label)
    {
        // 获取文件配置类型
        if (!$config = config('vod.'.$label, [])){
            return $this->message(1, "获取配置文件失败：".$label);
        }

        $params = [
            "secretId" => $config['secret_id'] ?? '',
            "currentTimeStamp" => time(),
            "expireTime" => time() + 3600,
            "random" => rand(),
            "classId" => $config['class_id'] ?? 0,
            'vodSubAppId' => $config['sub_appid'] ?? '',
        ];
        $params = http_build_query($params);
        $sign = base64_encode(hash_hmac('SHA1', $params, $config['secret_key']?? '', true).$params);

        return $this->message(0, '成功', ['sign' => $sign]);
    }

    /**
     * 修改视频基本信息
     * @param $fileId
     * @param $time
     * @return array
     */
    public function ModifyMediaInfo($label, $params = [])
    {
        $whiteParam = ['FileId', 'Name', 'Description', 'ClassId', 'ExpireTime','CoverData'];
        foreach ($params as $key => $val){
            if (!in_array($key, $whiteParam)){
                unset($params[$key]);
                continue;
            }

            // 有效期处理
            if ($key == 'ExpireTime' && is_numeric($params['ExpireTime'])){
                $params['ExpireTime'] = date('c', time() + $params['ExpireTime']);
            }
            // 封面图处理 可考虑兼容http或文件目录

        }
        return $this->send($label, __FUNCTION__, $params);
    }


    /**
     * 视频转码
     * @param $label
     * @param $params
     * @return array
     */
    public function ProcessMediaByProcedure($label, $params = [])
    {
        $whiteParam = ['FileId','ProcedureName', 'TasksPriority', 'TasksNotifyMode', 'SessionContext', 'SessionId'];

        foreach ($params as $key => $val){
            if (!in_array($key, $whiteParam)){
                unset($params[$key]);
            }
        }
        // 未设置转码流，读配置文件
        if (empty($params['ProcedureName'])){
            $params['ProcedureName'] = config('vod.'.$label.'.procedure_name','');
        }

        return $this->send($label, __FUNCTION__, $params);
    }

    /**
     * 任务详情查询
     * @param $label
     * @param $params
     * @return array
     */
    public function DescribeTaskDetail($label, $params = [])
    {
        $param['TaskId'] = $params['TaskId']??'';
        return $this->send($label, __FUNCTION__, $params);
    }

    /**
     * 发送请求
     * @param $label
     * @param $action
     * @param $params
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function send($label, $action, $params = [])
    {
        // 获取配置信息
        if (! $config = config('vod.'.$label)){
            return $this->message(1, "获取配置文件失败：".$label);
        }
        $params['SubAppId'] = (int)$config['sub_appid'];

        // 生成签名
        $curTime = time();
        $authorization = $this->getAuthorization($config, $params,$action, $curTime);

        // 执行请求
        $client = new Client();
        try {
            $response = $client->request('POST', 'https://vod.tencentcloudapi.com', [
                'verify' => false,
                'debug' => false,
                'headers' => [
                    'Authorization' => $authorization,
                    'Content-Type' => 'application/json; charset=utf-8',
                    'Host' => 'vod.tencentcloudapi.com',
                    'X-TC-Action' => $action,
                    'X-TC-Timestamp' => $curTime,
                    'X-TC-Version' => '2018-07-17',
                    'X-TC-Region' => ''
                ],
                'json' => $params,

            ]);
        }catch (\Exception $e){
            return $this->message(2, $e->getMessage());
        }

        if ($response->getStatusCode() != 200){
            dd($response->getStatusCode(), $response->getReasonPhrase(), $response);
        }
        if(! $content = json_decode($response->getBody()->getContents(), true)){
            return $this->message(3, '请求失败');
        }

        if (isset($content['Response']['Error'])){
            return  $this->message(4, $content['Response']['Error']['Message']);
        }

        return $this->message(0, '请求成功', $content);
    }

    /** 生成签名
     * @param $config
     * @param $params
     * @return string
     */
    private function getAuthorization($config, $params, $action, $curTime)
    {
        $curDate = gmdate("Y-m-d", $curTime);
        $credentialScope = $curDate . '/vod/tc3_request';
        $algorithm = "TC3-HMAC-SHA256";

        $canonicalRequest = "POST\n/\n\n".
            "content-type:application/json; charset=utf-8\n".
            "host:vod.tencentcloudapi.com\n\n".
            "content-type;host\n".
            hash("SHA256", json_encode($params));

        $stringToSign = $algorithm ."\n".
            $curTime . "\n".
            $credentialScope . "\n".
            hash("SHA256", $canonicalRequest);

        $signature = hash_hmac('SHA256', $curDate, 'TC3'.$config['secret_key'], true);
        $signature = hash_hmac('SHA256', 'vod', $signature, true);
        $signature = hash_hmac("SHA256", "tc3_request", $signature, true);
        $signature = hash_hmac("SHA256", $stringToSign, $signature);

        return $algorithm .' Credential='.$config['secret_id'].'/'.$credentialScope.', SignedHeaders=content-type;host, Signature='.$signature;
    }

    private function message($code, $message, $data = [])
    {
        return ['code' => $code, 'message' => $message, 'data' => $data];
    }

}
