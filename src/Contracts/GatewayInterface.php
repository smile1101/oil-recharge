<?php

namespace Recharge\Contracts;

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
     * @return mixed
     */
    public function rest();

    /**
     * 查询订单
     * @param $args
     * @return mixed
     */
    public function search($args);

    /**
     * @param array $payload
     * @return mixed
     */
    public function pay($payload);

    /**
     * 回调处理
     * @return mixed
     */
    public function callback();

    /**
     * 验签
     * @param $data
     * @return mixed
     */
    public function verify($data);
}