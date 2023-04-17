<?php

namespace Githen\LaravelTencentVod\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class VodController extends Controller
{
    public function __construct()
    {
        $this->middleware(config('vod.gloabal.auth'));
    }

    /**
     * 获取签名
     * @param Request $request
     * @param $label
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSignature(Request $request, $label)
    {
        $result = app('jiaoyu.tencent.vod')->getSignature($label);
        if ($result['code']){
            return $this->message('1001', $result['message']);
        }

        return $this->message('0000', $result['message'], $result['data']);
    }

    public function expireTime(Request $request, $label)
    {
        $result = app('jiaoyu.tencent.vod')->ModifyMediaInfo($label, $request->all());
        dd($result);

    }

    private function message($code, $message, $data = [])
    {
        return response()->json([
            'status' => $code,
            'message' => $message,
            'data' => $data
        ]);
    }
}
