<?php

namespace Recharge\Tests;

use PHPUnit\Framework\TestCase;
use Recharge\App;

class AppTest extends TestCase
{
    public function testBuild()
    {
        //直充
        $response = App::driver('gxoay')::gxpay([
            'partner' => 'A12345',
            'desKey' => '12345678',
            'desIv' => '123', //签名key
            'retUrl' => '', //回调地址
        ])->pay([
            'orderId' => mt_rand(111111111, 999999999), //订单号
            'cardNo' => '100011xxx', //加油卡号
            'money' => '100', //充值金额
        ])->response;
        var_export($response);
    }
}