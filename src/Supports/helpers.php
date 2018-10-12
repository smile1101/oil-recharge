<?php
/**
 * 充值辅助函数
 */

use Recharge\App;
use Recharge\Exceptions\InvalidArgumentException;

if (!function_exists('zlw_recharge')) {
    /**
     * 充值便捷辅助函数
     * @param string $driver 驱动目录
     * @param string $gateway 驱动类
     * @param array $config  公共配置参数
     * @param array $payload 业务参数
     * @throws Exception
     * @return Exception|\Recharge\Supports\Collection|mixed
     */
    function zlw_recharge($driver, $gateway, $config = [], $payload = [])
    {
        if (is_null($driver) || is_null($gateway))
            throw new InvalidArgumentException('necessary params cannot be empty!');
        if (empty($config))
            throw new InvalidArgumentException('config cannot be empty!');
        if (empty($payload))
            throw new InvalidArgumentException('payload cannot be empty!');
        return App::driver($driver)::{$gateway}($config)->pay($payload);
    }
}

if (!function_exists('zlw_callback')) {
    /**
     * 回调调用辅助函数
     * @param string $driver 驱动目录
     * @param string $gateway 驱动类
     * @param array $config  公共配置参数
     * @throws Exception
     * @return Exception|\Recharge\Supports\Collection|mixed
     */
    function zlw_callback($driver, $gateway, $config = [])
    {
        if (is_null($driver) || is_null($gateway))
            throw new InvalidArgumentException('necessary params cannot be empty!');
        if (empty($config))
            throw new InvalidArgumentException('config cannot be empty!');
        return App::driver($driver)::{$gateway}($config)->callback();
    }
}

if (!function_exists('zlw_methods')) {
    /**
     * 其他方法调用辅助函数
     * @param string $driver 驱动目录
     * @param string $gateway 驱动类
     * @param string $func 驱动方法
     * @param array $config  公共配置参数
     * @param array $payload 业务方法
     * @throws Exception
     * @return Exception|\Recharge\Supports\Collection|mixed
     */
    function zlw_methods($driver, $gateway, $func, $config = [], $payload = [])
    {
        if (is_null($driver) || is_null($gateway) || is_null($func))
            throw new InvalidArgumentException('necessary params cannot be empty!');
        if (empty($config))
            throw new InvalidArgumentException('config cannot be empty!');
        return App::driver($driver)::{$gateway}($config)->$func($payload);
    }
}

