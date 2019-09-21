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
use think\Request;

class WxPay extends Controller
{
    public static function barcodePay($content,$sub_mch_id)
    {
        $config = array(
            'appid' => config('wxpay_app_id'),
            'mch_id' => config('wxpay_mch_id'),
            'key' => config('wxpay_api_secret'),
        );
        $unified = array(
            'appid' => $config['appid'],
            'sub_mch_id'=>$sub_mch_id,
            'body' => $content['subject'],
            'mch_id' => $config['mch_id'],
            'nonce_str' => self::nonce_str(),//随机字符串
            'out_trade_no' => $content['order_num'],
            'total_fee' => config('sx_site') == 1 ? 1 : $content['total_amount'] * 100,
            'auth_code'=>$content['auth_code'],
            'spbill_create_ip' => Request::instance()->ip()?:'127.0.0.1',//终端的ip
        );

        $unified['sign'] = self::getSign($unified, $config['key']);//签名

        $url = 'https://api.mch.weixin.qq.com/pay/micropay';
        $responseXml = Http::postRequest($url, Http::arrayToXml($unified));
        $unifiedOrder = simplexml_load_string($responseXml, 'SimpleXMLElement', LIBXML_NOCDATA);

        Log::write(json_encode($unifiedOrder,JSON_UNESCAPED_UNICODE),'log');

        //支付成功
        if ($unifiedOrder->return_code == 'SUCCESS' && $unifiedOrder->result_code == 'SUCCESS') {
            return ['order_trade_no'=>$unifiedOrder->transaction_id,
                'total_amount'=>($unifiedOrder->cash_fee)/100,
                'order_pay_time'=>strtotime($unifiedOrder->time_end),
                'order_channel_id'=>1002];
        }else if ($unifiedOrder->err_code == 'USERPAYING') {
            $get_res = self::getWxOrderPay($sub_mch_id,$content['order_num'],0);
            Log::write(json_encode($get_res,JSON_UNESCAPED_UNICODE),'log');
            if($get_res->trade_state == 'SUCCESS'){
              return ['order_trade_no'=>$get_res->transaction_id,
                    'total_amount'=>($get_res->cash_fee)/100,
                    'order_pay_time'=>strtotime($get_res->time_end),
                    'order_channel_id'=>1002];
            }
        }

        return [];
    }

    //随机32位字符串
    public static function nonce_str(){
        $result = '';
        $str = 'QWERTYUIOPASDFGHJKLZXVBNMqwertyuioplkjhgfdsamnbvcxz';
        for ($i=0;$i<32;$i++){
            $result .= $str[rand(0,48)];
        }
        return $result;
    }

    /**
     * 获取签名
     */
    public static function getSign($params, $key)
    {
        ksort($params, SORT_STRING);
        $unSignParaString = self::formatQueryParaMap($params, false);
        $signStr = strtoupper(md5($unSignParaString . "&key=" . $key));
        return $signStr;
    }

    protected static function formatQueryParaMap($paraMap, $urlEncode = false)
    {
        $buff = "";
        ksort($paraMap);
        foreach ($paraMap as $k => $v) {
            if (null != $v && "null" != $v) {
                if ($urlEncode) {
                    $v = urlencode($v);
                }
                $buff .= $k . "=" . $v . "&";
            }
        }
        $reqPar = '';
        if (strlen($buff) > 0) {
            $reqPar = substr($buff, 0, strlen($buff) - 1);
        }
        return $reqPar;
    }

    /**
     * 订单查询
     * @author wzs
     * @param $sub_mch_id
     * @param $order_num
     * @param $second
     * @return \SimpleXMLElement
     */
    protected static function getWxOrderPay($sub_mch_id,$order_num,$second)
    {
        $config = array(
            'appid' => config('wxpay_app_id'),
            'mch_id' => config('wxpay_mch_id'),
            'key' => config('wxpay_api_secret'),
        );
        $unified = array(
            'appid' => $config['appid'],
            'sub_mch_id'=>$sub_mch_id,
            'mch_id' => $config['mch_id'],
            'nonce_str' => self::nonce_str(),//随机字符串
            'out_trade_no' => $order_num,
        );

        $unified['sign'] = self::getSign($unified, $config['key']);//签名

        $url = 'https://api.mch.weixin.qq.com/pay/orderquery';
        $responseXml = Http::postRequest($url, Http::arrayToXml($unified));
        $unifiedOrder = simplexml_load_string($responseXml, 'SimpleXMLElement', LIBXML_NOCDATA);

        if($unifiedOrder->trade_state == 'USERPAYING' && $second <= 30){
            sleep(3);$second += 3;
            $unifiedOrder = self::getWxOrderPay($sub_mch_id,$order_num,$second);
        }

        return $unifiedOrder;
    }
}