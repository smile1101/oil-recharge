<?php

namespace Recharge\Gateways\Ofpay;


use Recharge\Supports\Collection;
use Recharge\Supports\Response;

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

    /**
     * 签名
     * @param $params
     * @param $key_str
     * @return Collection
     */
    public static function sign($params, $key_str)
    {
        $signKey = ['userid', 'userpws', 'cardid', 'cardnum', 'sporder_id', 'sporder_time', 'game_userid',
            'login_name', 'login_pwd', 'name', 'cert_type', 'cert_no', 'gas_card_no', 'email', 'phone_no', 'charge_type',
            'event_id', 'verification_code'];
        $signStr = '';
        foreach($signKey as $item) {
            if(isset($params[$item])) {
                $signStr .= $params[$item];
            }
        }
        $params['md5_str'] = strtoupper(md5($signStr . $key_str));
        return Response::response($params);
    }
}