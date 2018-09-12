# Rrecharge Oil for PHP 

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
$config = [
    'userId' => 'A12345',
    'userPwd' => '12345678',
    'keyStr' => 'ofpay', //签名key
    'desKey' => 'abcd',   //加密key
    'retUrl' => '', //回调地址
]; //公共参数|基础参数
$playod = [
    'orderId' => '', //订单号
    'cardNo' => '100011xxx', //加油卡号
    'money' => '100', //充值金额
]; //业务参数
$response = App::payment($config)
    ->ofpay('ofpay')
    ->make($playod)
    ->commit();

var_export($response); //提交订单结果
//return $response;
#欧飞回调调用
$response = App::payment($config)
    ->ofpay('ofpay')
    ->callback()
    ->commit();
//$response[status] = 1 表示成功
//code 为订单状态 1 成功 9 撤销|失败

#公象 加油直充示例
$config = [
    'partner' => 'A12345',
    'desKey' => '12345678',
    'desIv' => '123', //签名key
    'retUrl' => '', //回调地址
]; //公共参数|基础参数
$playod = [
    'orderId' => '', //订单号
    'cardNo' => '100011xxx', //加油卡号
    'money' => '100', //充值金额
]; //业务参数
$response = App::payment($config)
    ->gxpay('gxpay')
    ->make($playod)
    ->commit();

#公象回调调用
$response = App::payment($config)
    ->gxpay('gxpay')
    ->callback()
    ->commit();
//$response[status] = 1 表示成功
```

## About
- 目前支持 公象 玖零逅 欧飞
- 文档持续更新中


