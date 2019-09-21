<?php
/**
 * 微信支付中间层业务
 *  2017-9-12 10:02:57
 */
namespace app\common\model;

use EasyWeChat\Foundation\Application;
use think\Model;
use think\Url;
use think\Log;

class Wxpay extends Model
{
    protected $type = [
        //'sysconfig' => 'object',
        //'user_last_logintime' => 'timestamp',
    ];
    protected $autoWriteTimestamp = false;

    public function dosome()
    {
        // $options = [
        //     // ...
        //     'open_platform' => [
        //         'app_id'  => 'component-app-id',
        //         'secret'  => 'component-app-secret',
        //         'token'   => 'component-token',
        //         'aes_key' => 'component-aes-key',
        //     ],
        //     // ...
        // ];
        // $app          = new Application($options);
        // $openPlatform = $app->open_platform;

        // echo time();
    }

    public function request($requestName="", $paramData=[])
    {
        Url::root("/");
        //基础配置
        $options = $this->configOptions();

        switch ($requestName) {
            case 'order': //扫描支付(条形码)/刷卡支付
                $options['payment'] = array_merge($options['payment'], $paramData["payment"]);

                $app     = new \EasyWeChat\Foundation\Application($options);
                $payment = $app->payment;

                // $attributes = [
                //     'body'         => 'iPad mini 16G 白色',
                //     'detail'       => 'iPad mini 16G 白色',
                //     'out_trade_no' => 'test' . time(),
                //     'total_fee'    => 1, // 单位：分
                //     'auth_code'    => $order_auth_code,
                //     //'notify_url'   => 'http://xxx.com/order-notify', // 支付结果通知网址，如果不设置则会使用配置里的默认地址
                //     //'sub_openid'   => '当前用户的 openid', // 如果传入sub_openid, 请在实例化Application时, 同时传入$sub_app_id, $sub_merchant_id
                //     // ...
                // ];
                $attributes = $paramData["order"];
                $order      = new \EasyWeChat\Payment\Order($attributes);
                //dump($attributes);die();
                $result = $payment->pay($order);
                break;
            //"统一下单"//公众号支付、扫码支付、APP 支付
            case "prepare":
                $options['payment'] = array_merge($options['payment'], $paramData["payment"]);

                $app     = new \EasyWeChat\Foundation\Application($options);
                $payment = $app->payment;

                // $attributes = [
                //     'body'         => 'iPad mini 16G 白色',
                //     'detail'       => 'iPad mini 16G 白色',
                //     'out_trade_no' => 'test' . time(),
                //     'total_fee'    => 1, // 单位：分
                //     'auth_code'    => $order_auth_code,
                //     //'notify_url'   => 'http://xxx.com/order-notify', // 支付结果通知网址，如果不设置则会使用配置里的默认地址
                //     //'sub_openid'   => '当前用户的 openid', // 如果传入sub_openid, 请在实例化Application时, 同时传入$sub_app_id, $sub_merchant_id
                //     // ...
                // ];
                $attributes = $paramData["order"];
                $order      = new \EasyWeChat\Payment\Order($attributes);
                Log::record("-------------bof ATTR -------------".json_encode($attributes)."----------------eof ATTR --------------");
                Log::record("-------------bof ORDER -------------".json_encode($order)."----------------eof ORDER --------------");
                //dump($attributes);die();                
                $result = $payment->prepare($order);
                Log::record("-------------bof -------------".json_encode($result)."----------------eof --------------");
                break;
            case "WxpayTradeQueryRequest":

                $options['payment'] = array_merge($options['payment'], $paramData["payment"]);
                $app                = new \EasyWeChat\Foundation\Application($options);
                $payment            = $app->payment;

                $orderNo = $paramData["out_trade_no"]; //"商户系统内部的订单号（out_trade_no）";
                $result  = $payment->query($orderNo);

                break;

            //调用支付接口后请勿立即调用撤销订单API，建议支付后至少15s后再调用撤销订单接口。
            case "reverseOrder":

                $options['payment'] = array_merge($options['payment'], $paramData["payment"]);
                $app                = new \EasyWeChat\Foundation\Application($options);
                $payment            = $app->payment;

                $orderNo = $paramData["out_trade_no"]; //"商户系统内部的订单号（out_trade_no）";
                $result  = $payment->reverse($orderNo);

                break;
            //退款
            case "refund":

                $options['payment'] = array_merge($options['payment'], ["sub_mch_id" => $paramData["sub_mch_id"]]);
                $app                = new \EasyWeChat\Foundation\Application($options);
                $payment            = $app->payment;

                $orderNo = $paramData["out_trade_no"]; //"商户系统内部的订单号（out_trade_no）";
                $result  = $payment->refund($orderNo, $paramData["out_refund_no"], round($paramData["total_amount"]* 100,2), round($paramData["refund_amount"]* 100,2),$paramData["operator_id"]); // 总金额 100， 退款 80，操作员：1900000109

                break;

            //生成支付 JS 配置
            case "configForPayment":
                //$options['payment'] = array_merge($options['payment'], ["sub_mch_id"=>$paramData["sub_mch_id"]]);
                $app     = new \EasyWeChat\Foundation\Application($options);
                $payment = $app->payment;
                $json    = $payment->configForPayment($paramData["prepayId"]);
                $result  = $json;
                break;
            default:
                $app = new \EasyWeChat\Foundation\Application($options);
                return $app;
        }

        return $result;
    }

