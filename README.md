# laravel-TencentVod
基于laravel的腾讯点播上传


[![image](https://img.shields.io/github/stars/jiaoyu-cn/laravel-tencent-vod)](https://github.com/jiaoyu-cn/laravel-tencent-vod/stargazers)
[![image](https://img.shields.io/github/forks/jiaoyu-cn/laravel-tencent-vod)](https://github.com/jiaoyu-cn/laravel-tencent-vod/network/members)
[![image](https://img.shields.io/github/issues/jiaoyu-cn/laravel-tencent-vod)](https://github.com/jiaoyu-cn/laravel-tencent-vod/issues)

## 安装

```shell
composer require githen/laravel-tencent-vod:^v1.0.0

# 迁移配置文件
php artisan vendor:publish --provider="Githen\LaravelTencentVod\VodProvider"
```
## 配置文件说明
生成`vod.php`上传配置文件
```php
return [
    /**
    |--------------------------------------------------------------------------
    | 腾讯视频上传配置
    |--------------------------------------------------------------------------
    |  '标识' => [
     *      'sub_appid' => 0, // 子应用id
     *      'class_id' => 0  // 应用的分类 id
     *      'secret_id' => ''  // API授权ID
     *      'secret_key' => ''  // API授权KEY
     * ]
     */
return [
    'global' => [
        'signature_url' => true, // 注册路由，生成签名  请求地址：/jiaoyu/tencent/vod/sign/{标识}
        'auth' => ['auth'], // 路由中间件
        'size' => 50, // MB
        'ext' => [".mp4"],
    ],
    'vod' => [
        'sub_appid' => env('TENCENT_VOD_SUB_APPID', 0),
        'class_id' => env('CLASS_ID', 0),
        'secret_id' => '***',
        'secret_key' => '***',
    ],
];

];

```


| 参数               | 名称                        | 说明                                       | 备注                 |
|------------------|---------------------------|------------------------------------------|--------------------|
| dom              | 上传文件对应的<input type=file>标识 | 必填                                       |                    |
| config           | 配置文件中的标识                  | 必填 |                    |
| signature        | 获取上传签名的方法                 | 非必填，默认请求：jiaoyu/tencent/vod/sign/{config} |                    |
| maxFilesize      | 文件大小                      | MB                                       |                    |
| acceptedFiles    | 允许上传文件后缀(.jpg,.png)       | 默认为.mp4                                  |                    |
| chunkSize        | 分片大小                      | 单位：MB,默认2MB                              |                    |
| maxFiles         | 最多上传文件数                   | 非必填，默认：1                                 |                    |
| progressInterval | 进程回调间隔                    | 非必填，默认：200ms                             |                    |
| replace          | MaxFiles为1时，是否直接替换        |                                          |                    |
| cover            | 上传的封面图 File资源对象           | 非必填                                      |                    |
| expireTime       | 视频临时存放时长                  | 非必填,默认：7200 单位：秒                         | 直接存放，值设为 undefined |


callbakck 参数
回调参数说明

| type  | action   | 说明        |
|-------|----------|-----------|
| media | progress | 媒体文件上传进度  |
| media | upload   | 媒体文件上传成功时 |
| cover | progress | 封面文件上传进度  |
| cover | upload   | 封面上传成功时   |
| done  | done     | 媒体文件上传结果  |
| files | remove   | 删除文件      |

type  media cover  done
action  progress upload  done


错误码

| 错误码  | 说明                     | 备注 |
|------|------------------------|----|
| 1001 | 未获取到 config/vod.php中配置 |    |
| 1002 | 请求异常                   |    |
|      |                        |    |
| 2001 | 请求腾讯，未获取到数据            |    |
| 2002 | 腾讯云返回的报错信息             |    |
