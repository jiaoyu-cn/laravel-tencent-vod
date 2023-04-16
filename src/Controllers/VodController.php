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

    public function getSignature(Request $request, $label)
    {
        $result = app('jiaoyu.tencent.vod')->getSignature($label);
        if ($result['code']){
            return $this->message('1001', $result['message']);
        }

        return $this->message('0000', $result['message'], $result['data']);
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
