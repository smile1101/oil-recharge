<?php

namespace Recharge\Contracts;


interface GatewayApplicationInterface
{
    /**
     * @param $method
     * @param $driver
     * @return mixed
     */
    public function make($method, $driver);
}