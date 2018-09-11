<?php

namespace Payment\Contracts;


interface GatewayApplicationInterface
{
    /**
     * @param $method
     * @param mixed $args
     * @return mixed
     */
    public function make($method, $args);
}