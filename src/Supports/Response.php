<?php

namespace Recharge\Supports;

class Response
{
    /**
     * 返回
     * @param mixed ...$args
     * @return Collection
     */
    public static function response(...$args)
    {
        if (empty($args)) {
            $args = [
                'status' => -1,
                'msg' => '处理失败'
            ];
        }
        $collection = new Collection($args);
        return $collection->get(0);
    }
}