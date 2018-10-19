<?php

namespace Recharge\Recharge\OFlow;


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
     * get products
     * @param array $params
     * @return array|mixed|null
     */
    public function products($params = [])
    {
        $phone = $params['cardNo'];
        $amount = $params['money'];
        $belong = substr($phone, 0, 3);
        switch ($belong) {
            // 联通
            case '130':
            case '131':
            case '132':
            case '145':
            case '155':
            case '156':
            case '185':
            case '186':
            case '176':
            case '175':
                if ($amount == '3') {
                    return ['20M', 3];
                } elseif ($amount == '4') {
                    return ['30M', 4];
                } elseif ($amount == '6') {
                    return ['50M', 6];
                } elseif ($amount == '10') {
                    return ['100M', 10];
                } elseif ($amount == '15') {
                    return ['200M', 15];
                } elseif ($amount == '20') {
                    return ['300M', 20];
                } elseif ($amount == '30') {
                    return ['500M', 30];
                } elseif ($amount == '50') {
                    return ['1G', 50];
                }
                break;
            // 移动
            case '134':
            case '135':
            case '136':
            case '137':
            case '138':
            case '139':
            case '147':
            case '150':
            case '151':
            case '152':
            case '157':
            case '158':
            case '159':
            case '182':
            case '183':
            case '187':
            case '188':
            case '178':
                if ($amount == '3') {
                    return ['10M', 3];
                } elseif ($amount == '5') {
                    return ['30M', 5];
                } elseif ($amount == '10') {
                    return ['100M', 10];
                } elseif ($amount == '20') {
                    return ['300M', 20];
                } elseif ($amount == '30') {
                    return ['500M', 30];
                } elseif ($amount == '50') {
                    return ['1G', 50];
                } elseif ($amount == '70') {
                    return ['2G', 70];
                } elseif ($amount == '100') {
                    return ['3G', 100];
                } elseif ($amount == '200') {
                    return ['6G', 180];
                } elseif ($amount == '300') {
                    return ['11G', 280];
                }
                break;
            // 电信
            case '133':
            case '153':
            case '180':
            case '181':
            case '189':
            case '177':
            case '173':
            case '149':
                if ($amount == '1') {
                    return ['5M', 1];
                } elseif ($amount == '2') {
                    return ['10M', 2];
                } elseif ($amount == '5') {
                    return ['30M', 5];
                } elseif ($amount == '7') {
                    return ['50M', 7];
                } elseif ($amount == '10') {
                    return ['100M', 10];
                } elseif ($amount == '15') {
                    return ['200M', 15];
                } elseif ($amount == '30') {
                    return ['500M', 30];
                } elseif ($amount == '50') {
                    return ['1G', 50];
                }
                break;
        }
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
        if (!$product) {
            return Response::response([
                'status' => -1,
                'msg' => '暂不支持的给产品充值，请联系客服'
            ]);
        }

        $params = [
            'phoneno' => $payload['cardNo'],
            'perValue' => $product[1],
            'flowValue' => $product[0],
            'range' => 2,
            'effectStartTime' => 1,
            'effectTime' => 1,
            'netType' => '4G',
            'sporderId' => $payload['orderId'],
            'retUrl' => $this->app['config']->retUrl,
            'version' => $this->app['config']->version,
        ];

        $response = $this->requestApi('/flowOrder.do?', $params);

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
        $request = $this->app['result']->request;
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
        $signKey = ['userid', 'userpws', 'phoneno', 'perValue', 'flowValue', 'range', 'effectStartTime', 'effectTime', 'netType', 'sporderId'];
        $signStr = '';
        foreach ($signKey as $item) {
            if (isset($params[$item])) {
                $signStr .= $params[$item];
            }
        }

        $params['md5Str'] = strtoupper(md5($signStr . $this->app['config']->appStr));
        return $params;
    }

}