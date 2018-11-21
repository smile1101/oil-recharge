<?php

namespace Recharge\Tests;

use PHPUnit\Framework\TestCase;
use Recharge\Factory;

/**
 * Class AppTest
 * exec php vendor/phpunit/phpunit/phpunit tests/AppTest.php
 * @package Recharge\Tests
 */
class AppTest extends TestCase
{
    /**
     * @throws \Exception
     */
    public function testBuild()
    {
        date_default_timezone_set('PRC');
        /*$config = [
            'appId' => 'A08566',  //partner
            'appKey' => '4c625b7861a92c7971cd2029c2fd3c4a',
            'appIv' => '19283746', //desiv
            'appStr' => 'OFCARD',
            'retUrl' => '/notify',
            'version' => '6.0'
        ];*/

        //顺丰接口测试
        /*$config = [
            'appId' => 'LP_leOtG',
            'appKey' => 'AvIeSB6hJxdFVmJasXQdcB9CEVOs6Ag1',
            'retUrl' => '/nptify',
            'log' => dirname(dirname(__FILE__)) . '/SF',
        ];
        $payload = [
            'order' => [
                'orderId' => 'SF' . date('YmdHis'),
                'fromCompany' => '花花',
                'fromContact' => '花花',
                'fromTel' => '18888888888',
                'fromProvince' => '广东省',
                'fromCity' => '深圳市',
                'fromCounty' => '南山区',
                'fromAddress' => '桃花源0001号',
                'toCompany' => '世纪银行',
                'toContact' => '花花',
                'toTel' => '7551234567',
                'toProvince' => '广东省',
                'toCity' => '深圳市',
                'toCounty' => '南山区',
                'toAddress' => '科技大厦',
                'custId' => '7551234567',
                'payMethod' => 2,
                'reserveTime' => date('Y-m-d H:i:s')
            ],
            'cargo' => [ //货物名称
                [
                    'name' => '英雄联盟手办',
                    'count' => '1',
                    'unit' => '个'
                ], //...
            ],
            'addedService' => [ //增值说明
                [
                    'name' => '测试',
                    'value' => '1000',
                    'value1' => '￥1000',
                ]
            ]
        ];*/
        /*$payload = [
            'orderId' => 'SF20181115154626',
            //'dealType' => 2 //取消订单标记
        ];*/
        /*$app = Factory::sf($config);
        $response = $app->orders->create($payload);
        var_export($response);*/

        $config = [
            'appId' => 'A10021',
            'appKey' => '123456789',
            'appStr' => '123456789',
            'retUrl' => '/notify'
        ];

        $app = Factory::cw($config);

        $payload = [
            'carNumber' => '粤B8N589',
            'vin' => '123456',
            'engine' => '123456',
            //'spOrder' => 'TEST' . date('YmdHis'),
            //'uniqueCode' => 'a03be530-8eeb-43ab-8095-26fd7383c73f',
        ];

        $response = $app->violate->query($payload);

        var_export($response);
    }
}