<?php
/**
 * 支付宝的中间层业务
 *  2017-9-6 13:20:47
 */
namespace app\common\model;

use think\Model;
use think\Url;

class Alipay extends Model
{
    protected $aop;
    protected $aopSHH;
    protected $type = [
        //'sysconfig' => 'object',
        //'user_last_logintime' => 'timestamp',
    ];
    protected $autoWriteTimestamp = false;
    /**
     * 统一API请求模块
     * 支付宝SDK，再次封装
     * @param  string $value [description]
     * @return [type]        [description]
     */
    public function request($requestName, $BizContentArray, $app_auth_token = null, $authToken = null)
    {
        $aop = $this->requestBase();
        //trace("request", "debug");
        //trace($requestName, "debug");
        //trace($aop, "debug");
        $request = new $requestName();
        $request->setBizContent(json_encode($BizContentArray, JSON_UNESCAPED_UNICODE));
        Url::root("/"); //并不是所有的有要的,写死格式（H5端是兼容url写法，不写死会有影响）
        $setNotifyUrl = request()->domain() . url("api/notifyurl/index?channel=alipay");
        $request->setNotifyUrl($setNotifyUrl);
        //trace('支付宝接口里的app_auth_token：'.json_encode($app_auth_token));
        //trace('支付宝接口里的authToken：'.json_encode($authToken));
        switch ($requestName) {
            case 'AlipayTradeWapPayRequest':
                $resultCodeArray = $aop->pageExecute($request); //返回的是表单
                break;
            default:
                //echo $app_auth_token;
                //exit();
                $result = $aop->execute($request, $authToken, $app_auth_token);
                //$responseNode    = str_replace(".", "_", $request->getApiMethodName()) . "_response";
                //$resultCodeArray = json_decode(json_encode($result->$responseNode, JSON_UNESCAPED_UNICODE), 1);
                return $this->alipayXmlToArray($result, $request);
                break;
        }
        //trace($resultCodeArray, "debug");
        return $resultCodeArray;
    }

    public function requestSHH($requestName, $BizContentArray, $app_auth_token = null, $authToken = null)
    {

        return $this->request($requestName, $BizContentArray, $app_auth_token, $authToken); //只用生活号的 APP 调用
        /*$aop = $this->requestBase(1);
        //trace("requestSHH", "debug");
        //trace($requestName, "debug");
        //trace($aop, "debug");
        $request = new $requestName();
        $request->setBizContent(json_encode($BizContentArray, JSON_UNESCAPED_UNICODE));
        Url::root("/"); //并不是所有的有要的,写死格式（H5端是兼容url写法，不写死会有影响）
        $setNotifyUrl = request()->domain() . url("api/notifyurl/index?channel=alipay");
        $request->setNotifyUrl($setNotifyUrl);
        switch ($requestName) {
            case 'AlipayTradeWapPayRequest':
                $resultCodeArray = $aop->pageExecute($request); //返回的是表单
                break;
            default:
                //echo $app_auth_token;
                //exit();
                //$result = $aop->execute($request);
                //trace('支付宝接口里生活号的authToken：'.$authToken);
                //trace('支付宝接口里生活号的app_auth_token：'.$app_auth_token);
                $result = $aop->execute($request, $authToken, $app_auth_token);
                //$responseNode    = str_replace(".", "_", $request->getApiMethodName()) . "_response";
                //$resultCodeArray = json_decode(json_encode($result->$responseNode, JSON_UNESCAPED_UNICODE), 1);
                return $this->alipayXmlToArray($result, $request);
                break;
        }
        //trace($resultCodeArray, "debug");
        return $resultCodeArray;*/
    }

