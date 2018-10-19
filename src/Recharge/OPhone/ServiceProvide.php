<?php
namespace Recharge\Recharge\OPhone;

use Pimple\Container;
use Pimple\ServiceProviderInterface;

class ServiceProvide implements ServiceProviderInterface
{
    public function register(Container $pimple)
    {
        $pimple['phone'] = function ($app) {
            return new Client($app);
        };
    }
}