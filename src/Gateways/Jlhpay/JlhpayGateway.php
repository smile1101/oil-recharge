<?php

namespace Recharge\Gateways\Jlhpay;

use Recharge\Contracts\GatewayInterface;
use Recharge\Supports\Config;
use Recharge\Supports\Response;
use Symfony\Component\HttpFoundation\Request;

class JlhpayGateway implements GatewayInterface
{

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
            'userId' => $this->config->get('userId'),
            'secret' => $this->config->get('secret'),
            'serialno' => $args['orderId'],
        ];

        return Support::callback('/unicomAync/queryBizOrder.do?', $params, $this->config);
    }

    /**
     * @param $args
     * @return mixed
     */
    public function pay($args)
    {
        if (empty($args)) {
            return Response::response(['status' => 0, 'msg' => '请传必要参数']);
        }
        $product = $this->getProducts([
            'cardNo' => $args['cardNo'],
            'money' => $args['money']
        ]);
        if (empty($product)) {
            return Response::response(['status' => -1,
                'msg' => '暂不支持该产品充值，联系客服！'
            ]);
        }

        $params = [
            'userId' => $this->config->get('userId'),
            'secret' => $this->config->get('secret'),
            'itemId' => $product[0],
            'uid' => $args['cardNo'],
            'serialno' => $args['orderId'],
            'dtCreate' => date('YmdHis'),
        ];
        
        return Support::requestApi('/unicomAync/buy.do?', $params);
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
            return Response::response([
                'status' => -1,
                'msg' => '验签失败'
            ]);
        }
        switch ($params['status']) {
            case '2':
                return Response::response([
                    'status' => 1,
                    'orderid' => $params['downstreamSerialno'],
                    'code' => GatewayInterface::STATUS_SUCCESS,
                    'msg' => ''
                ]);
            case '3':
                return Response::response([
                    'status' => 1,
                    'orderid' => $params['downstreamSerialno'],
                    'code' => GatewayInterface::STATUS_FAIL,
                    'msg' => urldecode($request->get('memo'))
                ]);
            default:
                return Response::response();
        }
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
            return Response::response([
                'status' => -1,
                'msg' => "Method:{$name} Not Exists"
            ]);

        return $name($arguments);
    }
}