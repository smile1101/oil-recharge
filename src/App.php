<?php

namespace Payment;

use Payment\Contracts\GatewayApplicationInterface;
use Payment\Exceptions\InvalidGatewayException;
use Payment\Supports\Config;
use Payment\Supports\Str;

/**
 * classmap into
 * @package Payment\Oli
 */
class App
{
    private $config;

    public function __construct($config)
    {
        $this->config = new Config($config);
    }

    /**
     * 验证
     * @param $method
     * @return mixed
     */
    protected function build($method)
    {
        $gateways = __NAMESPACE__ . "\\Gateways\\" . Str::studly($method);

        if (class_exists($gateways)) {
            return self::make($gateways);
        }

        throw new InvalidGatewayException("Gateway [{$method}] Not Exists");
    }

    /**
     * 初始化
     * @param $class
     * @return mixed
     */
    protected function make($class)
    {
        $app = new $class($this->config);

        if ($app instanceof GatewayApplicationInterface) {
            return $app;
        }

        throw new InvalidGatewayException("Gateway [$class] Must Be An Instance Of GatewayApplicationInterface");
    }

    /**
     * @param \stdClass $name 类名
     * @param mixed $arguments 基础参数
     * @return mixed
     */
    public static function __callStatic($name, $arguments)
    {
        $app = new self(...$arguments);
        return $app->build($name);
    }
}