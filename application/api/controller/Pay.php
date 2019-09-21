<?php
namespace app\api\controller;
class Pay extends Apibase
{
    public function _initialize()
    {
        parent::_initialize();
    }
    /**
     * 扫描枪、APP条码支付；统一收单
     * @param  string  $user_id   [description]
     * @param  integer $total_amount    [description]
     * @param  string  $subject   [description]
     * @param  integer $auth_code  [description]
     * @param  string  $version   [description]
     * @param  string  $sign      [description]
     * @param  string  $time      [description]
     * @return [type]             [description]
     */
    public function barpay()
    {
        $user_id        = input("user_id");
        $total_amount   = input("total_amount", 0);
        $subject        = input("subject");
        $auth_code      = input("auth_code");
        $version        = input("version");
        $sign           = input("sign");
        $time           = input("time");
        $sale_order_num = input("sale_order_num", ""); //第三方销售系统的订单号
        $create_where   = input("create_where", "pc");
        $total_amount = trim($total_amount);
        $this->verifyPostDataHelper($user_id);
        $auth_code2 = "code" . $auth_code; //下面正则判断需要转成字符串
        //判断微信or支付宝
        if (preg_match("/^code(10|11|12|13|14|15){1}[0-9]{16}$/", $auth_code2)) //微信 https://pay.weixin.qq.com/wiki/doc/api/micropay.php?chapter=9_10&index=1
        {
            $channel = "face_wxpay";
        } else if (preg_match("/^code(25|26|27|28|29|30){1}[0-9]{14,22}$/", $auth_code2)) //支付宝将会在2017年9月底对支付宝的用户付款码做升级处理。付款码将由原来的28开头扩充到25-30开头，长度由原来的16-18位扩充到16-24位
        {
            $channel = "face_alipay";
        } else //未知
        {
            trace("-10001未知付款码:" . $auth_code, "debug");
            return ["code" => 0, "message" => "-10001未知付款码" . $auth_code, "data" => $auth_code];
        }
        $rs = model("order")->createOrder(["channel" => $channel, "user_id" => $user_id, "total_amount" => $total_amount, "subject" => $subject, "auth_code" => $auth_code, "create_where" => $create_where, "sale_order_num" => $sale_order_num]);
        if ($rs["code"] == 0) {
            return $rs;
        } else {
            $payrs = model("pay")->dopay($rs["data"]["order_id"]);
            if ($payrs["data"]["trade_status"] == 100) {
                $payrs["data"]["printhtml"] = model("printorder")->printTmp($payrs["data"]["order_num"]);
            }
            return $payrs;
        }
    }
    //订单查询
    public function tradequeryrequest()
    {
        $order_num       = input("order_num");
        $user_id         = input("user_id");
        $returnpricedata = input("returnpricedata", 0);
        $this->verifyPostDataHelper($user_id);
        $rs = model("pay")->tradeQueryFontRequest($order_num);
        if ($rs["data"]["trade_status"] == 100 && $returnpricedata) {
            $rs["data"]["printhtml"] = model("printorder")->printTmp($rs["data"]["order_num"]);
        }
        return $rs;
    }
    /**
     * [getSoundFile 获取文字转声音的文件]
     * @param  [number] $cash_num [金额]
     * @return [type]           [description]
     */
    public function getsoundfile()
    {
        $cash_num = input("cash_num");
        $user_id  = input("user_id");
        $this->verifyPostDataHelper($user_id);
        $rs = model("baidu")->getcashsoundfile($cash_num);
        return $rs;
    }
    //订单退款
    //$refund_fee 为零时是全款退款
    public function dorefundrequest()
    {
        $order_num  = input("order_num");
        $refund_fee = input("refund_fee", 0);
        $user_id    = input("user_id");
        $password   = input("password", "", null);
        $refund_fee = trim($refund_fee);
        if ($refund_fee) {
            $refund_fee = floatval($refund_fee);
        }
        $userOne = $this->verifyPostDataHelper($user_id);
        if (!$order_num) {
            return ["code" => 0, "message" => "订单号错误", "data" => ""];
        }
        $pay = model("pay");
        //退款权限检测
        $rsRefundBefaultCheck = $pay->refundBefaultCheck($order_num, $user_id, $password);
        if ($rsRefundBefaultCheck["code"] == 0) {
            return $rsRefundBefaultCheck;
        }
        //__退款权限检测
        return $pay->doRefundRequest($order_num, $refund_fee, $user_id);
    }
    /**
     * jsapi支付；统一收单(H5端)
     * @param  string  $user_id   [description]
     * @param  integer $total_amount    [description]
     * @return [type]             [description]
     */
    public function jsapipayrequest()
    {
        $user_id        = input("user_id");
        $sale_order_num = input("sale_order_num", ""); //第三方销售系统的订单号
        $create_where   = input("create_where", "guestscan");
        //trace('传递的数据get:'.json_encode($_GET));
        //trace('传递的数据post:'.json_encode($_POST));
        //trace('sale_order_num:'.$sale_order_num);
        if (!$user_id) {
            return ["code" => 0, "message" => "操作员参数丢失100201", "data" => ""];
        }
        $rscheckuser = model("user")->checkuseractive($user_id);
        if ($rscheckuser["code"] == 1) {
            $userOne = $rscheckuser["data"]["userOne"];
        } else {
            return $rscheckuser;
        }
        $order_guest_brief = msubstr(input("guest_brief", ""), 0, 100); //留言备注
        $total_amount = input("total_amount", 0, "floatval"); //收款金额
        //
        if($create_where=="parking")//做价格校正（即获取最新的价格）
        {
            $get_price_rs = model("parking_record")->getPrice(["parking_record_id"=>$sale_order_num,"car_number"=>input("car_number",""),"user_id"=>input("user_id","")]);
            if($get_price_rs["code"]==1)
            {
                if($get_price_rs["data"]["parking_record_pay_state"]==100)
                {
                    return ["code" => 0, "message" => input("car_number","")."停车费已经支付过", "data" => ""];
                }
                $total_amount = $get_price_rs["data"]["total_amount"];//从数据里拿价格
            }
            else
            {
                return $get_price_rs;
            }
        }
        /*包月支付*/
        if($create_where=='parking_month'){
            $get_price_rs=model("member_car_record")->getPrice(['record_id'=>$sale_order_num,'record_car_number_plate'=>input('car_number','')]);
            if($get_price_rs["code"]==1)
            {
                // if($get_price_rs["data"]["parking_record_pay_state"]==100)
                // {
                //     return ["code" => 0, "message" => input("car_number","")."停车费已经支付过", "data" => ""];
                // }
                $total_amount = $get_price_rs["data"]["total_amount"];//从数据里拿价格
            }
            else
            {
                return $get_price_rs;
            }
        }
        if ($total_amount <= 0) {
            return ["code" => 0, "message" => "付款金额必需大于0", "data" => ""];
        }
        $buyer_id = input("buyer_id"); //
        //$this->verifyPostDataHelper($user_id);
        $userAgent = strtolower(request()->header("user-agent"));
        //trace("浏览器：".$userAgent);
        //判断微信or支付宝
        if (preg_match("/micromessenger/", $userAgent)) //微信
        {
            $channel = "jsapi_wxpay";
        } else if (preg_match("/alipayclient/", $userAgent)) //支付宝
        {
            $channel = "jsapi_alipay";
        } else //未知
        {
            //$this->error("未知的浏览器");
            $channel = "jsapi_alipay";
        }
        $subject = input("subject", "支付(" . $userOne->store_name . ")");
        $rs = model("order")->createOrder(["channel" => $channel, "buyer_id" => $buyer_id, "user_id" => $user_id, "total_amount" => $total_amount, "subject" => $subject, "guest_brief" => $order_guest_brief, "product_code" => ($channel == "jsapi_alipay" ? "QUICK_WAP_WAY" : ""), "create_where" => $create_where, "sale_order_num" => $sale_order_num]);
        return $rs;
    }

