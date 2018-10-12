<?php

namespace Recharge\Gateways\Gxpay;

use Recharge\Contracts\GatewayInterface;
use Recharge\Gateways\Support as SupportIterate;
use Recharge\Supports\Config;
use Recharge\Supports\Response;

class Support extends SupportIterate
{

    protected static $gateway = 'http://oapi.gxcards.com/';

    //protected static $gateway = 'http://boapi.gxcards.com/';

    /**
     * request an api
     * @param $endpoint
     * @param $params
     * @param $desKey
     * @param $desIv
     * @return \Recharge\Supports\Collection
     */
    public static function requestApi($endpoint, $params, $desKey, $desIv)
    {
        $timestamp = floor(microtime(true) * 1000) . '';
        $params['timestamp'] = $timestamp;
        $endpoint = self::$gateway . md5($endpoint) .
            '?t=' .
            urlencode(self::encrypt3Des($timestamp, $desKey, $desIv));

        $response = self::getInstance()->post($endpoint, self::encrypt3Des(json_encode($params), $desKey, $desIv), [
            'headers' => ['Content-Type: application/json; charset=UTF-8']
        ]);
        if (isset($response['status']) && $response['status'] == '200') {
            //$status = $response['data']['status'] == -1 ? 9 : ($response['data']['status'] == 6 ? 1 : 0);
            return Response::response([
                'status' => 1,
                'paySn' => $response['data']['orderNo'], //回调订单号
                'code' => GatewayInterface::STATUS_PROCESSING, //充值中
                'msg' => $response['statusText'],
            ]);
        } else {
            return Response::response([
                'status' => -1,
                'code' => $response['status'],
                'msg' => $response['statusText'],
                'remark' => "充值失败({$response['status']})，请联系客服手动充值"
            ]);
        }
    }

    /**
     * request a api and callback
     * @param $endpoint
     * @param $params
     * @param Config $config
     * @return \Recharge\Supports\Collection
     */
    public static function callback($endpoint, $params, Config $config)
    {
        $timestamp = floor(microtime(true) * 1000) . '';
        $params['timestamp'] = $timestamp;
        $endpoint = self::$gateway . md5($endpoint) .
            '?t=' .
            urlencode(self::encrypt3Des($timestamp, $config->get('desKey'), $config->get('desIv')));
        $orderId = $params['orderId'];
        $gxOrder = $params['gxOrder'];
        unset($params['orderId'], $params['gxOrder']);
        $response = self::getInstance()->post($endpoint,
            self::encrypt3Des(json_encode($params),
                $config->get('desKey'),
                $config->get('desIv')
            ), [
                'headers' => ['Content-Type: application/json; charset=UTF-8']
            ]);

        if (isset($data['status']) && $response['status'] == 200) {
            // -1 充值失败 6 充值成功 4 充值中
            //$status = $response['data']['status'] == -1 ? 1 : ($response['data']['status'] == 6 ? 6 : 0);
            //回调处理
            return Response::response(self::getInstance()->post($config->get('retUrl'), [
                'partnerId' => $orderId,
                'partnerNo' => $config->get('partner'),
                'gxOrderNo' => $gxOrder,
                'status' => $response['data']['status'],
                'sign' => md5('partnerId' . $orderId
                    . 'partnerNo' . $config->get('partner')
                    . 'gxOrderNo' . $gxOrder
                    . 'status' . $response['data']['status'] . $config->get('desKey'))
            ]));
        } else {
            return Response::response([
                'status' => -1,
                'code' => $response['status'],
                'msg' => $response['statusText'],
                'remark' => '订单信息查询失败'
            ]);
        }
    }

    /**
     * native a request api
     * @param $endpoint
     * @param $params
     * @param $desKey
     * @param $desIv
     * @return \Recharge\Supports\Collection
     */
    public static function requestNative($endpoint, $params, $desKey, $desIv)
    {
        $timestamp = floor(microtime(true) * 1000) . '';
        $params['timestamp'] = $timestamp;
        $endpoint = self::$gateway . md5($endpoint) .
            '?t=' .
            urlencode(self::encrypt3Des($timestamp, $desKey, $desIv));
        $response = self::getInstance()->post($endpoint, self::encrypt3Des(json_encode($params), $desKey, $desIv), [
            'headers' => ['Content-Type: application/json; charset=UTF-8']
        ]);

        return Response::response($response);
    }

    /**
     * encode a param|string
     * @param $params
     * @param $desKey
     * @param $desIv
     * @return string
     */
    public static function encrypt3Des($params, $desKey, $desIv)
    {
        return openssl_encrypt(
            $params,
            'des-ede3-cbc',
            $desKey,
            0, $desIv
        );
    }
}