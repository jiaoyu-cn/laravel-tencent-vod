<?php

namespace Githen\LaravelTencentVod;

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
            ->name('jiaoyu.tencent.vod.sign'); // 文件上传
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

    private function message($code, $message, $data = [])
    {
        return ['code' => $code, 'message' => $message, 'data' => $data];
    }

}
