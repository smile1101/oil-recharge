<?php

namespace Recharge\Tests;

use PHPUnit\Framework\TestCase;
use Recharge\App;

class AppTest extends TestCase
{
    public function testBuild()
    {
        //话费充值查询
//        $response = App::driver('ofpay')::phone([
//           /* 'partner' => 'A12345',
//            'desKey' => '12345678',
//            'desIv' => '123', //签名key
//            'retUrl' => '', //回调地址*/
//            'userid' => 'A08566',
//            'userpws' => '4c625b7861a92c7971cd2029c2fd3c4a',
//            'strKey' => 'OFCARD',
//            'retUrl' => '',
//        ])->search([
//            'orderId' => mt_rand(111111111, 999999999), //订单号
//            //'cardNo' => '90011111', //加油卡号
//            //'money' => '100', //充值金额
//            //'retUrl' => ''
//        ]);
        //话费充值
//        $response = App::driver('ofpay')::phone([
//            /* 'partner' => 'A12345',
//             'desKey' => '12345678',
//             'desIv' => '123', //签名key
//             'retUrl' => '', //回调地址*/
//            'userid' => 'A08566',
//            'userpws' => '4c625b7861a92c7971cd2029c2fd3c4a',
//            'strKey' => 'OFCARD',
//            'retUrl' => '',
//        ])->search([
//            'orderId' => mt_rand(111111111, 999999999), //订单号
//            'cardNo' => '90011111', //加油卡号
//            'money' => '100', //充值金额
//        ]);
        //加油直充
        $response = App::driver('jlhpay')::jlhpay([
             /*'partner' => 'test',
             'desKey' => 'qwertyuioppoiuytrewqasdf',
             'desIv' => '19283746', //签名key
             'retUrl' => '', //回调地址*/
            /*'userid' => 'A08566',
            'userpws' => '4c625b7861a92c7971cd2029c2fd3c4a',
            'strKey' => 'OFCARD',
            'retUrl' => '',*/
            'userId' => 284,
            'secret' => 'e59e98638eda244764d202b77ea7382c973a36c2b58b2a29ccbf20aedddbdbaf'
        ])->search([
            'orderId' => mt_rand(111111111, 999999999), //订单号
            'cardNo' => '100011111', //加油卡号
            'money' => '100', //充值金额
        ]);
        var_export($response);
    }
}