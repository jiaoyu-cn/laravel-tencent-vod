# laravel-TencentVod
基于laravel的腾讯点播上传


[![image](https://img.shields.io/github/stars/jiaoyu-cn/laravel-tencent-vod)](https://github.com/jiaoyu-cn/laravel-tencent-vod/stargazers)
[![image](https://img.shields.io/github/forks/jiaoyu-cn/laravel-tencent-vod)](https://github.com/jiaoyu-cn/laravel-tencent-vod/network/members)
[![image](https://img.shields.io/github/issues/jiaoyu-cn/laravel-tencent-vod)](https://github.com/jiaoyu-cn/laravel-tencent-vod/issues)

## 安装

```shell
composer require githen/laravel-tencent-vod:~v1.0.0

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
     *       'procedure_name' =>  // 任务流名称
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
        'procedure_name' => ''
    ],
];

];

```
## 初始化视频上传实例
在`html`中引入`JS`文件

```html
<script src="/app-assets/js/scripts/qcloud/vod-js-sdk-v6.js'"></script>
<script src="/app-assets/js/scripts/qcloud/vod.upload.js'"></script>
```

实例化上传类
```javascript
myVideo = new Vodupload({
        dom:"#upload_file_input",
        // signature:"vod",
        config:'vod',
        signature:function (){return "{{$sign}}"},
        maxFilesize:{!! config('vod.global.size') !!},
        acceptedFiles:"{!! implode(',', config('vod.global.ext')) !!}",
        replace:false, // 当限制为1个时，是否直接替换
        progressInterval:100,
        cacheTime:600,
        callback:function (type, name, info) {
            // info  每种事件传递的数据信息
            
            // 回调会有五个事件
            // type:media name:upload    视频上传完成
            // type:media name:progress  视频上传进度
            // type:cover name:upload    封面图上传完成
            // type:cover name:progress  封面图上传进度
            // type:done name:done       获取视频在腾讯端的基本信息
    
        }
    });
```

| 参数               | 名称                               | 说明                                        | 备注                 |
|------------------|----------------------------------|-------------------------------------------|--------------------|
| dom              | 上传文件对应的&lt;input type=file&gt;标识 | 必填                                        |                    |
| config           | 配置文件中的标识                         | 必填                                        |                    |
| signature        | 获取上传签名的方法                        | 非必填，默认请求：jiaoyu/tencent/vod/sign/{config} |                    |
| maxFilesize      | 文件大小                             | MB                                        |                    |
| acceptedFiles    | 允许上传文件后缀(.jpg,.png)              | 默认为.mp4                                   |                    |
| chunkSize        | 分片大小                             | 单位：MB,默认2MB                               |                    |
| maxFiles         | 最多上传文件数                          | 非必填，默认：1                                  |                    |
| progressInterval | 进程回调间隔                           | 非必填，默认：200ms                              |                    |
| replace          | MaxFiles为1时，是否直接替换               |                                           |                    |
| cover            | 上传的封面图 File资源对象                  | 非必填                                       |                    |
| expireTime       | 视频临时存放时长                         | 非必填,默认：7200 单位：秒                          | 直接存放，值设为 undefined |


回调参数说明

| type  | action   | 说明        |
|-------|----------|-----------|
| media | progress | 媒体文件上传进度  |
| media | upload   | 媒体文件上传成功时 |
| cover | progress | 封面文件上传进度  |
| cover | upload   | 封面上传成功时   |
| done  | done     | 媒体文件上传结果  |
| files | error    | 上传报错      |
| files | remove   | 超过视频限制数量  |

## 自动注入的`路由`

| 请求方法 | 请求地址                              | 参数说明                                                            | 备注      |
|------|-----------------------------------|-----------------------------------------------------------------|---------|
| GET  | jiaoyu/tencent/vod/sign/{param}   | param(string)：使用的配置中的标识                                         | 获取上传签名  |
| GET  | jiaoyu/tencent/vod/modify/{param} | param(string)：使用的配置中的标识<br/>FileId：腾讯视频ID <br/>ExpireTime：到期时间戳 | 修改视频的时长 |

错误码

| 错误码  | 说明         | 备注 |
|------|------------|----|
| 1001 | 签名请求报错     |    |
| 1002 | 修改视频时长报错   |    |

## 支持方法

已封装的`Provider`提供的方法，

```php
// 注入服务的单例 app('jiaoyu.tencent.vod')
// ModifyMediaInfo 支持方法
app('jiaoyu.tencent.vod')->ModifyMediaInfo($label, $request->all());
```

### 调用方法 ModifyMediaInfo($label, $params = [])

修改视频基本信息
$label：配置标识
$params: 请求参数

| 参数          | 类型        | 说明                                                                |
|-------------|-----------|-------------------------------------------------------------------|
| FileId      | 必填：String | 媒体文件唯一标识。                                                         |
| Name        | String    | 媒体文件名称，最长 64 个字符。                                                 |
| Description | String    | 媒体文件描述，最长 128 个字符。                                                |
| ExpireTime  | String    | 媒体文件过期时间，采用 ISO 日期格式。填“9999-12-31T23:59:59Z”表示永不过期。               |
| CoverData   | String    | 视频封面图片文件（如 jpeg, png 等）进行 Base64 编码后的字符串，仅支持 gif、jpeg、png 三种图片格式。 |

### 调用方法 getSignature($label)

获取上传签名
$label：配置标识

### 调用方法 ProcessMediaByProcedure($label, $params = [])

对视频执行转码
$label：配置标识
$params: 请求参数

| 参数              | 类型        | 说明                                                                |
|-----------------|-----------|-------------------------------------------------------------------|
| FileId          | 必填：String | 媒体文件唯一标识。                                                         |
| ProcedureName   | 必填：String | 任务流模板名字。                                                          |
| TasksPriority   | Integer   | 任务流的优先级，数值越大优先级越高，取值范围是-10到10，不填代表0。                              |
| TasksNotifyMode | String    | 任务流状态变更通知模式，可取值有 Finish，Change 和 None，不填代表 Finish。                |
| SessionContext  | String    | 来源上下文，用于透传用户请求信息，任务流状态变更回调将返回该字段值，最长 1000 个字符。                    |
| SessionId       | String    | 用于去重的识别码，如果三天内曾有过相同的识别码的请求，则本次的请求会返回错误。最长 50 个字符，不带或者带空字符串表示不做去重。 |

### 调用方法 DescribeTaskDetail($label, $params = [])

获取任务详情
$label：配置标识
$params: 请求参数

| 参数     | 类型        | 说明   |
|--------|-----------|------|
| TaskId | 必填：String | 任务ID |

#### 调用方法 send($label, $action, $params = [])

发送请求
$label：配置标识
$action：腾讯云端的方法
$params: 请求参数，具体参考要请求的`$action`的腾讯文档

