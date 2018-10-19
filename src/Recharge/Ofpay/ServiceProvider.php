<?php

namespace Recharge\Recharge\Ofpay;

use Pimple\Container;
use Pimple\ServiceProviderInterface;

class ServiceProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        $app['ofpay'] = function ($app) {
            return new Client($app);
        };
    }
}