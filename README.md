# Rrecharge Oil for PHP 
 1.第三方加油充值[中石化|中石油]卡加油充值
 2.第三方话费、流量充值

## Installation

Install the latest version with

```bash
$ composer require zlwleng/oil-recharge
```

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

use Recharge\App;

#加油卡直充 最小配置参数

#公象 加油直充示例
//公共参数
$config = [
    'partner' => 'A12345',
    'desKey' => '12345678',
    'desIv' => '123', //签名key
    'retUrl' => '', //回调地址
]; 
$gxpay = App::driver('gxpay');
//业务参数
$playod = [
    'orderId' => 'GX82252154', //订单号
    'cardNo' => '1000111111111111111', //加油卡号
    'money' => '100', //充值金额
]; 
$response = $gxpay::gxpay($config)->pay($playod);

echo $response->status; //成功 -1: 表示失败 
echo $response->msg; //错误信息

#公象回调处理
$response = $gxpay::gxpay($config)->callback();
//$response[status] = 1 表示成功

#自己处理回调验签
$request = $_POST;//获取
//laravel
$request = request()->all();
$verify = $gxpay::gxpay($config)->verify($request);
var_export($verify); //true 成功 false 失败

#二、欧飞充值
#公共参数
$config = [
    'userId' => 'A08566',
    'userPws' => '4c625b7861a92c7971cd2029c2fd3c4a',
    'strKey' => '123', //签名key
    'retUrl' => 'OFCARD', //回调地址 针对业务场景可调整
];
$ofpay = App::driver('ofpay');
#1.加油直充
#业务参数
$playod = [
    'cardNo' => '1000111111111111111', //加油卡号
    'orderId' => 'OF123456', //订单号
    'money' => 100, //充值金额 ￥：100
];
$response = $ofpay::ofpay($config)->pay($playod);
// $response 

#2.话费充值
#业务参数
$playod = [
    'cardNo' => '13388888888', //手机号
    'orderId' => 'OF123456', //订单号
    'money' => 100, //充值金额 ￥：100
];
$response = $ofpay::phone($config)->pay($playod);

#3.流量充值
#业务参数
$playod = [
    'cardNo' => '13388888888', //手机号
    'orderId' => 'OF123456', //订单号
    'money' => 100, //充值金额 ￥：100
];
$response = $ofpay::flow($config)->pay($playod);

#4.流量充值异步回调处理
$response = $ofpay::flow($config)->callback();

// $response 处理结果

```   

## About
- 目前支持 公象 玖零逅 欧飞
- 文档持续更新中


