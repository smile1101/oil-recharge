<?php

namespace Recharge\Sf\Orders;

use Recharge\Sf\Base\BasicClient;
use Recharge\Supports\Response;

class Client extends BasicClient
{

    /**
     * 定义路由
     * @var string
     */
    protected $baseUri = 'http://bsp-oisp.sf-express.com/bsp-oisp/sfexpressService';

    /**
     * 顺丰下单接口
     * @param array $order
     * @return \Recharge\Supports\Collection
     */
    public function create(array $order)
    {
        $data = $this->toXml([
            'Order' => [
                [
                    'orderid' => $order['orderId'],
                    'is_gen_bill_no' => 1, //返回运单号

                    'j_company' => $order['fromCompany'],
                    'j_contact' => $order['fromContact'],
                    'j_tel' => $order['fromTel'],
                    'j_province' => $order['fromProvince'],
                    'j_city' => $order['fromCity'],
                    'j_county' => $order['fromCounty'],
                    'j_address' => $order['fromAddress'],

                    'd_company' => $order['toCompany'],
                    'd_contact' => $order['toContact'],
                    'd_tel' => $order['toTel'],
                    'd_province' => $order['toProvince'],
                    'd_city' => $order['toCity'],
                    'd_county' => $order['toCounty'],
                    'd_address' => $order['toAddress'],

                    'custid' => isset($order['custId']) ? $order['custId'] : '', //顺丰月结卡号
                    'pay_method' => isset($order['payMethod']) ? $order['payMethod'] : 1, //1:寄方付 2:收方付 3:第三方付
                    'express_type' => isset($order['expressType']) ? $order['expressType'] : 2, //顺丰特惠
                    'sendstarttime' => isset($order['reserveTime']) ? $order['reserveTime'] : date('Y-m-d H:i:s'),
                    'is_docall' => 2
                ]
            ],
            'Cargo' => [
                [
                    'name' => '身份证复印件正反面',
                    'count' => '1',
                    'unit' => '份'
                ], [
                    'name' => '交强险保单副本原件',
                    'count' => '1',
                    'unit' => '份'
                ], [
                    'name' => '车辆登记证书复印件',
                    'count' => '1',
                    'unit' => '份'
                ], [
                    'name' => '行驶证原件和复印件',
                    'count' => '1',
                    'unit' => '份'
                ]
            ]
        ], 'OrderService');

        $response = $this->verifyToCurl($data, 'OrderService');

        if ($response->Head == 'OK') {

            $return = $response->Body['OrderResponse']['@attributes'];
            $return['rls_info'] = $response->Body['OrderResponse']['rls_info']['@attributes'];
            $return['rls_info']['rls_detail'] = $response->Body['OrderResponse']['rls_info']['rls_detail']['@attributes'];
            return Response::response([
                'status' => 1,
                'data' => $return
            ]);
        } else {
            return Response::response([
                'status' => -1,
                'msg' => $response->ERROR
            ]);
        }
    }

    /**
     * 订单查询接口
     * @param array $order
     * @return \Recharge\Supports\Collection
     */
    public function query(array $order)
    {
        $xml = $this->toXml([
            'OrderSearch' => [[
                'orderid' => $order['orderId']
            ]]
        ], 'OrderSearchService');

        $response = $this->verifyToCurl($xml, 'OrderSearchService');

        if ($response->Head == 'OK') {
            return Response::response([
                'status' => 1,
                'data' => $response->Body['OrderResponse']['@attributes']
            ]);
        } else {
            return Response::response([
                'status' => -1,
                'msg' => $response->ERROR
            ]);
        }
    }

    /**
     * 取消订单接口
     * @param array $order
     * @return \Recharge\Supports\Collection
     */
    public function cancel(array $order = [])
    {
        $xml = $this->toXml([
            'OrderConfirm' => [[
                'orderid' => $order['orderId'],
                'dealtype' => isset($order['dealType']) ? $order['dealType'] : 2,
            ]]
        ], 'OrderConfirmService');

        $response = $this->verifyToCurl($xml, 'OrderConfirmService');

        if ($response->Head == 'OK') {

            $return = $response->Body['OrderConfirmResponse']['@attributes'];
            return Response::response([
                'status' => 1,
                'data' => $return
            ]);
        } else {
            return Response::response([
                'status' => -1,
                'msg' => $response->ERROR
            ]);
        }
    }

