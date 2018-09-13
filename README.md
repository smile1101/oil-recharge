# Rrecharge Oil for PHP 
 1.针对第三方加油充值[中石化|中石油]卡加油充值

## Installation

Install the latest version with

```bash
$ composer require zlwleng/oil-recharge
```

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
//业务参数
$playod = [
    'orderId' => mt_rand(111111111, 999999999), //订单号
    'cardNo' => '100011xxx', //加油卡号
    'money' => '100', //充值金额
]; 
$response = App::driver('gxpay')
    ::gxpay('gxpay')
    ->pay($playod)
    ->response;

echo $response->status; //成功 -1: 表示失败 
echo $response->msg; //错误信息

#公象回调处理
$response = App::driver('gxpay')
     ::gxpay('gxpay')
     ->callback()
     ->response;
//$response[status] = 1 表示成功

#自己处理回调验签
$request = $_POST;//获取
//laravel
$request = request()->all();
$verify = App::driver('gxpay')
     ::gxpay('gxpay')
     ->verify($request);
var_export($verify); //true 成功 false 失败

```   

## About
- 目前支持 公象 玖零逅 欧飞
- 文档持续更新中


