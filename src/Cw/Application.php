<?php

namespace Recharge\Cw;

use Recharge\Kernel\ServiceContainer;

class Application extends ServiceContainer
{
    /**
     * @var array
     */
    protected $providers = [
        Base\ServiceProvider::class,
        Violate\ServiceProvider::class,
    ];

    /**
     * @param string $name
     * @param array  $arguments
     *
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        return call_user_func_array([$this['base'], $name], $arguments);
    }
}