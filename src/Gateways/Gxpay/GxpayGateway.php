<?php

namespace Recharge\Gateways\Gxpay;

use Recharge\Contracts\GatewayInterface;
use Recharge\Supports\Collection;
use Recharge\Supports\Config;
use Recharge\Supports\Response;
use Symfony\Component\HttpFoundation\Request;

class GxpayGateway implements GatewayInterface
{

    /**
     * @var Config
     */
    protected $config;

    const VERSION = 1.0;

    /**
     * 公告参数
     * @var array
     */
    protected $payload = [];

    public function __construct($config)
    {
        $this->config = $config;

        $this->payload = [
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
     * 商品池查询
     * @return Collection
     */
    public function goodsPool()
    {
        $this->payload['bussinessParam'] = json_encode([
            'type' => 1
        ]);

        return Support::requestNative('goods_poll',
            $this->payload,
            $this->config->get('desKey'),
            $this->config->get('desIv')
        );
    }

    /**
     * 查询余额
     * @param array $args
     * @return mixed
     */
    public function rest($args)
    {
        //$args = func_get_arg(0);

        return Support::requestNative('account_detail',
            $this->payload,
            $this->config->get('desKey'),
            $this->config->get('desIv')
        );
    }

    /**
     * 查询订单
     * @param $args
     * @return mixed
     */
    public function search($args)
    {
        $this->payload['bussinessParam'] = json_encode([
            'gxOrderNo' => $args['gxOrder']
        ]);
        $this->payload['orderId'] = $args['orderId'];

        return Support::callback('query_gas_order', $this->payload, $this->config);
    }

    /**
     * @param $args
     * @return mixed
     */
    public function pay($args)
    {
        $product = $this->getProducts([
            'cardNo' => $args['cardNo'], 'money' => $args['money']
        ]);
        if (empty($product)) {
            return Response::response([
                'status' => -1,
                'msg' => '不支持该产品充值！'
            ]);
        }

        $this->payload['bussinessParam'] = json_encode([
            'goodsId' => $product[0], //商品id
            'userAccount' => $args['cardNo'], //油卡号
            'partnerNo' => $args['orderId'],   //订单号
        ]);

        return Support::requestApi('buy_charge',
            $this->payload,
            $this->config->get('desKey'),
            $this->config->get('desIv')
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
                'orderSn' => $response['partnerId'],
                'code' => $response['status']
            ]);
        else
            return Response::response();
    }


    /**
     * 验签
     * @param $data
     * @return mixed
     */
    public function verify($data)
    {
        $args = func_get_arg(0);

        return Support::verify($args, $this->config);
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     */
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