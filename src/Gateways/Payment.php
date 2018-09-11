<?php

namespace Recharge\Gateways;

use Recharge\Contracts\GatewayApplicationInterface;
use Recharge\Contracts\GatewayInterface;
use Recharge\Exceptions\InvalidGatewayException;
use Recharge\Supports\Config;
use Recharge\Supports\Str;

class Recharge implements GatewayApplicationInterface
{

    /**
     * Config.
     *
     * @var Config
     */
    protected $config;

    /**
     * 渠道
     * @var $channel
     */
    protected $channel;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * @param $method
     * @param mixed $args
     * @return mixed
     */
    public function make($method, $args)
    {
        $this->channel = $args;

        $gateway =  __NAMESPACE__ . '\\' . Str::studly($method) . '\\' . Str::studly($this->channel) . 'Gateway';

        if (class_exists($gateway)) {
            return $this->makePay($gateway);
        }

        throw new InvalidGatewayException("make Gateway [{$gateway}] not exists");
    }

    /**
     * 方法回调
     * @param $gateway
     * @return mixed
     */
    protected function makePay($gateway)
    {
        $app = new $gateway($this->config);

        if ($app instanceof GatewayInterface) {
            return $app;
        }

        throw new InvalidGatewayException("make Gateway [{$gateway}] Must Be An Instance Of GatewayInterface");
    }

    /**
     * 动态调用
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        return $this->make($name, $arguments[0]);
    }
}