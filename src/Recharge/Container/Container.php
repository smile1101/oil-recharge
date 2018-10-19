<?php

namespace Recharge\Recharge\Container;

/**
 * Interface Container
 * @package Recharge\Oil\Container
 */
interface Container
{

    const STATUS_PROCESSING = 0; // deal...
    const STATUS_SUCCESS = 1; // success
    const STATUS_FAIL = 9; // failure
    /**
     * get product lists
     * @param array $params
     * @return mixed
     */
    public function products($params = []);

    /**
     * order and pay
     * @param array $payload
     * @return mixed
     */
    public function pay($payload = []);

    /**
     * query order result
     * @param array $params
     * @return mixed
     */
    public function query($params = []);

    /**
     * account rest query
     * @return mixed
     */
    public function rest();

    /**
     * callback
     * @return mixed
     */
    public function callback();
}