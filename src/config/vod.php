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
     * ]
     */
    'vod' => [
        'sub_appid' => env('TENCENT_VOD_SUB_APPID', '0'),
        'class_id' => env('CLASS_ID', 0),
        'secret_id' => '***',
        'secret_key' => '***',
    ],
];
