<?php

namespace Recharge;

use Recharge\Contracts\GatewayApplicationInterface;
use Recharge\Exceptions\InvalidGatewayException;
use Recharge\Gateways\Payment;
use Recharge\Supports\Config;
use Recharge\Supports\Str;

/**
 * classmap into
 * @package Recharge\Oli
 */
class App
{
    private $config;

    /**
     * 驱动器
     * @var $driver
     */
    protected static $driver;

    public static function driver($driver)
    {
        self::$driver = $driver;
        return new static();
    }

    /**
     * 验证
     * @param $class
     * @param $args
     * @return mixed
     */
    protected function build($class, $args)
    {
        $this->config = new Config($args[0]);

        return $this->make($class);
    }

    /**
     * 初始化
     * @param $class
     * @return mixed
     */
    protected function make($class)
    {
        $app = new Payment($this->config);

        if ($app instanceof GatewayApplicationInterface) {
            return $app->make($class, self::$driver);
        }

        throw new InvalidGatewayException("Gateway [$class] Must Be An Instance Of GatewayApplicationInterface");
    }

    /**
     * @param $class
     * @param $args
     * @return mixed
     */
    public static function __callStatic($class, $args)
    {
        $app = new static();
        return $app->build($class, $args);
    }
}