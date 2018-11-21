<?php

namespace Recharge\Cw\Violate;

use Pimple\Container;
use Pimple\ServiceProviderInterface;

class ServiceProvider implements ServiceProviderInterface
{
    public function register(Container $pimple)
    {
        $pimple['violate'] = function ($app){
            return new Client($app);
        };
    }
}