    /**
     * 路由查询接口
     * @param array $params
     * @return \Recharge\Supports\Collection
     */
    public function routeQuery(array $params = [])
    {
        $xml = $this->toXml([
            'RouteRequest' => [[
                'method_type' => 1,
                'tracking_type' => isset($params['trackingType']) ? $params['trackingType'] : 2,
                'tracking_number' => $params['orderId']
            ]]
        ], 'RouteService');

        $response = $this->verifyToCurl($xml, 'RouteService');

        if ($response->Head == 'OK') {

            $return = $response->Body['RouteResponse']['@attributes'];
            foreach ($response->Body['RouteResponse']['Route'] as $k => $v) {
                $return['route'][$k] = $v['@attributes'];
            }
            return Response::response([
                'status' => 1,
                'data' => $return
            ]);
        } else {
            return Response::response([
                'status' => -1,
                'msg' => $response->ERROR
            ]);
        }
    }


    /**
     * 路由接收接口
     * @return \Recharge\Supports\Collection
     */
    public function routeAccept()
    {
        $data = urldecode(file_get_contents($this->app['config']->log . '/../test.xml'));

        $response = fromXml($data);

        if ($response) {
            if (isset($this->app['config']->log) && !empty($this->app['config']->log)) {
                @zlw_logger($response, $this->app['config']->log, 'RoutePushService');
            }
            return Response::response([
                'status' => 1,
                'data' => $response['Body']['WaybillRoute']['@attributes']
            ]);
        }
        return Response::response();
    }

    /**
     * 路由推送接口
     * @param bool $status
     */
    public function routeSend($status = true)
    {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $requestEl = $dom->createElement('Response');
        $requestEl->setAttribute('service', 'RoutePushService');
        $requestEl->setAttribute('lang', 'zh-CN');

        if ($status) {
            $headEl = $dom->createElement('Head');
            $headEl->appendChild($dom->createTextNode('OK'));
            $requestEl->appendChild($headEl);
            $dom->appendChild($requestEl);
            echo $dom->saveXML();
            die;
        } else {
            $headEl = $dom->createElement('Head');
            $headEl->appendChild($dom->createTextNode('ERR'));
            $headEl = $dom->createElement('ERROR');
            $headEl->appendChild($requestEl->setAttribute('code', '40001'));
            $headEl->appendChild($dom->createTextNode('系统发生数据错误或运行时异常'));
            $requestEl->appendChild($headEl);
            $dom->appendChild($requestEl);
            echo $dom->saveXML();
            die;
        }
    }


    /**
     * 签名并发起请求
     * @param $xml
     * @param $service
     * @return \Recharge\Supports\Collection
     */
    protected function verifyToCurl($xml, $service = null)
    {
        $sign = $this->sign($xml);

        $options = array(
            'http' => array(
                'method' => 'POST',
                'header' => 'Content-type:application/x-www-form-urlencoded;charset=utf-8',
                'content' => http_build_query([
                    'xml' => $xml,
                    'verifyCode' => $sign
                ]),
                'timeout' => 15 * 60 // 超时时间（单位:s）
            )
        );
        $context = stream_context_create($options);

        $result = file_get_contents($this->baseUri, false, $context);

        $response = fromXml($result);

        if (isset($this->app['config']->log) && !empty($this->app['config']->log)) {
            @zlw_logger($response, $this->app['config']->log, $service);
        }

        return Response::response($response);
    }

    /**
     * 签名
     * @param $params
     * @return string
     */
    protected function sign($params)
    {
        $md5 = md5(($params . $this->app['config']->appKey), true);

        $sign = base64_encode($md5);

        return $sign;
    }

    /**
     * 数组转换为xml
     * @param array $data
     * @param null $service
     * @return string
     */
    protected function toXml(array $data = [], $service = null)
    {
        if (!is_array($data))
            return null;

        $dom = new \DOMDocument('1.0', 'UTF-8');

        $requestEl = $dom->createElement('Request');
        $requestEl->setAttribute('service', $service);
        $requestEl->setAttribute('lang', 'zh-CN');
        $headEl = $dom->createElement('Head');
        $headEl->appendChild($dom->createTextNode($this->app['config']->appId));
        $requestEl->appendChild($headEl);
        $bodyEl = $dom->createElement('Body');
        foreach ($data as $tag => $elements) {
            foreach ($elements as $element) {
                $el = $dom->createElement($tag);
                foreach ($element as $k => $v) {
                    $el->setAttribute($k, $v);
                }
                $bodyEl->appendChild($el);
            }
        }
        $requestEl->appendChild($bodyEl);
        $dom->appendChild($requestEl);
        return $dom->saveXML();
    }
}