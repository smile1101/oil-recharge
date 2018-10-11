<?php

namespace Recharge\Gateways\Ofpay;

use Recharge\Supports\Collection;
use Recharge\Supports\Response;
use Recharge\Gateways\Support as SupportIterate;

class Support extends SupportIterate
{

    protected static $gateway = 'http://apitest.ofpay.com';

    /**
     * request an api and deal result
     * @param $endpoint
     * @param array $params
     * @param string $strKey
     * @return Collection
     */
    public static function requestApi($endpoint, $params = [], $strKey = '')
    {
        if (!empty($strKey))
            $params = self::sign($params, $strKey)->toArray();
        $endpoint = self::$gateway . $endpoint . http_build_query($params);
        $response = self::getInstance()->post($endpoint);
        if ((int)$response['retcode'] === 1) {
            return Response::response([
                'status' => 1,
                'paySn' => $response['orderid'],
                'code' => $response['game_state'] //充值中
            ]);
        } else
            return Response::response([
                'status' => -1,
                'msg' => !empty($response['err_msg']) ? $response['err_msg'] : '充值失败，联系客户手动充值！'
            ]);
    }

    /**
     * exec a request and callback an api
     * @param $endpoint
     * @param array $params
     * @param string $strKey
     * @param string $retUrl
     * @return Collection
     */
    public static function callback($endpoint, $params = [], $strKey = '', $retUrl = '')
    {
        if (!empty($strKey))
            $params = self::sign($params, $strKey)->toArray();
        $endpoint = self::$gateway . $endpoint . http_build_query($params);
        $response = self::getInstance()->post($endpoint);
        if ($response['retcode'] == '1') {
            $ret = ['status' => $response['game_state']];
            if ($response['game_state'] == '1' || $response['game_state'] == '9') {
                //回调处理
                return Response::response(self::getInstance()->post($retUrl, [
                    'sporder_id' => $response['sporder_id'],
                    'ret_code' => $response['game_state'],
                ]));
            }
            return Response::response($ret);
        } else
            return Response::response($response);

    }

    /**
     * exec a request and not deal
     * @param $endpoint
     * @param array $params
     * @param string $strKey
     * @return Collection
     */
    public static function requestOther($endpoint, $params = [], $strKey = '')
    {
        if (!empty($strKey))
            $params = self::sign($params, $strKey)->toArray();

        $endpoint = self::$gateway . $endpoint . http_build_query($params);

        return Response::response(self::getInstance()->post($endpoint));
    }

    /**
     * verify a sign|create a sign
     * @param $params
     * @param $strKey
     * @return Collection
     */
    public static function sign($params, $strKey)
    {
        $signKey = ['userid', 'userpws', 'cardid', 'cardnum', 'sporder_id', 'sporder_time', 'game_userid',
            'login_name', 'login_pwd', 'name', 'cert_type', 'cert_no', 'gas_card_no', 'email', 'phone_no', 'charge_type',
            'event_id', 'verification_code'];
        $signStr = '';
        foreach ($signKey as $item) {
            if (isset($params[$item])) {
                $signStr .= $params[$item];
            }
        }
        $params['md5_str'] = strtoupper(md5($signStr . $strKey));
        return Response::response($params);
    }
}