    //基础
    public function requestBase($is_ssh = 0)
    {
        //https://docs.open.alipay.com/54/103419
        //AlipayClient的实现类都是线程安全的，所以没有必要每次API请求都新建一个AlipayClient实现类；
        //
        if (($is_ssh == 1 ? $this->aopSHH : $this->aop)) {
            //trace("is_ssh(cache):".$is_ssh,"debug");
            return ($is_ssh == 1 ? $this->aopSHH : $this->aop);
        } else {
            defined("AOP_SDK_WORK_DIR") or define("AOP_SDK_WORK_DIR", RUNTIME_PATH); //or 防止重复定义
            import('Alipay.AopSdk');
            //$is_ssh?import('Alipayparking.AopSdk'):import('Alipay.AopSdk');

            $aop                     = new \AopClient;
            $aop->gatewayUrl         = config("alipay_gatewayUrl");
            //$aop->appId              = $is_ssh ? config("alipay_shh_app_id") : config("alipay_app_id");
            $aop->appId              = config("alipay_app_id");
            $aop->rsaPrivateKey      = config("alipay_rsaPrivateKey");
            $aop->alipayrsaPublicKey = config("alipay_alipayrsaPublicKey");
            $aop->apiVersion         = '1.0';
            $aop->signType           = 'RSA2';
            $aop->postCharset        = 'UTF-8';
            $aop->format             = 'json';
            if ($is_ssh == 1) {
                $this->aopSHH = $aop;
            } else {
                $this->aop = $aop;
            }
            return $aop;
        }
    }
    //撤销订单API
    //https://docs.open.alipay.com/api_1/alipay.trade.cancel/
    //支付交易返回失败或支付系统超时，调用该接口撤销交易。如果此订单用户支付失败，支付宝系统会将此订单关闭；如果用户支付成功，支付宝系统会将此订单资金退还给用户。 注意：只有发生支付系统超时或者支付结果未知时可调用撤销，其他正常支付的单如需实现相同功能请调用申请退款API。提交支付交易后调用【查询订单API】，没有明确的支付结果再调用【撤销订单API】。
    //$paramArray=["out_trade_no" => $out_trade_no,"order_shop_id"=>$order_shop_id]
    public function cancelOrder($paramArray)
    {
        $data["out_trade_no"]       = $paramArray["out_trade_no"];
        $shop_alipay_app_auth_token = model("shop")->where(["shop_id" => $paramArray["order_shop_id"]])->value("shop_alipay_app_auth_token");
        $resultCodeArray            = $this->request("AlipayTradeCancelRequest", $data, $shop_alipay_app_auth_token);
        if ($resultCodeArray["code"] == 10000) {
            return ["code" => 1, "message" => [], "data" => []];
        } else {
            return ["code" => 0, "message" => $resultCodeArray["sub_msg"], "data" => $resultCodeArray];
        }
    }
    /**
     * [统一收单交易关闭接口]
     * 检查当前交易的状态是不是等待买家付付款，只有等待买家付款状态下才能发起交易关闭。
     * https://docs.open.alipay.com/api_1/alipay.trade.close/
     * @param  [type] $paramArray=["out_trade_no" => $out_trade_no,"order_shop_id"=>$order_shop_id]
     * @return [type]             [description]
     */
    public function closeOrder($paramArray)
    {
        $data["out_trade_no"]       = $paramArray["out_trade_no"];
        $shop_alipay_app_auth_token = model("shop")->where(["shop_id" => $paramArray["order_shop_id"]])->value("shop_alipay_app_auth_token");
        $resultCodeArray            = $this->request("AlipayTradeCloseRequest", $data, $shop_alipay_app_auth_token);
        if ($resultCodeArray["code"] == 10000) {
            return ["code" => 1, "message" => [], "data" => []];
        } else {
            return ["code" => 0, "message" => $resultCodeArray["sub_msg"], "data" => $resultCodeArray];
        }
    }
    public function alipayXmlToArray($result, $request)
    {
        $responseNode    = str_replace(".", "_", $request->getApiMethodName()) . "_response";
        $resultCodeArray = json_decode(json_encode($result->$responseNode, JSON_UNESCAPED_UNICODE), 1);
        return $resultCodeArray;
    }
}
