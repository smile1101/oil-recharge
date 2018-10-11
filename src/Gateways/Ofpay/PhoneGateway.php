<?php

namespace Recharge\Gateways\Ofpay;

use Recharge\Contracts\GatewayInterface;
use Recharge\Supports\Collection;
use Recharge\Supports\Config;
use Recharge\Supports\Response;
use Symfony\Component\HttpFoundation\Request;

/**
 * 话费充值
 * Class PhoneGateway
 * @package Recharge\Gateways\Ofpay
 */
class PhoneGateway implements GatewayInterface
{

    const VERSION = 6.0;

    /**
     * @var Config
     */
    protected $config;


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
        switch ($args['money']) {
            case '1':
            case '2':
            case '5':
            case '10':
            case '20':
            case '30':
            case '50':
            case '100':
            case '200':
            case '300':
            case '500':
                return [$args['money'], 1];
        }
        return null;
    }

    /**
     * 查询余额
     * @param array $args
     * @return mixed
     */
    public function rest($args)
    {

    }

    /**
     * 查询订单
     * @param $args
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
        return Support::callback('/queryOrderInfo.do?', $params, $this->config->get('strKey'), $this->config->get('retUrl'));
    }

    /**
     * create a order of pay
     * @param mixed ...$payload
     * @return Collection
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
            'cardid' => '140101',
            'cardnum' => $product[0],
            'sporder_id' => $payload['orderId'],
            'sporder_time' => date('YmdHis'),
            'game_userid' => $payload['cardNo'],
            'ret_url' => $this->config->get('retUrl'),
            'version' => self::VERSION,
        ];
        return Support::requestApi('/onlineorder.do?', $params, $this->config->get('strKey'));
    }

    /**
     * 根据手机号和面值查询商品信息
     * @param $args
     * @return Collection
     */
    public function reserve($args)
    {
        $product = $this->getProducts([
            'money' => $args['money']
        ]);
        $params = [
            'userid' => $this->config->get('userId'),
            'userpws' => $this->config->get('userPws'),
            'phoneno' => $args['cardNo'],
            'pervalue' => $product[0],
            'version' => self::VERSION
        ];

        return Support::requestOther('/telquery.do?', $params);
    }

    /**
     * get callback request && deal
     * @return mixed|Collection
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
        if (!$response['orderSn'] || !in_array($response['code'], [GatewayInterface::STATUS_FAIL, GatewayInterface::STATUS_SUCCESS]))
            return Response::response();
        else
            $response['status'] = 1;
        return Response::response($response);

    }

    /**
     * 验签
     * @param $data
     * @return mixed
     */
    public function verify($data)
    {

    }

    /**
     * method verify
     * @param $name
     * @param $args
     * @return Collection
     */
    public function __call($name, $args)
    {
        if (!method_exists($this, $name))
            return Response::response([
                'status' => -1,
                'msg' => "Method:{$name} Not Exists"
            ]);

        return $name($args);
    }
}