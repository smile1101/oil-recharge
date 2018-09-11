<?php

namespace Recharge\Gateways\Jlhpay;

use Recharge\Supports\Response;

class Support
{
    /**
     * 签名并返回
     * @param $params
     * @return \Recharge\Supports\Collection
     */
    public static function sign($params)
    {
        $secret = $params['secret'];
        unset($params['$secret']);
        $signKey = ['dtCreate', 'itemId', 'serialno', 'uid', 'userId'];
        $signStr = '';
        foreach($signKey as $item) {
            if(isset($params[$item])) {
                $signStr .= $params[$item];
            }
        }
        $params['sign'] = md5($signStr . $secret);

        return Response::response($params);
    }
}