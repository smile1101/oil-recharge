<?php
namespace Recharge\Kernel\Providers;


use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Recharge\Supports\Config;

class ConfigServiceProvider implements ServiceProviderInterface
{
    public function register(Container $pimple)
    {
        $pimple['config'] = function ($app) {
            return new Config($app->getConfig());
        };
    }
}