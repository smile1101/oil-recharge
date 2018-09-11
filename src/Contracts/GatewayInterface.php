<?php

namespace Payment\Contracts;

use Payment\Supports\Collection;

interface GatewayInterface
{
    const STATUS_PROCESSING = 0; // 处理中
    const STATUS_SUCCESS = 1; // 成功
    const STATUS_FAIL = 9; // 失败
    /**
     * 获取产品列表
     * @param array $args
     * @return mixed
     */
    public function getProducts($args = []);

    /**
     * 查询余额
     * @param array $args
     * @return mixed
     */
    public function rest(...$args);

    /**
     * 查询订单
     * @param $args
     * @return mixed
     */
    public function orders(...$args);

    /**
     * @param array $payload
     * @return mixed
     */
    public function make(...$payload);

    /**
     * 回调处理
     * @return mixed
     */
    public function callback();

    /**
     * @return Collection
     */
    public function commit();
}