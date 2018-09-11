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

#欧飞加油卡直充 最小配置参数
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
return $response;
```

## About
- 目前支持 公象 玖零逅 欧飞
- 文档持续更新中


