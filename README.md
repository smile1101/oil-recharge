# Rrecharge Oil for PHP 
- 第三方加油充值[中石化|中石油]卡加油充值
- 第三方话费、流量充值

## Installation

Install the latest version with

```bash
$ composer require zlwleng/oil-recharge
```
#### version
- 2.0.1

#####公共状态定义

字段 | 类型 | 描述
:-----------: | :-----------: | :-----------:
status        | int           | 1:表示成功 -1:表示失败
code          | int           | 该字段为第三方返回状态 详情参考对应文档
paySn         | string        | 第三方订单号
msg           | string        | 描述信息

## Basic Usage

```php
<?php

/**
 * 欧飞公共参数配置
 * 允许留空
 */
$config = [
    'appId' => 'A08566',  
    'appKey' => '4c625b7861a92c7971cd2029c2fd3c4a',
    'appIv' => '',
    'appStr' => 'OFCARD',
    'retUrl' => '/notify',
    'version' => '6.0'
];
$app = \Recharge\Factory::recahrge($config);
#加油直充
$payload = [
    'orderId' => 'test' . mt_rand(111111, 999999),
    'cardNo' => '13281888888',
    'money' => 50
];
$response = $app->ofpay->pay($payload);
#话费充值
$payload = [
    'orderId' => 'test' . mt_rand(111111, 999999),
    'cardNo' => '1000113200018313897',
    'money' => 50
];
$response = $app->phone->pay($payload);
#流量充值
$response = $app->flow->pay($payload);

/**
 * 公象配置
 */
$config = [
    'appId' => 'gxTest',  
    'appKey' => '4c625b7861a92c7971cd2029c2fd3c4a',
    'appIv' => '19283746',
    'appStr' => '',
    'retUrl' => '/notify',
    'version' => '6.0'
];
/**
 * 玖零逅配置
 */
$config = [
    'appId' => 'jlhTest',  
    'appKey' => '4c625b7861a92c7971cd2029c2fd3c4a',
    'appIv' => '',
    'appStr' => '',
    'retUrl' => '/notify',
    'version' => ''
];
##其他选择
#gxpay::公象加油直充 jlhpay::玖零逅加油直充
```   
## About
- 目前支持 公象 玖零逅 欧飞
- 文档持续更新中