    /**
     * appjsapi支付；手机端统一收单(H5端)
     * @param  string  $user_id   [description]
     * @param  integer $total_amount    [description]
     * @return [type]             [description]
     */
    public function appjsapipayrequest()
    {
        $user_id        = input("user_id");
        $sale_order_num = input("sale_order_num", ""); //第三方销售系统的订单号
        $create_where   = input("create_where", "guestscan");
        //trace('传递的数据get:'.json_encode($_GET));
        //trace('传递的数据post:'.json_encode($_POST));
        //trace('sale_order_num:'.$sale_order_num);
        if (!$user_id) {
            return ["code" => 0, "message" => "操作员参数丢失100201", "data" => ""];
        }
        $rscheckuser = model("user")->checkuseractive($user_id);
        if ($rscheckuser["code"] == 1) {
            $userOne = $rscheckuser["data"]["userOne"];
        } else {
            return $rscheckuser;
        }
        $order_guest_brief = msubstr(input("guest_brief", ""), 0, 100); //留言备注
        $total_amount = input("total_amount", 0, "floatval"); //收款金额


        if ($total_amount <= 0) {
            return ["code" => 0, "message" => "付款金额必需大于0", "data" => ""];
        }
        $buyer_id = input("buyer_id"); //
        //$this->verifyPostDataHelper($user_id);
        $userAgent = strtolower(request()->header("user-agent"));
        //trace("浏览器：".$userAgent);
        //判断微信or支付宝
        if (preg_match("/micromessenger/", $userAgent)) //微信
        {
            $channel = "jsapi_wxpay";
        } else if (preg_match("/alipayclient/", $userAgent)) //支付宝
        {
            $channel = "jsapi_alipay";
        } else //未知
        {
            //$this->error("未知的浏览器");
            $channel = "jsapi_alipay";
        }
        $subject = input("subject", "聚合码支付(" . $userOne->store_name . ")");
        $rs = model("order")->createAppOrder(["channel" => $channel, "buyer_id" => $buyer_id, "user_id" => $user_id, "total_amount" => $total_amount, "subject" => $subject, "guest_brief" => $order_guest_brief, "product_code" => ($channel == "jsapi_alipay" ? "QUICK_WAP_WAY" : ""), "create_where" => $create_where, "sale_order_num" => $sale_order_num]);
        return $rs;
    }
}