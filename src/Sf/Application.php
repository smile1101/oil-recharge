<?php

namespace Recharge\Sf;

use Recharge\Kernel\ServiceContainer;

/**
 * 顺丰物流
 * Class Application
 * @package Recharge\Sf
 */
class Application extends ServiceContainer
{

    /**
     * @var array
     */
    protected $providers = [
        Orders\ServiceProvider::class,
    ];

    /**
     * @param string $name
     * @param array  $arguments
     *
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        return call_user_func_array([$this[''], $name], $arguments);
    }
}