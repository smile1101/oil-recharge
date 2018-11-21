<?php

namespace Recharge\Recharge\Jlhpay;

use Recharge\Exceptions\RuntimeException;
use Recharge\Kernel\BasicClient;
use Recharge\Recharge\Container\Container;
use Recharge\Supports\Response;

/**
 * 玖零逅加油充值
 * Class Client
 * @package Recharge\Recharge\Jlhpay
 */
class Client extends BasicClient implements Container
{

    protected $baseUri = 'http://182.92.157.25:8160';

    /**
     * get product ids
     * @param array $params
     * @return mixed|null
     */
    public function products($params = [])
    {
        $cardNo = (int)$params['cardNo']{0};
        $money = (int)$params['money'];
        $array = [
            9 => [
                50 => ['14908', 1],
                100 => ['14909', 1],
                200 => ['14910', 1],
                500 => ['14911', 1],
                1000 => ['14912', 1],
            ],
            1 => [
                50 => ['14894', 1],
                100 => ['14895', 1],
                200 => ['14907', 1],
                500 => ['14880', 1],
                1000 => ['14881', 1],
            ]
        ];
        if (isset($array[$cardNo][$money]))
            return $array[$cardNo][$money];
        return null;
    }

    /**
     * create order and pay
     * @param array $payload
     * @return mixed|\Recharge\Supports\Collection
     */
    public function pay($payload = [])
    {
        $product = $this->products($payload);
        if (empty($product)) {
            return Response::response(['status' => -1,
                'msg' => '暂不支持该产品充值，联系客服！'
            ]);
        }

        $params = [
            'userId' => $this->app['config']->appId,
            'itemId' => $product[0],
            'uid' => $payload['cardNo'],
            'serialno' => $payload['orderId'],
            'dtCreate' => date('YmdHis'),
        ];

        $response = $this->requestApi('/unicomAync/buy.do?', $params);

        if ($response['status'] == 'success' && $response['code'] == '00') {
            return Response::response([
                'status' => 1,
                'paySn' => $response['bizOrderId'],
                'code' => Container::STATUS_PROCESSING //充值中
            ]);
        } else {
            return Response::response([
                'status' => -1,
                'msg' => "充值失败({$response['code']})，请联系客服手动充值",
            ]);
        }
    }

    /**
     * query order result
     * @param array $params
     * @return mixed|\Recharge\Supports\Collection
     */
    public function query($params = [])
    {
        $params = [
            'userId' => $this->app['config']->appId,
            'serialno' => $params['orderId'],
        ];

        $response = $this->requestApi('/unicomAync/queryBizOrder.do?', $params);

        if ($response['status'] == 'success' && $response['code'] == '00') {
            switch ($response['data']['status']) {
                case '0':
                case '1':
                case '4':
                case '9':
                    $ret = Response::response(['code' => Container::STATUS_PROCESSING]);
                    break;
                case '2':
                    $ret = Response::response(['code' => Container::STATUS_SUCCESS]);
                    break;
                default:
                    $ret = Response::response(['code' => Container::STATUS_FAIL]);
                    break;
            }

            if ($ret['code'] == Container::STATUS_SUCCESS || $ret['code'] == Container::STATUS_FAIL) {
                $call = $this->get($this->app['config']->retUrl, [
                    'userId' => $this->app['config']->appId,
                    'bizId' => $response['data']['bizId'], //业务编号
                    'ejId' => $response['data']['ejId'],   //平台方订单号
                    'downstreamSerialno' => $response['data']['serialno'], //商户订单号
                    'code' => $response['data']['status'], //2成功 3：失败
                    'sign' => md5($response['data']['id'] . $response['data']['serialno'] . $response['data']['id'] .
                        $response['data']['status'] . $this->app['config']->appId . $this->app['config']->appKey)
                ]);
                if (strtoupper($call) == 'OK') {
                    return Response::response([
                        'status' => 1,
                        'msg' => '充值成功'
                    ]);
                }
                return Response::response();
            }
        }

        return Response::response($response);
    }


    /**
     * query account rest
     * @return mixed|\Recharge\Supports\Collection
     */
    public function rest()
    {
        $params = [
            'userId' => $this->app['config']->appId,
        ];
        $response = $this->requestApi('/unicomAync/queryBalance.do?', $params);

        return Response::response($response);
    }


    /**
     * callback result deal...
     * @return mixed|\Recharge\Supports\Collection
     */
    public function callback()
    {
        $request = $this->app['request']->request;
        $params = [
            'userId' => $request->get('userId'),
            'bizId' => $request->get('bizId'),
            'ejId' => $request->get('ejId'),
            'downstreamSerialno' => $request->get('downstreamSerialno'),
            'status' => $request->get('code')
        ];
        if ($this->verify($request->all()) === false)
            return Response::response([
                'status' => -1,
                'msg' => '验签失败'
            ]);
        switch ($params['status']) {
            case '2':
                return Response::response([
                    'status' => 1,
                    'orderid' => $params['downstreamSerialno'],
                    'code' => Container::STATUS_SUCCESS,
                    'msg' => ''
                ]);
            case '3':
                return Response::response([
                    'status' => 1,
                    'orderid' => $params['downstreamSerialno'],
                    'code' => Container::STATUS_FAIL,
                    'msg' => urldecode($request->get('memo'))
                ]);
            default:
                return Response::response();
        }
    }


    /**
     * request an api
     * @param $endpoint
     * @param array $params
     * @return array|string
     */
    protected function requestApi($endpoint, $params = [])
    {
        try {
            return $this->get($endpoint, $this->sign($params), [
                'Accept:application/json;charset=UTF-8'
            ]);
        } catch (\Exception $e) {
            throw new RuntimeException($e->getMessage());
        }
    }

    /**
     * sign
     * @param $params
     * @return mixed
     */
    protected function sign($params)
    {
        $signKey = ['dtCreate', 'itemId', 'serialno', 'uid', 'userId'];
        $signStr = '';
        foreach ($signKey as $item) {
            if (isset($params[$item])) {
                $signStr .= $params[$item];
            }
        }
        $params['sign'] = md5($signStr . $this->app['config']->appKey);

        return $params;
    }

    /**
     * verify request params
     * @param $data
     * @return bool
     */
    protected function verify($data)
    {
        if (empty($data) || !isset($data['sign']))
            return false;
        ksort($data);
        reset($data);
        $strSign = '';
        foreach ($data as $key => $value) {
            if ($key != 'sign' && !is_null($value) && !empty($value)) {
                $strSign .= $value;
            }
        }

        $strSign .= $this->app['config']->appKey;

        if (strtolower($data['sign']) === md5($strSign))
            return true;
        return false;
    }
}