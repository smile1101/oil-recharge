<?php

namespace Recharge\Gateways\Ofpay;

use Recharge\Contracts\GatewayInterface;
use Recharge\Supports\Collection;
use Recharge\Supports\Config;
use Recharge\Supports\Response;
use Recharge\Traits\HttpRequestTraits;
use Symfony\Component\HttpFoundation\Request;

/**
 * 欧飞直充
 * @package Recharge\Oil\Gateways\Ofpay
 */
class OfpayGateway implements GatewayInterface
{

    use HttpRequestTraits;

    /**
     * 固定版本
     */
    const VERSION = 6.0;

    /**
     * @var Config
     */
    protected $config;

    /**
     * 网关
     * @var string
     */
    protected $gateway = 'http://apitest.ofpay.com';

    /**
     * 响应
     * @var Collection
     */
    public $response;

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
        if ($cardNo{0} === '9') {
            // 中石油加油卡
            if ($money == 1000) {
                return ['64349101', 1]; //直充
            } else if ($money == 500) {
                return ['64349103', 1]; //直充
            } else if ($money == 200) {
                return ['64349105', 1]; //直充
            } else if ($money == 100) {
                return ['64349106', 1]; //直充
            } else if ($money == 50) {
                return ['64349107', 1]; //直充
            }
        } else if (strpos($cardNo, '100011') === 0) {
            // 中石化加油卡
            if ($money == 1000) {
                return ['64157001', 1];
            } else if ($money == 500) {
                return ['64157002', 1];
            } else if ($money == 200) {
                return ['64157003', 1];
            } else if ($money == 100) {
                return ['64157004', 1];
            } else if ($money == 50) {
                return ['64157005', 1];
            }
        }
        return null;
    }

    /**
     * @param array $payload
     * @return mixed
     */
    public function pay(...$payload)
    {
        $payload = func_get_arg(0);
        if (empty($payload)) {
            $this->response = Response::response([
                'status' => -1,
                'msg' => '请传必要参数！'
            ]);
            return $this;
        }
        $product = $this->getProducts([
            'cardNo' => $payload['cardNo'],
            'money' => $payload['money']
        ]);

        if (empty($product)) {
            $this->response = Response::response(['status' => -1,
                'msg' => '暂不支持该产品充值，联系客服！'
            ]);
            return $this;
        }
        $params = [
            'userid' => $this->config->get('userid'),
            'userpws' => $this->config->get('userpws'),
            'cardid' => $product[0],
            'cardnum' => $product[1],
            'sporder_id' => $payload['orderId'],
            'sporder_time' => date('YmdHis'),
            'game_userid' => $payload['cardNo'],
            'chargeType' => $payload['cardNo']{0} == '9' ? 2 : 1,
            'ret_url' => $this->config->get('retUrl'),
            'version' => self::VERSION
        ];
        $params = Support::sign($params, $this->config->get('str_key'));
        $endpoint = $this->gateway . '/sinopec/onlineorder.do?' . http_build_query($params);
        $response = $this->post($endpoint, []);
        if ((int)$response['retcode'] === 1) {
            $this->response = Response::response([
                'status' => 1,
                'paySn' => $response['orderId'],
                'amount' => $payload['money'],
                'code' => $response['game_state'] //充值中
            ]);
            return $this;
        }
        $this->response = Response::response([
            'status' => -1,
            'msg' => !empty($response['err_msg']) ? $response['err_msg'] : '充值失败，联系客户手动充值！'
        ]);
        return $this;
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
     * @param $args ['orderId', 'retUrl']
     * @return mixed
     */
    public function search(...$args)
    {
        $args = func_get_arg(0);
        $params = [
            'userid' => $this->config->get('userid'),
            'userpws' => $this->config->get('userpws'),
            'sporder_id' => $args['orderId'],
            'version' => self::VERSION
        ];
        $params = Support::sign($params, $this->config->get('str_key'));
        $endpoint = $this->gateway . '/queryOrderInfo.do?' . http_build_query($params);
        $response = $this->post($endpoint, []);
        if ($response['retcode'] == '1') {
            $ret = ['status' => $response['game_state']];
            if ($response['game_state'] == '1' || $response['game_state'] == '9') {
                //回调处理
                $this->response = Response::response($this->post($this->config->get('retUrl'), [
                    'sporder_id' => $response['sporder_id'],
                    'ret_code' => $response['game_state'],
                ]));
                return $this;
            }
            $this->response =  Response::response($ret);
            return $this;
        } else {
            $this->response =  Response::response($response);
            return $this;
        }
    }

    /**
     * 回调处理
     * @return mixed
     */
    public function callback()
    {
        $request = Request::createFromGlobals()->request;
        $response = [
            // sporder_id 商户订单号
            'orderSn' => $request->get('sporder_id'),
            'code' => $request->get('ret_code'),
            'msg' => mb_convert_encoding($request->get('err_msg', ''), 'UTF-8', 'GBK')
        ];
        if (!$response['orderSn'] || !in_array($response['code'], [GatewayInterface::STATUS_FAIL, GatewayInterface::STATUS_SUCCESS])) {
            $this->response = Response::response();
        } else {
            $response['status'] = 1;
            $this->response = Response::response($response);
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