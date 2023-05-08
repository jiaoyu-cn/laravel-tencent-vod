<?php

return [

    /**
    |--------------------------------------------------------------------------
    | 视频上传配置
    |--------------------------------------------------------------------------
    |  '标识' => [
     *      'sub_appid' => , //点播应用的SubAppId
     *      'class_id' => , //点播应用的分类ID
     *       'secret_id' =>  // API密钥SecretId
     *       'secret_key' =>  // API密钥SecretKey
     *       'procedure_name' =>  // 任务流名称
     * ]
     */
    'global' => [
        'signature_url' => true, // 注册路由，生成签名
        'auth' => ['auth'], // 路由中间件
        'size' => 50, // MB
        'ext' => [".mp4"],
    ],
    'vod' => [
        'sub_appid' => env('TENCENT_VOD_SUB_APPID', 1500004122),
        'class_id' => env('CLASS_ID', 1066262),
        'secret_id' => '',
        'secret_key' => '',
        'procedure_name' => ''
    ],
];
