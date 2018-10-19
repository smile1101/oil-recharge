<?php
namespace Recharge\Recharge\Gxpay;

use Pimple\Container;
use Pimple\ServiceProviderInterface;

class ServiceProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        $app['gxpay'] = function ($app) {
            return new Client($app);
        };
    }
}