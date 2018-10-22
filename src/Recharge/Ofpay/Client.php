<?php

namespace Recharge\Recharge\Ofpay;

use Recharge\Exceptions\RuntimeException;
use Recharge\Recharge\Base\BasicClient;
use Recharge\Recharge\Container\Container;
use Recharge\Supports\Response;

class Client extends BasicClient implements Container
{

    /**
     * http://A1403742.api2.ofpay.com product
     * http://apitest.ofpay.com dev
     * @var string
     */
    protected $baseUri = 'http://A1403742.api2.ofpay.com';

    /**
     * get recharge product ids
     * @param array $params
     * @return mixed|null
     */
    public function products($params = [])
    {
        $cardNo = (int)$params['cardNo']{0};
        $money = (int)$params['money'];
        $array = [
            9 => [
                50 => ['64349107', 1],
                100 => ['64349106', 1],
                200 => ['64349105', 1],
                500 => ['64349103', 1],
                1000 => ['64349101', 1],
            ],
            1 => [
                50 => ['64157005', 1],
                100 => ['64157004', 1],
                200 => ['64157003', 1],
                500 => ['64157002', 1],
                1000 => ['64157001', 1],
            ]
        ];
        if (isset($array[$cardNo][$money]))
            return $array[$cardNo][$money];
        return null;
    }

    /**
     * create an order and pay
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
            'cardid' => $product[0],
            'cardnum' => $product[1],
            'sporder_id' => $payload['orderId'],
            'sporder_time' => date('YmdHis'),
            'game_userid' => $payload['cardNo'],
            'chargeType' => $payload['cardNo']{0} == '9' ? 2 : 1,
            'ret_url' => $this->app['config']->retUrl,
            'version' => $this->app['config']->version,
        ];

        $response = $this->requestApi('/sinopec/onlineorder.do?', $params);

        if ((int)$response['retcode'] === 1) {
            return Response::response([
                'status' => 1,
                'paySn' => $response['orderid'],
                'code' => $response['game_state']
            ]);
        } else
            return Response::response([
                'status' => -1,
                'msg' => !empty($response['err_msg']) ? $response['err_msg'] : '充值失败，联系客户手动充值！'
            ]);
    }

    /**
     * query an result by orderId
     *
     * @param array $params
     * @return mixed|\Recharge\Supports\Collection
     */
    public function query($params = [])
    {
        $params = [
            'sporder_id' => $params['orderId'],
            'version' => $this->app['config']->version,
        ];

        $response = $this->requestApi('/queryOrderInfo.do?', $params);

        if ($response['retcode'] == '1') {
            if ($response['game_state'] == '1' || $response['game_state'] == '9') {
                $callback = $this->post($this->app['config']->retUrl, [
                    'sporder_id' => $response['sporder_id'],
                    'ret_code' => $response['game_state'],
                ]);

                if ($callback == 'true') {
                    return Response::response([
                        'status' => 1,
                        'msg' => '充值成功'
                    ]);
                }

                return Response::response([
                    'status' => -11,
                    'msg' => '处理失败'
                ]);
            }
            return Response::response([
                'status' => -1,
                'code' => $response['game_state'],
                $response
            ]);
        } else
            return Response::response($response);
    }

    /**
     * query account rest
     * @return mixed|\Recharge\Supports\Collection
     */
    public function rest()
    {
        $params = [
            'version' => $this->app['config']->version,
        ];

        return Response::response($this->requestApi('/newqueryuserinfo.do?', $params));
    }

    /**
     * callback result dealing
     * @return mixed|\Recharge\Supports\Collection
     */
    public function callback()
    {
        $request = $this->app['request']->request;
        $response = [
            'orderSn' => $request->get('sporder_id'),
            'code' => $request->get('ret_code'),
            'msg' => mb_convert_encoding($request->get('err_msg', ''), 'UTF-8', 'GBK')
        ];
        if (!$response['orderSn'] || !in_array($response['code'], [Container::STATUS_FAIL, Container::STATUS_SUCCESS])) {
            return Response::response();
        } else {
            $response['status'] = 1;
            return Response::response($response);
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
        if (!empty($params))
            $params = $this->sign($params);

        $endpoint = $endpoint . http_build_query($params);

        try {
            return $this->post($endpoint);
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
        $params['userid'] = $this->app['config']->appId;
        $params['userpws'] = $this->app['config']->appKey;
        $signKey = ['userid', 'userpws', 'cardid', 'cardnum', 'sporder_id', 'sporder_time', 'game_userid',
            'login_name', 'login_pwd', 'name', 'cert_type', 'cert_no', 'gas_card_no', 'email', 'phone_no', 'charge_type',
            'event_id', 'verification_code'];
        $signStr = '';
        foreach ($signKey as $item) {
            if (isset($params[$item])) {
                $signStr .= $params[$item];
            }
        }

        $params['md5_str'] = strtoupper(md5($signStr . $this->app['config']->appStr));
        return $params;
    }
}