    //1.交易时间超过一年的订单无法提交退款；
    // 2、微信支付退款支持单笔交易分多次退款，多次退款需要提交原支付订单的商户订单号和设置不同的退款单号。申请退款总金额不能超过订单金额。 一笔退款失败后重新提交，请不要更换退款单号，请使用原商户退款单号。
    // 3、请求频率限制：150qps，即每秒钟正常的申请退款请求次数不超过150次
    //     错误或无效请求频率限制：6qps，即每秒钟异常或错误的退款申请请求不超过6次
    // 4、每个支付订单的部分退款次数不能超过50次
    //服务商模式下，退款接口需要单独申请权限，指引链接：http://kf.qq.com/faq/120911VrYVrA150929imAfuU.html

    /*refund([
    "out_trade_no"   => $orderOne->order_num,
    "sub_mch_id"=>$orderOne->order_shop_mch_id,
    "refund_amount"  => $cash,
    "out_refund_no" => time(), //标识一次退款请求，同一笔交易多次退款需要保证唯一，如需部分退款，则此参数必传。
    ]);*/
    public function refund($paramArray, $returnNiceRs = false)
    {
        $rsObject = $this->request("refund", $paramArray);
        if ($returnNiceRs) //要求原样返回
        {
            return $rsObject;
        }
        if ($rsObject->return_code == "SUCCESS") {
//通信成功
            if ($rsObject->result_code == "SUCCESS") {
                return ["code" => 1, "message" => "", "data" => json_decode(json_encode($rsObject, JSON_UNESCAPED_UNICODE), 1)];
            } else {
                $rs = ["code" => 0, "message" => $rsObject->err_code . $rsObject->err_code_des, "data" => ""];
                trace("微信退款异常" . json_encode($rs, JSON_UNESCAPED_UNICODE), "error");
                return $rs;
            }
        } else {
            trace("微信退款异常" . json_encode($rsObject, JSON_UNESCAPED_UNICODE), "error");
            return ["code" => 0, "message" => (isset($rsObject->return_msg) ? $rsObject->return_msg : "退款微信服务器内部"), "data" => ""];
        }
    }

    //调用支付接口后请勿立即调用撤销订单API，建议支付后至少15s后再调用撤销订单接口。
    //$paramArray=["out_trade_no" => $out_trade_no, "payment" => ['sub_merchant_id' => $orderOne->order_shop_mch_id]]
    public function reverseOrder($paramArray)
    {
        $rsObject = $this->request("reverseOrder", $paramArray);
        if ($rsObject->return_code == "SUCCESS") {
//通信成功
            if ($rsObject->result_code == "SUCCESS") {
                return ["code" => 1, "message" => "", "data" => json_decode(json_encode($rsObject, JSON_UNESCAPED_UNICODE), 1)];
            } else {
                $rs = ["code" => 0, "message" => $rsObject->err_code . $rsObject->err_code_des, "data" => ""];
                trace("微信退款异常" . json_encode($rs, JSON_UNESCAPED_UNICODE), "error");
                return $rs;
            }
        } else {
            trace("微信退款异常" . json_encode($rsObject, JSON_UNESCAPED_UNICODE), "error");
            return ["code" => 0, "message" => (isset($rsObject->return_msg) ? $rsObject->return_msg : "退款微信服务器内部"), "data" => ""];
        }
    }

    //配置选项
    public function configOptions()
    {
        return [
            /**
             * Debug 模式，bool 值：true/false
             *
             * 当值为 false 时，所有的日志都不会记录
             */
            'debug'   => true,
            'sandboxEnabled' => true,
            /**
             * 账号基本信息，请从微信公众平台/开放平台获取
             */
            'app_id'  => config("wxpay_app_id"), // AppID 服务商1441063102的
            'secret'  => config("wxpay_app_secret"), // AppSecret
            'token'   => 'your-token', // Token
            'sandbox' => true,
            'aes_key' => '', // EncodingAESKey，安全模式下请一定要填写！！！
            /**
             * 日志配置
             *
             * level: 日志级别, 可选为：
             *         debug/info/notice/warning/error/critical/alert/emergency
             * permission：日志文件权限(可选)，默认为null（若为null值,monolog会取0644）
             * file：日志文件位置(绝对路径!!!)，要求可写权限
             */
            'log'     => [
                'level'      => 'debug',
                'permission' => 0777,
                'file'       => LOG_PATH . date("Ym") . DS . 'easywechat.log',
            ],
            /**
             * OAuth 配置
             *
             * scopes：公众平台（snsapi_userinfo / snsapi_base），开放平台：snsapi_login
             * callback：OAuth授权完成后的回调页地址
             */
            'oauth'   => [
                'scopes'   => ['snsapi_userinfo'],
                'callback' => '/examples/oauth_callback.php',
            ],
            /**
             * 微信支付
             */
            'payment' => [
                'merchant_id' => config("wxpay_mch_id"), //主商户ID
                'key'         => config("wxpay_api_secret"), //理解为API密钥,在“微信支付”后台设置
                'cert_path'   => config("wxpay_cert_path"), // XXX: 绝对路径！！！！
                'key_path'    => config("wxpay_key_path"), // XXX: 绝对路径！！！！
                'notify_url'  => request()->domain() . url("api/notifyurl/index?channel=wxpay"), //并不是所有的有要的,写死格式（H5端是兼容url写法，不写死会有影响）
                // 'device_info'     => '013467007045764',
                // 'sub_app_id'      => '',                 
                // 'sub_merchant_id' => '',
                // ...
            ],
            /**
             * Guzzle 全局设置
             *
             * 更多请参考： http://docs.guzzlephp.org/en/latest/request-options.html
             */
            'guzzle'  => [
                'timeout' => 30.0, // 超时时间（秒） ,请求超时的时间 https://forum.easywechat.org/d/173--
                //'verify' => false, // 关掉 SSL 认证（强烈不建议！！！）
            ],
        ];
    }

}
