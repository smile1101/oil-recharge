<?php

namespace Recharge\Gateways\Jlhpay;

use Recharge\Contracts\GatewayInterface;
use Recharge\Supports\Config;
use Recharge\Supports\Response;
use Recharge\Gateways\Support as SupportIterate;

class Support extends SupportIterate
{

    protected $baseUri = 'http://182.92.157.25:8160';

    /**
     * request an api
     * @param $endpoint
     * @param $params
     * @return \Recharge\Supports\Collection
     */
    public static function requestApi($endpoint, $params)
    {
        $response = self::getInstance()->get($endpoint, self::sign($params)->toArray(), [
            'Accept:application/json;charset=UTF-8'
        ]);

        if ($response['status'] == 'success' && $response['code'] == '00') {
            return Response::response([
                'status' => 1,
                'paySn' => $response['bizOrderId'],
                'code' => GatewayInterface::STATUS_PROCESSING //充值中
            ]);
        } else {
            return Response::response([
                'status' => -1,
                'msg' => "充值失败({$response['code']})，请联系客服手动充值",
            ]);
        }
    }

    /**
     * exec a request and callback
     * @param $endpoint
     * @param $params
     * @param Config $config
     * @return \Recharge\Supports\Collection
     */
    public static function callback($endpoint, $params, Config $config)
    {
        $response = self::getInstance()->get($endpoint, self::sign($params)->toArray(), [
            'Accept:application/json;charset=UTF-8'
        ]);

        if ($response['status'] == 'success' && $response['code'] == '00') {
            switch ($response['data']['status']) {
                case '0':
                case '1':
                case '4':
                case '9':
                    $ret = Response::response(['code' => GatewayInterface::STATUS_PROCESSING]);
                    break;
                case '2':
                    $ret = Response::response(['code' => GatewayInterface::STATUS_SUCCESS]);
                    break;
                default:
                    $ret = Response::response(['code' => GatewayInterface::STATUS_FAIL]);
                    break;
            }

            if ($ret['code'] == GatewayInterface::STATUS_SUCCESS || $ret['code'] == GatewayInterface::STATUS_FAIL) {
                return Response::response(self::getInstance()->get($config->get('retUrl'), [
                    'userId' => $config->get('userId'),
                    'bizId' => $response['data']['bizId'], //业务编号
                    'ejId' => $response['data']['ejId'],   //平台方订单号
                    'downstreamSerialno' => $response['data']['serialno'], //商户订单号
                    'code' => $response['data']['status'], //2成功 3：失败
                    'sign' => md5($response['data']['id'] . $response['data']['serialno'] . $response['data']['id'] .
                        $response['data']['status'] . $config->get('userId') . $config->get('secret'))
                ]));
            }
        }

        return Response::response($response);
    }

    /**
     * other request api
     * @param $endpoint
     * @param $params
     * @return \Recharge\Supports\Collection
     */
    public static function requestNative($endpoint, $params)
    {
        $response = self::getInstance()->get($endpoint, self::sign($params)->toArray(), [
            'Accept:application/json;charset=UTF-8'
        ]);

        return Response::response($response);
    }

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