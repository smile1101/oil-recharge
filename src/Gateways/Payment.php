<?php

namespace Recharge\Gateways;

use Recharge\Contracts\GatewayApplicationInterface;
use Recharge\Contracts\GatewayInterface;
use Recharge\Exceptions\InvalidGatewayException;
use Recharge\Supports\Config;
use Recharge\Supports\Str;

class Payment implements GatewayApplicationInterface
{

    /**
     * Config.
     *
     * @var Config
     */
    protected $config;


    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * @param $method
     * @param $driver
     * @return mixed
     */
    public function make($method, $driver)
    {
        $gateway =  __NAMESPACE__ . '\\' . Str::studly($driver) . '\\' . Str::studly($method) . 'Gateway';

        if (class_exists($gateway)) {
            return $this->build($gateway);
        }

        throw new InvalidGatewayException("make Gateway [{$gateway}] not exists");
    }

    /**
     * 方法回调
     * @param $gateway
     * @return mixed
     */
    protected function build($gateway)
    {
        $app = new $gateway($this->config);

        if ($app instanceof GatewayInterface) {
            return $app;
        }

        throw new InvalidGatewayException("make Gateway [{$gateway}] Must Be An Instance Of GatewayInterface");
    }
}
