<?php

namespace Recharge\Sf\Orders;


use Pimple\Container;
use Pimple\ServiceProviderInterface;

class ServiceProvider implements ServiceProviderInterface
{
    public function register(Container $pimple)
    {
        $pimple['orders'] = function ($app) {
            return new Client($app);
        };
    }
}