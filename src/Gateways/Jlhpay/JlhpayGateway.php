<?php

namespace Recharge\Gateways\Jlhpay;

use Recharge\Contracts\GatewayInterface;
use Recharge\Supports\Collection;
use Recharge\Supports\Config;
use Recharge\Supports\Response;
use Recharge\Traits\HttpRequestTraits;
use Symfony\Component\HttpFoundation\Request;

class JlhpayGateway implements GatewayInterface
{

    use HttpRequestTraits;
    /**
     * @var Collection
     */
    public $response;

    /**
     * @var Config
     */
    protected $config;

    protected $gateway = 'http://182.92.157.25:8160';

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * 获取产品列表
     * @param array $args
     * @return mixed
     */
    public function getProducts($args = [])
    {
        $cardNo = $args['cardNo'];
        $money = $args['money'];
        if ($cardNo{0} === '9') { // 中石油加油卡
            if ($money == 1000) {
                return ['14912', 1];
            } else if ($money == 500) {
                return ['14911', 1];
            } else if ($money == 200) {
                return ['14910', 1];
            } else if ($money == 100) {
                return ['14909', 1];
            } else if ($money == 50) {
                return ['14908', 1];
            }
        } else if (strpos($cardNo, '100011') === 0) {// 中石化加油卡
            if ($money == 1000) {
                return ['14881', 1];
            } else if ($money == 500) {
                return ['14880', 1];
            } else if ($money == 200) {
                return ['14907', 1];
            } else if ($money == 100) {
                return ['14895', 1];
            } else if ($money == 50) {
                return ['14894', 1];
            }
        }
        return null;
    }

    /**
     * 查询余额
     * @param array $args
     * @return mixed
     */
    public function rest(...$args)
    {
        $args = func_get_arg(0);
        return $this;
    }

    /**
     * 查询订单
     * @param $args
     * @return mixed
     */
    public function search(...$args)
    {
        $args = func_get_arg(0);
        $params = [
            'userId' => $this->config->get('userId'),
            'secret' => $this->config->get('secret'),
            'serialno' => $args['orderId'],
        ];
        $params = Support::sign($params);
        $url = $this->gateway . '/unicomAync/queryBizOrder.do?';
        $response = $this->get($url, $params, [
            CURLOPT_HTTPHEADER => [
                'Accept:application/json;charset=UTF-8'
            ]
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
                $this->response = Response::response($this->get($this->config->get('retUrl'), [
                    'userId' => $this->config->get('userId'),
                    'bizId' => $response['data']['id'],
                    'ejId' => $response['data']['id'],
                    'downstreamSerialno' => $response['data']['serialno'],
                    'code' => $response['data']['status'],
                    'sign' => md5($response['data']['id'] . $response['data']['serialno'] . $response['data']['id'] .
                        $response['data']['status'] . $this->config->get('userId') . $this->config->get('secret'))
                ]));
            }
        } else {
            $this->response = Response::response();
        }
        return $this;
    }

    /**
     * @param array $payload
     * @return mixed
     */
    public function pay(...$payload)
    {
        $args = func_get_arg(0);
        if (empty($args)) {
            $this->response = Response::response(['status' => 0, 'msg' => '请传必要参数']);
            return $this;
        }
        $product = $this->getProducts([
            'cardNo' => $args['cardNo'],
            'money' => $args['money']
        ]);
        if (empty($product)) {
            $this->response = Response::response(['status' => -1,
                'msg' => '暂不支持该产品充值，联系客服！'
            ]);
            return $this;
        }
        $params = [
            'userId' => $this->config->get('userId'),
            'secret' => $this->config->get('secret'),
            'itemId' => $product[0],
            'uid' => $args['cardNo'],
            'serialno' => $args['orderId'],
            'dtCreate' => date('YmdHis')
        ];
        $params = Support::sign($params);
        $url = $this->gateway . '/unicomAync/buy.do?';
        $response = $this->get($url, $params, [
            CURLOPT_HTTPHEADER => [
                'Accept:application/json;charset=UTF-8'
            ]
        ]);
        if ($response['status'] == 'success' && $response['code'] == '00') {
            $this->response = Response::response([
                'status' => 1,
                'paySn' => $response['bizOrderId'],
                'amount' => $args['money'],
                'code' => GatewayInterface::STATUS_PROCESSING //充值中
            ]);
        } else {
            $this->response = Response::response([
                'status' => -1,
                'msg' => "充值失败({$response['code']})，请联系客服手动充值"
            ]);
        }
        return $this;
    }

    /**
     * 回调处理
     * @return mixed
     */
    public function callback()
    {
        $request = Request::createFromGlobals()->request;
        $params = [
            'userId' => $request->get('userId'),
            'bizId' => $request->get('bizId'),
            'ejId' => $request->get('ejId'),
            'downstreamSerialno' => $request->get('downstreamSerialno'),
            'status' => $request->get('code')
        ];
        $sign = strtolower($request->get('sign'));
        if ($sign != md5($params['bizId'] . $params['downstreamSerialno'] . $params['ejId'] . $params['status'] . $params['userId'] . $this->config->get('secret'))) {
            exit;
        }
        switch ($params['status']) {
            case '2':
                $this->response = Response::response([
                    'status' => 1,
                    'orderid' => $params['downstreamSerialno'],
                    'code' => GatewayInterface::STATUS_SUCCESS,
                    'msg' => ''
                ]);
                break;
            case '3':
                $this->response = Response::response([
                    'status' => 1,
                    'orderid' => $params['downstreamSerialno'],
                    'code' => GatewayInterface::STATUS_FAIL,
                    'msg' => urldecode($request->get('memo'))
                ]);
                break;
            default:
                $this->response = Response::response();
                exit;
        }
        return $this;
    }

    /**
     * 验签
     * @param $data
     * @return mixed
     */
    public function verify($data)
    {
        // TODO: Implement verify() method.
    }

    public function __call($name, $arguments)
    {
        if (!method_exists($this, $name))
            $this->response = Response::response([
                'status' => -1,
                'msg' => "Method:{$name} Not Exists"
            ]);

        return $this;
    }
}