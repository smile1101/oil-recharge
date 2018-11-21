<?php

namespace Recharge\Recharge\Gxpay;

use Recharge\Exceptions\RuntimeException;
use Recharge\Kernel\BasicClient;
use Recharge\Recharge\Container\Container;
use Recharge\Supports\Response;

class Client extends BasicClient implements Container
{

    /**
     * http://boapi.gxcards.com/ testUrl
     * @var string
     */
    protected $baseUri = 'http://oapi.gxcards.com/';

    /**
     * get charge product ids
     * @param array $params
     * @return array|mixed|null
     */
    public function products($params = [])
    {
        $cardNo = (int)$params['cardNo']{0};
        $money = (int)$params['money'];
        $array = [
            9 => [
                500 => ['246', 1],
            ],
            1 => [
                100 => ['10015', 1],
                200 => ['10016', 1],
                500 => ['10017', 1],
                1000 => ['245', 1],
            ]
        ];
        if (isset($array[$cardNo][$money]))
            return $array[$cardNo][$money];
        return null;
        /*$response = $this->requestApi('goods_pool', [
            'partner' => $this->app->getConfig()['appId'],
            'version' => $this->app->getConfig()['version'],
        ]);

        return $response;*/
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
            return Response::response([
                'status' => -1,
                'msg' => '不支持该产品充值！'
            ]);
        }

        $params = [
            'partner' => $this->app['config']->appId,
            'version' => $this->app['config']->version,
            'bussinessParam' => json_encode([
                'goodsId' => $product[0],
                'userAccount' => $payload['cardNo'],
                'partnerNo' => $payload['orderId'],
            ])
        ];

        $response = $this->requestApi('buy_charge', $params);

        if (isset($response['status']) && $response['status'] == '200') {
            return Response::response([
                'status' => 1,
                'paySn' => $response['data']['orderNo'], //回调订单号
                'code' => Container::STATUS_PROCESSING, //充值中
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
     * query an result of orderId
     * callback result|retUrl
     * @param array $params
     * @return mixed|\Recharge\Supports\Collection
     */
    public function query($params = [])
    {
        $params = [
            'partner' => $this->app['config']->appId,
            'version' => $this->app['config']->version,
            'bussinessParam' => json_encode([
                'gxOrderNo' => $params['payId']
            ])
        ];

        $response = $this->requestApi('query_gas_order', $params);

        if (isset($data['status']) && $response['status'] == 200) {
            // -1 充值失败 6 充值成功 4 充值中
            $callback = $this->post($this->app['config']->retUrl, [
                'partnerId' => $this->app['config']->appId,
                'partnerNo' => $params['orderId'],
                'gxOrderNo' => $params['gxOrder'],
                'status' => $response['data']['status'],
                'sign' => md5(''
                    . 'partnerId' . $this->app['config']->appId
                    . 'partnerNo' . $params['orderId']
                    . 'gxOrderNo' . $params['gxOrder']
                    . 'status' . $response['data']['status']
                    . $this->app['config']->appKey
                )
            ]);
            if ($callback == 'OK') {
                return Response::response([
                    'status' => 1,
                    'code' => $response['data']['status'],
                    'msg' => '充值成功'
                ]);
            } else {
                return Response::response([
                    'status' => -1,
                    'code' => $response['data']['status'],
                    'msg' => '处理失败'
                ]);
            }
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
     * query account rest
     * @return mixed|\Recharge\Supports\Collection
     */
    public function rest()
    {
        $params = [
            'partner' => $this->app['config']->appId,
            'version' => $this->app['config']->version,
        ];

        $response = $this->requestApi('account_detail', $params);

        return Response::response($response);
    }

    /**
     * callback result
     *
     * @return mixed|\Recharge\Supports\Collection
     */
    public function callback()
    {
        $request = $this->app['request']->request;
        $response = [
            'partnerId' => $request->get('partnerId'),
            'partnerNo' => $request->get('partnerNo'),
            'gxOrderNo' => $request->get('gxOrderNo'),
            'goodsId' => $request->get('goodsId'),
            'status' => $request->get('status'),
            'sign' => $request->get('sign'),
        ];
        if ($this->verify($response))
            return Response::response([
                'status' => 1,
                'orderSn' => $response['partnerNo'],
                'code' => $response['status']
            ]);
        else
            return Response::response([
                'status' => -1,
                'msg' => '验签失败'
            ]);
    }


    /**
     * request an api
     * @param $endpoint
     * @param array $params
     * @return array|string
     */
    protected function requestApi($endpoint, $params = [])
    {
        $timestamp = floor(microtime(true) * 1000) . '';

        $params['timestamp'] = $timestamp;

        $endpoint = md5($endpoint) . '?t=' . urlencode(self::encrypt3Des($timestamp));

        try {
            return $this->post(
                $endpoint,
                self::encrypt3Des(json_encode($params)),
                [
                    'headers' => ['Content-Type: application/json; charset=UTF-8']
                ]
            );
        } catch (\Exception $e) {
            throw new RuntimeException($e->getMessage());
        }
    }

    /**
     * 3des encode
     * @param $data
     * @return string
     */
    protected function encrypt3Des($data)
    {
        return openssl_encrypt(
            $data,
            'des-ede3-cbc',
            $this->app['config']->appKey,
            0,
            $this->app['config']->appIv
        );
    }

    /**
     * sign request params
     * @param $params
     * @return bool
     */
    protected function verify($params)
    {
        if (empty($params) || !isset($params['sign']))
            return false;
        $strKey = '';
        foreach ($params as $key => $val) {
            if ($key != 'sign' && !empty($val) && !is_null($key))
                $strKey .= $key . $val;
        }
        $strKey .= $this->app['config']->appKey;
        if (md5($strKey) === $params['sign'])
            return true;
        return false;
    }
}