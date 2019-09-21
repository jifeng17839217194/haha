<?php
/**
 * Created by PhpStorm.
 * User: ZGL
 * Date: 2019/5/17
 * Time: 14:01
 */

namespace app\api\controller;


use think\Controller;
use think\Log;

class SmilePay extends Controller
{
    private static $instance;

    private static function getInstance()
    {
        vendor('alipay.AopSdk','.php');
        if(!self::$instance){
            self::$instance = new \AopClient();
            self::$instance->gatewayUrl = config('alipay_gatewayUrl');
            self::$instance->appId = config('alipay_app_id');
            self::$instance->rsaPrivateKey = config('alipay_rsaPrivateKey');
            self::$instance->alipayrsaPublicKey = config('alipay_alipayrsaPublicKey');
            self::$instance->apiVersion = '1.0';
            self::$instance->signType = 'RSA2';
            self::$instance->postCharset='UTF-8';
            self::$instance->format='json';
        }
    }

    /**
     * 刷脸初始化
     * @param string $content
     * @return \SimpleXMLElement|string
     */
    public static function initSmile(string $content)
    {
        self::getInstance();
        $request = new \ZolozAuthenticationCustomerSmilepayInitializeRequest();
        $request->setBizContent($content);

        return self::doResult($request);
    }

    /**
     * 脸ftoken查询消费接口
     * @param string $content
     * @return string
     */
    public static function getSmilePayInfo(string $content)
    {
        self::getInstance();
        $request = new \ZolozAuthenticationCustomerFtokenQueryRequest();
        $request->setBizContent($content);

        return self::doResult($request);
    }

    /**
     * 统一收单接口
     * @return string
     */
    public static function paySmile(string $content)
    {
        self::getInstance();
        $request = new \AlipayTradePayRequest();
        $request->setBizContent($content);

        $result = self::$instance->execute ( $request);

        $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";

        $resultCode = $result->$responseNode->code;
        Log::write(json_encode($result->$responseNode,JSON_UNESCAPED_UNICODE),'log');

        if(!empty($resultCode)&&$resultCode == 10000){
            return ['trade_no'=>$result->$responseNode->trade_no,'pay_amount'=>$result->$responseNode->buyer_pay_amount];
        } else {
            return [];
        }

    }

    /**
     * 处理接口以及返回数据
     * @param $request
     * @return string
     */
    public static function doResult($request)
    {
        $result = self::$instance->execute ( $request);

        $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";

        $resultCode = $result->$responseNode->code;
        Log::write(json_encode($result->$responseNode,JSON_UNESCAPED_UNICODE),'log');

        if(!empty($resultCode)&&$resultCode == 10000){
            return $result->$responseNode->result;
        } else {
            return '';
        }
    }

    /**
     * 支付宝退款
     * @author wzs
     * @param $pay_info
     * @param $shop_alipay_app_auth_token
     * @return array
     */
    public static function refund($pay_info,$shop_alipay_app_auth_token)
    {
        self::getInstance();
        $request = new \AlipayTradeRefundRequest ();
        $refund = ['out_trade_no'=>$pay_info['order_num'],'refund_amount'=>$pay_info['order_amount'],'refund_reason'=>$pay_info['reason']];
        if(!empty($pay_info['out_request_no'])){
            $refund['out_request_no'] = $pay_info['out_request_no'];
        }
        $request->setBizContent(json_encode($refund,JSON_UNESCAPED_UNICODE));

        $result = self::$instance->execute ( $request,null,$shop_alipay_app_auth_token);

        $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
        $resultCode = $result->$responseNode->code;
        Log::write(json_encode($result->$responseNode,JSON_UNESCAPED_UNICODE),'log');

        if(!empty($resultCode)&&$resultCode == 10000){
            return ['refund_out_trade_no'=>$result->$responseNode->out_trade_no,
                'refund_amount' =>$result->$responseNode->refund_fee,
                'refund_date_time'=>strtotime($result->$responseNode->gmt_refund_pay)];
        } else {
            return [];
        }
    }

    /**
     * 支付宝扫码支付
     * @param $content
     * @param $shop_alipay_app_auth_token
     * @return array
     */
    public static function barCodePay($content,$shop_alipay_app_auth_token)
    {
        self::getInstance();
        $request = new \AlipayTradePayRequest();
        $request->setBizContent($content);
        $result = self::$instance->execute ( $request,null,$shop_alipay_app_auth_token);

        $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
        $resultCode = $result->$responseNode->code;

        Log::write(json_encode($result->$responseNode,JSON_UNESCAPED_UNICODE),'log');

        if(!empty($resultCode) && $resultCode == 10000){
            return ['order_trade_no'=>$result->$responseNode->trade_no,
                'total_amount'=>$result->$responseNode->total_amount,
                'order_pay_time'=>strtotime($result->$responseNode->gmt_payment),
                'order_channel_id'=>1001];
        } else {
            return [];
        }
    }
}