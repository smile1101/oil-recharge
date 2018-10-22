<?php
namespace Recharge\Recharge\OGame;

use Pimple\Container;
use Pimple\ServiceProviderInterface;

class ServiceProvide implements ServiceProviderInterface
{
    public function register(Container $pimple)
    {
        $pimple['game'] = function ($app) {
            return new Client($app);
        };
    }
}