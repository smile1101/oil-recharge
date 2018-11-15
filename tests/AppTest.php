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
        $config = [
            'appId' => 'LP_leOtG',
            'appKey' => 'AvIeSB6hJxdFVmJasXQdcB9CEVOs6Ag1',
            'retUrl' => '/nptify',
            'log' => dirname(dirname(__FILE__)) . '/SF',
        ];
        $payload = [
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
            'reserveTime' => date('Y-m-d H:i:s'),
        ];
        $payload = [
            'orderId' => 'SF20181115154626',
            //'dealType' => 2 //取消订单标记
        ];
        $app = Factory::sf($config);
        $response = $app->orders->routeAccept($payload);
        var_export($response);
    }
}