<?php

namespace Recharge\Gateways\Ofpay;

use Recharge\Contracts\GatewayInterface;
use Recharge\Supports\Collection;
use Recharge\Supports\Config;
use Recharge\Supports\Response;
use Symfony\Component\HttpFoundation\Request;

/**
 * 欧飞加油直充
 * @package Recharge\Oil\Gateways\Ofpay
 */
class OfpayGateway implements GatewayInterface
{

    /**
     * 固定版本
     */
    const VERSION = 6.0;

    /**
     * @var Config
     */
    protected $config;


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
     * exec a request
     * @param mixed $payload
     * @return mixed|Collection
     */
    public function pay($payload)
    {
        if (empty($payload)) {
            return Response::response([
                'status' => -1,
                'msg' => '请传必要参数！'
            ]);
        }
        $product = $this->getProducts([
            'cardNo' => $payload['cardNo'],
            'money' => $payload['money']
        ]);

        if (empty($product)) {
            return Response::response(['status' => -1,
                'msg' => '暂不支持该产品充值，联系客服！'
            ]);
        }
        $params = [
            'userid' => $this->config->get('userId'),
            'userpws' => $this->config->get('userPws'),
            'cardid' => $product[0],
            'cardnum' => $product[1],
            'sporder_id' => $payload['orderId'],
            'sporder_time' => date('YmdHis'),
            'game_userid' => $payload['cardNo'],
            'chargeType' => $payload['cardNo']{0} == '9' ? 2 : 1,
            'ret_url' => $this->config->get('retUrl'),
            'version' => self::VERSION,
        ];

        return Support::requestApi('/sinopec/onlineorder.do?',
            $params,
            $this->config->get('strKey')
        );
    }

    /**
     * 查询余额
     * @return mixed
     */
    public function rest()
    {
        $params = [
            'userid' => $this->config->get('userId'),
            'userpws' => $this->config->get('userPws'),
            'version' => self::VERSION
        ];

        return Support::requestOther('/newqueryuserinfo.do?', $params);
    }

    /**
     * 查询订单
     * @param $args ['orderId', 'retUrl']
     * @return mixed
     */
    public function search($args)
    {
        $params = [
            'userid' => $this->config->get('userId'),
            'userpws' => $this->config->get('userPws'),
            'sporder_id' => $args['orderId'],
            'version' => self::VERSION
        ];

        return Support::callback('/queryOrderInfo.do?',
            $params,
            $this->config->get('strKey'),
            $this->config->get('retUrl')
        );
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
            return Response::response();
        } else {
            $response['status'] = 1;
            return Response::response($response);
        }
    }

    /**
     * 验签
     * @param $data
     * @return mixed
     */
    public function verify($data)
    {
        $request = Request::createFromGlobals();
        $data = !empty($data) && is_array($data) ?? $request->request->all();

        return true;
    }

    public function __call($name, $arguments)
    {
        if (!method_exists($this, $name))
            return Response::response([
                'status' => -1,
                'msg' => "Method:{$name} Not Exists"
            ]);

        return $name($arguments);
    }
}