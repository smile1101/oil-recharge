<?php

namespace Recharge\Gateways\Gxpay;


class Support
{
    /**
     * Instance.
     *
     * @var Support
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
            self::$instance = new self();
        }

        return self::$instance;
    }

    public static function encrypt3Des($params, $des_key, $des_iv)
    {
        return openssl_encrypt($params, 'des-ede3-cbc', $des_key, 0, $des_iv);
    }
}