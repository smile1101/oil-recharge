<?php

namespace Recharge\Gateways;

use Recharge\Traits\HttpRequestTraits;

abstract class Support
{
    use HttpRequestTraits;

    /**
     * Instance.
     *
     * @var self()
     */
    private static $instance;

    /**
     * Bootstrap.
     */
    private function __construct()
    {
    }

    /**
     * Get instance.
     *
     * @return Support
     */
    public static function getInstance()
    {
        if (!(self::$instance instanceof self)) {
            self::$instance = new static();
        }

        return self::$instance;
    }
}