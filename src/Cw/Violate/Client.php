<?php

namespace Recharge\Cw\Violate;

use Recharge\Kernel\BasicClient;
use Recharge\Supports\Response;

/**
 * violate charge
 * Class Client
 * @package Recharge\Cs\Violate
 */
class Client extends BasicClient
{

    protected $baseUri = 'http://112.74.179.0:8081/';

    /**
     * 查询预检测
     * @param array $params
     * @return \Recharge\Supports\Collection
     */
    public function queryCheck($params = [])
    {
        if (empty($params['carNumber']))
            return Response::response([
                'status' => -1,
                'msg' => '车牌号不能为空'
            ]);

        $data = [
            'appid' => $this->app['config']->appId,
            'carNumber' => $this->encodeAes($params['carNumber'])
        ];

        return $this->requestApi('violation/queryPreCheck', $data);
    }

    /**
     * 查询
     * @param array $params
     * @return \Recharge\Supports\Collection
     */
    public function query($params = [])
    {
        if (empty($params['carNumber']))
            return Response::response([
                'status' => -1,
                'msg' => '车牌号不能为空'
            ]);

        $data = [
            'appid' => $this->app['config']->appId,
            'carNumber' => $this->encodeAes($params['carNumber']),
            'vin' => !empty($params['vin']) ? $this->encodeAes($params['vin']) : '',
            'engine' => !empty($params['engine']) ? $this->encodeAes($params['engine']) : '',
            'spOrder' => !empty($params['spOrder']) ? $params['spOrder'] : ''
        ];

        return $this->requestApi('violation/query', $data);
    }

    /**
     * 缴费预检测
     * @param array $params
     * @return \Recharge\Supports\Collection
     */
    public function chargeCheck($params = [])
    {
        if (empty($params['uniqueCode']))
            return Response::response([
                'status' => -1,
                'msg' => '违章编码不能为空'
            ]);

        $data = [
            'appid' => $this->app['config']->appId,
            'uniqueCode' => $params['uniqueCode'],
        ];

        return $this->requestApi('violation/agencyPreCheck', $data);
    }

    /**
     * 缴费
     * @param array $params
     * @return \Recharge\Supports\Collection
     */
    public function charge($params = [])
    {

        if (empty($params['uniqueCode']))
            return Response::response([
                'status' => -1,
                'msg' => '违章编码不能为空'
            ]);

        if (empty($params['spOrder']))
            return Response::response([
                'status' => -1,
                'msg' => '商户订单号不能为空'
            ]);

        $data = [
            'appid' => $this->app['config']->appId,
            'uniqueCode' => $params['uniqueCode'],
            'contactUser' => isset($params['contactUser']) && !empty($params['contactUser']) ? $this->encodeAes($params['contactUser']) : '',
            'contactTel' => isset($params['contactTel']) && !empty($params['contactTel']) ? $this->encodeAes($params['contactTel']) : '',
            'vin' => isset($params['vin']) && !empty($params['vin']) ? $this->encodeAes($params['vin']) : '',
            'engine' => isset($params['engine']) && !empty($params['engine']) ? $this->encodeAes($params['engine']) : '',
            'driverLicenseNumber' => isset($params['driverLicenseNumber']) && !empty($params['driverLicenseNumber']) ? $this->encodeAes($params['driverLicenseNumber']) : '',
            'driverLicenseFileNumber' => isset($params['driverLicenseFileNumber']) && !empty($params['driverLicenseFileNumber']) ? $this->encodeAes($params['driverLicenseFileNumber']) : '',
            'driverLicenseCoreNumber' => isset($params['driverLicenseCoreNumber']) && !empty($params['driverLicenseCoreNumber']) ? $this->encodeAes($params['driverLicenseCoreNumber']) : '',
            'drivingLicenseImageFace' => isset($params['drivingLicenseImageFace']) && !empty($params['drivingLicenseImageFace']) ? base64_encode($php_errormsg['drivingLicenseImageFace']) : '',
            'driverLicenseName' => isset($params['driverLicenseName']) && !empty($params['driverLicenseName']) ? $this->encodeAes($params['driverLicenseName']) : '',
            'driverLicensePhone' => isset($params['driverLicensePhone']) && !empty($params['driverLicensePhone']) ? $this->encodeAes($params['driverLicensePhone']) : '',
            'driverLicenseImageFace' => isset($params['driverLicenseImageFace']) && !empty($params['driverLicenseImageFace']) ? base64_encode($params['driverLicenseImageFace']) : '',
            'idCardImageFace' => isset($params['idCardImageFace']) && !empty($params['idCardImageFace']) ? base64_encode($params['idCardImageFace']) : '',
            'drivingLicenseImageSide' => isset($params['drivingLicenseImageSide']) && !empty($params['drivingLicenseImageSide']) ? base64_encode($params['drivingLicenseImageSide']) : '',
            'spOrder' => $params['spOrder'],
            'retUrl' => !empty($params['retUrl']) ? $params['retUrl'] : ''
        ];

        return $this->requestApi('violation/agency', $data);
    }

    /**
     * 结果通知
     * @return \Recharge\Supports\Collection
     */
    public function notify()
    {
        $request = $this->app['request']->request->all();

        if ($request['signature'] === $this->sign($request))
            return Response::response([
                'status' => 1,
                'data' => $request
            ]);
        return Response::response([
            'status' => -1,
            'msg' => '签名失败'
        ]);
    }


    /**
     * request an api
     * @param $endpoint
     * @param $data
     * @return \Recharge\Supports\Collection
     */
    protected function requestApi($endpoint, $data)
    {
        $data['signature'] = $this->sign($data);

        $response = $this->post($endpoint, $data);

        if (is_string($response))
            $response = json_decode($response, true);
        if ($response['code'] == 1)
            return Response::response([
                'status' => 1,
                'data' => $response['data']
            ]);
        return Response::response([
            'status' => -1,
            'msg' => $response['message']
        ]);
    }


    /**
     * 签名
     * @param $data
     * @return string
     */
    protected function sign($data)
    {

        @ksort($data);

        $strVal = '';
        foreach ($data as $k => $v) {
            if (!empty($v) && strtolower($k) !== 'returl' && strtolower($k) !== 'signature')
                $strVal .= $v;
        }

        $strVal .= $this->app['config']->appKey;

        return md5($strVal);
    }

    /**
     * 关键字加密
     * @param $data
     * @return string
     */
    protected function encodeAes($data)
    {

        $charSet = mb_detect_encoding($data, "UTF-8, GBK") == 'UTF-8' ? 'UTF-8' : 'GBK';

        $aes = openssl_encrypt(mb_convert_encoding($data, 'UTF-8', $charSet),
            'aes-128-ecb',
            $this->app['config']->appStr
        );

        return $aes;
    }
}