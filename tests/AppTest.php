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
        $config = [
            'appId' => 'A08566',  //partner
            'appKey' => '4c625b7861a92c7971cd2029c2fd3c4a',
            'appIv' => '19283746', //desiv
            'appStr' => 'OFCARD',
            'retUrl' => '/notify',
            'version' => '6.0'
        ];
        $payload = [
            'orderId' => mt_rand(111111, 999999),
            'cardNo' => '13281868806',
            'money' => 50
        ];
        $app = Factory::recharge($config);
        var_export($app->flow->pay($payload));
    }
}