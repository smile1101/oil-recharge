<?php
namespace Recharge\Recharge\Jlhpay;


use Pimple\Container;
use Pimple\ServiceProviderInterface;

class ServiceProvide implements ServiceProviderInterface
{
    public function register(Container $pimple)
    {
        $pimple['jlhpay'] = function ($app) {
            return new Client($app);
        };
    }
}