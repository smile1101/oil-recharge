<?php
namespace Recharge\Kernel\Providers;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Symfony\Component\HttpFoundation\Request;

class RequestServiceProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        $app['request'] = function () {
            return Request::createFromGlobals();
        };
    }
}