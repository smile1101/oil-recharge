<?php

namespace Recharge\Gateways\Gxpay;

use Recharge\Contracts\GatewayInterface;
use Recharge\Supports\Collection;
use Recharge\Supports\Config;
use Recharge\Supports\Response;
use Recharge\Traits\HttpRequestTraits;
use Symfony\Component\HttpFoundation\Request;

class GxpayGateway implements GatewayInterface
{
    use HttpRequestTraits;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Collection
     */
    protected $response;

    const VERSION = 1.0;

    /**
     * 当前时间戳[毫秒]
     * @var string
     */
    protected $timestamp;

    protected $gateway = 'http://oapi.gxcards.com/';

    /**
     * 公告参数
     * @var array
     */
    protected $payload = [];

    public function __construct($config)
    {
        $this->config = $config;

        $this->timestamp = floor(microtime(true) * 1000) . '';
        $this->payload = [
            'timestamp' => $this->timestamp,
            'partner' => $this->config->get('partner'),
            'version' => self::VERSION,
        ];
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
        if (strpos($cardNo, '100011') === 0) {
            // 中石化加油卡
            if ($money == 1000) {
                return ['245', 1];
            } else if ($money == 500) {
                return ['10017', 1];
            } else if ($money == 200) {
                return ['10016', 1];
            } else if ($money == 100) {
                return ['10015', 1];
            }
        } elseif ($cardNo{0} === '9') {
            //中石油
            if ($money == 500) {
                //全国中石油加油卡直充500元
                return ['246', 1];
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
        $timestamp = $this->timestamp;
        $url = $this->gateway .= md5('account_detail') . '?t=' . urlencode(Support::encrypt3Des($timestamp, $this->config->get('des_key'), $this->config->get('des_iv')));
        try {
            $response = $this->post($url, Support::encrypt3Des(json_encode($this->payload), $this->config->get('des_key'), $this->config->get('des_iv')), [
                'headers' => ['Content-Type: application/json; charset=UTF-8']
            ]);
            $this->response = Response::response([
                'status' => 1,
                $response
            ]);
        } catch (\Exception $e) {
            $this->response = Response::response([
                'status' => -1,
                'msg' => '查询失败！'
            ]);
        }
        return $this;
    }

    /**
     * 查询订单
     * @param $args
     * @return mixed
     */
    public function orders(...$args)
    {
        $args = func_get_arg(0);

        $timestamp = $this->timestamp;
        $this->payload['bussinessParam'] = json_encode([
            'gxOrderNo' => $args['gxOrder']
        ]);
        $url = $this->gateway .= md5('query_gas_order') . '?t=' . urlencode(Support::encrypt3Des($timestamp, $this->config->get('des_key'), $this->config->get('des_iv')));
        $response = $this->post($url, Support::encrypt3Des(json_encode($this->payload), $this->config->get('des_key'), $this->config->get('des_iv')), [
            'headers' => ['Content-Type: application/json; charset=UTF-8']
        ]);
        if (isset($data['status']) && $response['status'] == 200) {
            // -1 充值失败 6 充值成功 4 充值中
            $status = $response['data']['status'] == -1 ? 1 : ($response['data']['status'] == 6 ? 6 : 0);
            //回调处理
            $this->response = Response::response($this->post($this->config->get('retUrl'), [
                'partnerId' => $args['orderId'],
                'partnerNo' => $this->config->get('partner'),
                'gxOrderNo' => $args['gxOrder'],
                'status' => $status,
                'sign' => md5('partnerId' . $args['orderId']
                    . 'partnerNo' . $this->config->get('partner')
                    . 'gxOrderNo' . $args['gxOrder']
                    . 'status' . $status . $this->config->get('des_key'))
            ]));
        } else {
            $this->response = [
                'status' => -1,
                'code' => $response['status'],
                'msg' => $response['statusText'],
                'remark' => '订单信息查询失败'
            ];
        }
        return $this;
    }

    /**
     * @param array $payload
     * @return mixed
     */
    public function make(...$payload)
    {
        $args = func_get_arg(0);
        $product = $this->getProducts([
            'cardNo' => $args['cardNo'], 'money' => $args['money']
        ]);
        if (empty($product)) {
            $this->response = Response::response([
                'status' => -1,
                'msg' => '不支持该产品充值！'
            ]);
            return $this;
        }
        $timestamp = $this->timestamp;
        $this->payload['bussinessParam'] = json_encode([
            'goodsId' => $product[0], //商品id
            'userAccount' => $args['cardNo'], //油卡号
            'partnerNo' => $args['orderId'],   //订单号
        ]);
        $url = $this->gateway .= md5('buy_charge') . '?t=' . urlencode(Support::encrypt3Des($timestamp, $this->config->get('des_key'), $this->config->get('des_iv')));

        $response = $this->post($url, Support::encrypt3Des(json_encode($this->payload), $this->config->get('des_key'), $this->config->get('des_iv')), [
            'headers' => ['Content-Type: application/json; charset=UTF-8']
        ]);
        if (isset($response['status']) && $response['status'] == '200') {
            //$status = $response['data']['status'] == -1 ? 9 : ($response['data']['status'] == 6 ? 1 : 0);
            $this->response = [
                'status' => 1,
                'pay_sn' => $response['data']['orderNo'], //回调订单号
                'amount' => $args['money'],
                'code' => GatewayInterface::STATUS_PROCESSING, //充值中
                'msg' => $response['statusText'],
            ];
        } else {
            $this->response = [
                'status' => -1,
                'code' => $response['status'],
                'msg' => $response['statusText'],
                'remark' => "充值失败({$response['status']})，请联系客服手动充值"
            ];
        }
        return $this;
    }

    /**
     * 回调处理
     * @return mixed
     */
    public function callback()
    {
        $request = new Request();
        $response = [
            'partnerId' => $request->get('partnerId'),
            'partnerNo' => $request->get('partnerNo'),
            'gxOrderNo' => $request->get('gxOrderNo'),
            'goodsId' => $request->get('goodsId'),
            'status' => $request->get('status'),
            'sign' => $request->get('sign'),
        ];
        if ($this->verify($response))
            $this->response = Response::response([
                'status' => 1,
                'orderSn' => $response['partnerId'],
                'code' => $response['status']
            ]);
        else
            $this->response = Response::response();
        return $this;
    }

    /**
     * @return Collection
     */
    public function commit()
    {
        return $this->response;
    }

    /**
     * 验签
     * @param $data
     * @return mixed
     */
    public function verify($data)
    {
        $args = func_get_arg(0);
        $appKey = $this->config->get('des_key');
        $sign = $data['sign'];
        unset($data['sign']);
        $strKey = '';
        foreach ($args as $key => $val) {
            $strKey .= $key . $val;
        }
        $strKey .= $appKey;
        if (md5($strKey) === $sign)
            return true;
        return false;
    }

    /**
     * 可购买充值商品
     * @return $this
     */
    public function goodsPool()
    {
        $timestamp = $this->timestamp;
        $this->payload['bussinessParam'] = json_encode([
            'type' => 1
        ]);
        $url = $this->gateway .= md5('query_gas_order') . '?t=' . urlencode(Support::encrypt3Des($timestamp, $this->config->get('des_key'), $this->config->get('des_iv')));
        $response = $this->post($url, Support::encrypt3Des(json_encode($this->payload), $this->config->get('des_key'), $this->config->get('des_iv')), [
            'headers' => ['Content-Type: application/json; charset=UTF-8']
        ]);
        if (isset($response['status']) && $response['status'] == 200) {
            $this->response = Response::response($response['data']);
        } else {
            $this->response = Response::response();
        }

        return $this;
    }
}