<?php
namespace Recharge\Recharge\OFlow;

use Pimple\Container;
use Pimple\ServiceProviderInterface;

class ServiceProvide implements ServiceProviderInterface
{
    public function register(Container $pimple)
    {
        $pimple['flow'] = function ($app) {
            return new Client($app);
        };
    }
}