<?php

namespace Recharge\Recharge;

use Recharge\Kernel\ServiceContainer;


/**
 * Class Application
 * @package Recharge\Oil
 */
class Application extends ServiceContainer
{
    /**
     * @var array
     */
    protected $providers = [
        Ofpay\ServiceProvider::class,
        Gxpay\ServiceProvider::class,
        OPhone\ServiceProvide::class,
        OFlow\ServiceProvide::class,
        Jlhpay\ServiceProvide::class,
    ];

    /**
     * @param string $name
     * @param array  $arguments
     *
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        return call_user_func_array([$this['gxpay'], $name], $arguments);
    }
}