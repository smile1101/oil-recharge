<?php

namespace Recharge\Gateways\Ofpay;


use Recharge\Contracts\GatewayInterface;
use Recharge\Supports\Collection;
use Recharge\Supports\Config;
use Recharge\Supports\Response;
use Symfony\Component\HttpFoundation\Request;

class FlowGateway implements GatewayInterface
{

    /**
     * @var Config
     */
    protected $config;

    const VERSION = '6.0';

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
        $phone = $args['cardNo'];
        $amount = $args['amount'];
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
     * 充值检测
     * @param array $args
     * @return Collection
     */
    public function rechargeCheck($args = [])
    {
        $product = $this->getProducts($args);
        if (!$product) {
            return Response::response([
                'status' => -1,
                'msg' => '暂不支持的充值金额'
            ]);
        }
        $params = [
            'userid' => $this->config->get('userId'),
            'userpws' => $this->config->get('userPws'),
            'phoneno' => $args['cardNo'],
            'perValue' => $product[1],
            'flowValue' => $product[0],
            'range' => 2, // 1:省内、2:全国
            'effectStartTime' => 1, // 1:当日
            'effectTime' => 1, // 1.当月有效
            'version' => self::VERSION
        ];
        $response = Support::requestOther('', $params);
        if ($response) {
            if ($response['retcode'] == '1') {
                return Response::response([
                    'status' => 1,
                    'msg' => $product[0]
                ]);
            }
            return Response::response([
                'status' => -1,
                'msg' => '运营商地区维护，暂不能充值'
            ]);
        }
        return Response::response([
            'status' => -1,
            'msg' => '暂不能充值'
        ]);
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

        return Support::callback('/queryOrderInfo.do?',
            $params,
            $this->config->get('strKey'),
            $this->config->get('retUrl')
        );
    }

    /**
     * create a order and exec pay
     * @param array $payload
     * @return mixed
     */
    public function pay($payload)
    {
        $product = $this->getProducts(['cardNo' => $payload['cardNo'], 'amount' => $payload['amount']]);
        if (!$product) {
            return Response::response([
                'status' => -1,
                'msg' => '暂不支持的充值金额'
            ]);
        }

        $params = [
            'userid' => $this->config->get('userId'),
            'userpws' => $this->config->get('userPws'),
            'phoneno' => $payload['cardNo'],
            'perValue' => $product[1],
            'flowValue' => $product[0],
            'range' => 2,
            'effectStartTime' => 1,
            'effectTime' => 1,
            'netType' => '4G',
            'sporderId' => $payload['orderId'],
            'retUrl' => $this->config->get('retUrl'),
            'version' => self::VERSION,
        ];

        return Support::requestApi('', $params, $this->config->get('strKey'));
    }

    /**
     * 回调处理
     * @return mixed
     */
    public function callback()
    {
        $request = Request::createFromGlobals()->request;
        $response = [
            'orderid' => $request->get('sporder_id'),
            'code' => $request->get('ret_code'),
            'msg' => mb_convert_encoding($request->get('err_msg'), 'UTF-8', 'GBK')
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
        $request = Request::createFromGlobals();
        $data = !empty($data) && is_array($data) ?? $request->request->all();

        return true;
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