<?php
namespace app\common\model;

use think\Model;

class Order extends Model
{
    protected $type = [
        "order_addtime"  => "timestamp",
        "order_pay_time" => "timestamp",
    ];
    protected $autoWriteTimestamp = false;
    //createOrder(["channel"=>$channel,"user_id"=>$user_id,"total_amount"=>$total_amount,"subject"=>$subject,"auth_code"=>$auth_code])
    /**
     * 生成本地预支付订单
     * 前置：user_id已经判断合法有效的；
     * ["channel"=>$channel,"user_id"=>$user_id,"total_amount"=>$total_amount,"subject"=>$subject,"auth_code"=>$auth_code]
     * @param  [type] $paramArray [description]
     * @return [type]             [description]
     */
    public function createOrder($paramArray)
    {
        $pay = model("pay");
        if (!$order_channel_id = $pay->payWayTranslate($paramArray["channel"])) {
            return ["code" => 0, "message" => "未知的支付通道{" . $paramArray["channel"] . "}", "data" => ""];
        }
        if (!is_numeric($paramArray["user_id"])) {
            return ["code" => 0, "message" => "user_id 错误", "data" => ""];
        }
        if (!is_numeric($paramArray["total_amount"])) {
            return ["code" => 0, "message" => "收款金额错误", "data" => ""];
        }
        if ($paramArray["total_amount"] > 9999999) {
            return ["code" => 0, "message" => "收款金额超限", "data" => ""];
        }
        //参数处理
        switch ($paramArray["channel"]) {
            case 'face_alipay':
            case 'face_wxpay':
                if (empty($paramArray["auth_code"])) {
                    return ["code" => 0, "message" => "付款码不可为空", "data" => ""];
                }
                break;
            default:
                $paramArray["auth_code"] = "";
                break;
        }
        //根据操作员 user_id ,获取店铺等数据
        $user_id       = $paramArray["user_id"];
        $user_store_id = model("user")->where(["user_id" => $user_id])->field(true)->value("user_store_id");
        $storeOne      = model("store")->where(["store_id" => $user_store_id])->field(true)->find();
        $shopOne       = model("shop")->where(["shop_id" => $storeOne->store_shop_id])->field(true)->find();
        //__操作员 user_id 检测
        //完整性判断
        if (strpos($paramArray["channel"], "alipay") !== false) //如果是支付宝
        {
            if (empty($shopOne->shop_alipay_app_auth_token)) {
                return ["code" => 0, "message" => $shopOne->shop_name . "还未扫描授权二维码", "data" => ""];
            }
        }
        if (strpos($paramArray["channel"], "wxpay") !== false) //如果是微信
        {
            if (empty($shopOne->shop_wxpay_sub_mch_id)) {
                return ["code" => 0, "message" => "请在“微信配置”->“微信支付商户号”设置参数", "data" => ""];
            }
        }
        //__完整性判断
        //设置默认标题
        if (empty($paramArray["subject"])) {
            $paramArray["subject"] = $storeOne->store_name;
        }
        //如果是微信支付需要调取mch_id参数（后台填写的）
        if (in_array($order_channel_id, [1002, 1004, 1006])) {
            $savedata["order_shop_mch_id"] = $shopOne->shop_wxpay_sub_mch_id;
        }
        $savedata['order_product_code']         = isset($paramArray["product_code"]) ? $paramArray["product_code"] : ""; //支付宝，销售产品码
        $savedata['order_user_id']              = $paramArray["user_id"];
        $savedata['order_addtime']              = time();
        $savedata['order_subject']              = $paramArray["subject"];
        $savedata['order_total_amount']         = floatval($paramArray["total_amount"]);
        $savedata['order_auth_code']            = $paramArray["auth_code"];
        $savedata['order_channel_id']           = $order_channel_id;
        $savedata['order_num']                  = "ZZ" . time();
        $savedata['order_shop_id']              = $shopOne->shop_id;
        $savedata['order_store_id']             = $storeOne->store_id;
        $savedata['order_guest_brief']          = isset($paramArray["guest_brief"]) ? $paramArray["guest_brief"] : "";
        $savedata['order_other_sale_order_num'] = isset($paramArray["sale_order_num"]) ? $paramArray["sale_order_num"] : "";
        $savedata['order_create_where']         = isset($paramArray["create_where"]) ? $paramArray["create_where"] : ""; //订单_创建的客户端

        $this->data($savedata)->isUpdate(false)->save();
        //再次更新订单号
        $this->order_num = $this->order_num . $this->order_id;
        $this->save();
        //__再次更新订单号
        //是否要预生成订单
        switch ($paramArray["channel"]) {
            case 'jsapi_alipay': //支付宝APP内，JSAPI唤起支付
                if($paramArray["create_where"]=='parking_month'){
                    $alipay = model("alipay");
                    if (empty($paramArray["buyer_id"])) {
                        return ["code" => 0, "message" => "buyer_id错误", "data" => ""];
                    }

                    $data["out_trade_no"]        = $this->order_num;
                    $data["discountable_amount"] = 0; //参与优惠计算的金额
                    $data["subject"]             = msubstr($savedata['order_subject'], 0, 50);
                    $data["total_amount"]        = $savedata['order_total_amount'];
                    //$data["seller_id"]           = $shopOne->shop_alipay_seller_id; //如果该值为空，则默认为商户签约账号对应的支付宝用户ID
                    $data["buyer_id"]        = $paramArray["buyer_id"]; 
                    $data["operator_id"]     = $savedata['order_user_id'];
                    $data["store_id"]        = $user_store_id;
                    $data["extend_params"]   = ["sys_service_provider_id" => config("sys_service_provider_id")];
                    $data["timeout_express"] = "2m";
                    trace('支付宝的生活号：'.json_encode($data));
                    trace('shop_alipay_app_auth_token: '.$shopOne->shop_alipay_app_auth_token_auto_pay);
                    $resultCodeArray         = $alipay->requestSHH("AlipayTradeCreateRequest", $data, $shopOne->shop_alipay_app_auth_token_auto_pay);
                    trace("支付宝生活号支付返回结果：".json_encode($resultCodeArray));
                    if ($resultCodeArray["code"] == 10000) {
                        $resultData["trade_no"] = $savedata['order_trade_no'] = $resultCodeArray["trade_no"];
                    } else {
                        return ["code" => 0, "message" => ($resultCodeArray["sub_msg"] ? $resultCodeArray["sub_msg"] : $resultCodeArray["msg"]), "data" => $data];
                    }
                }else{
                     $alipay = model("alipay");
                    if (empty($paramArray["buyer_id"])) {
                        return ["code" => 0, "message" => "buyer_id错误", "data" => ""];
                    }
                    $data["out_trade_no"]        = $this->order_num;
                    $data["discountable_amount"] = 0; //参与优惠计算的金额
                    $data["total_amount"]        = $savedata['order_total_amount'];
                    $data["subject"]             = msubstr($savedata['order_subject'], 0, 50);
                    //$data["seller_id"]           = $shopOne->shop_alipay_seller_id; //如果该值为空，则默认为商户签约账号对应的支付宝用户ID
                    $data["buyer_id"]        = $paramArray["buyer_id"];
                    $data["operator_id"]     = $savedata['order_user_id'];
                    $data["store_id"]        = $user_store_id;
                    $data["extend_params"]   = ["sys_service_provider_id" => config("sys_service_provider_id")];
                    $data["timeout_express"] = "2m";
                    trace('支付宝的正常支付：'.json_encode($data));
                    $resultCodeArray         = $alipay->request("AlipayTradeCreateRequest", $data, $shopOne->shop_alipay_app_auth_token);
                    trace('支付宝的正常支付返回结果：'.json_encode($resultCodeArray));
                    if ($resultCodeArray["code"] == 10000) {
                        $resultData["trade_no"] = $savedata['order_trade_no'] = $resultCodeArray["trade_no"];
                    } else {
                        return ["code" => 0, "message" => ($resultCodeArray["sub_msg"] ? $resultCodeArray["sub_msg"] : $resultCodeArray["msg"]), "data" => $data];
                    }
                }
               
                break;
            case 'jsapi_wxpay': //微信公众号APP内，JSAPI唤起支付
                $wxpay = model("wxpay");
                if (empty($paramArray["buyer_id"])) {
                    //即openid
                    return ["code" => 0, "message" => "openid错误", "data" => ""];
                }
                $orderPayData["payment"] = [
                    'sub_merchant_id' => $this->order_shop_mch_id, //子商户号
                ];
                $orderPayData["order"] = [
                    'body'         => msubstr($this->order_subject, 0, 50),
                    'out_trade_no' => $this->order_num,
                    'trade_type'   => 'JSAPI', //这个参数是写这里，跟NATIVE不一样
                    //'total_fee'    => _bcmul($this->order_total_amount, 100, 0), // 转化成分
                    'total_fee'    => round($this->order_total_amount * 100, 0), // 转化成分
                    'openid'       => $paramArray["buyer_id"],
                ];
                $rsObject = $wxpay->request("prepare", $orderPayData);
                if ($rsObject->return_code == 'SUCCESS' && $rsObject->result_code == 'SUCCESS') {
                    $prepayId = $rsObject->prepay_id;
                    //trace($prepayId, "debug");
                    //生成 jsapi的付款json code
                    $BrandWCPayRequest = $wxpay->request("configForPayment", ["prepayId" => $prepayId]);
                    return ["code" => 1, "message" => "", "data" => $BrandWCPayRequest];
                } else {
                    return ["code" => 0, "message" => "统一下单：" . $rsObject->return_msg . $rsObject->err_code . $rsObject->err_code_des, "data" => ""];
                }
                break;
            default:
                break;
        }
        $resultData["order_id"]     = $this->order_id;
        $resultData["out_trade_no"] = $this->order_num;
        return ["code" => 1, "message" => "", "data" => $resultData];
    }

    /**
     * 生成本地预支付订单
     * 前置：user_id已经判断合法有效的；
     * ["channel"=>$channel,"user_id"=>$user_id,"total_amount"=>$total_amount,"subject"=>$subject,"auth_code"=>$auth_code]
     * @param  [type] $paramArray [description]
     * @return [type]             [description]
     */
    public function createAppOrder($paramArray)
    {
        $pay = model("pay");
        if (!$order_channel_id = $pay->payWayTranslate($paramArray["channel"])) {
            return ["code" => 0, "message" => "未知的支付通道{" . $paramArray["channel"] . "}", "data" => ""];
        }
        if (!is_numeric($paramArray["user_id"])) {
            return ["code" => 0, "message" => "user_id 错误", "data" => ""];
        }
        if (!is_numeric($paramArray["total_amount"])) {
            return ["code" => 0, "message" => "收款金额错误", "data" => ""];
        }
        if ($paramArray["total_amount"] > 9999999) {
            return ["code" => 0, "message" => "收款金额超限", "data" => ""];
        }
        //参数处理
        switch ($paramArray["channel"]) {
            case 'face_alipay':
            case 'face_wxpay':
                if (empty($paramArray["auth_code"])) {
                    return ["code" => 0, "message" => "付款码不可为空", "data" => ""];
                }
                break;
            default:
                $paramArray["auth_code"] = "";
                break;
        }
        //根据操作员 user_id ,获取店铺等数据
        $user_id       = $paramArray["user_id"];
        $user_store_id = model("user")->where(["user_id" => $user_id])->field(true)->value("user_store_id");
        $storeOne      = model("store")->where(["store_id" => $user_store_id])->field(true)->find();
        $shopOne       = model("shop")->where(["shop_id" => $storeOne->store_shop_id])->field(true)->find();
        //__操作员 user_id 检测
        //完整性判断
        if (strpos($paramArray["channel"], "alipay") !== false) //如果是支付宝
        {
            if (empty($shopOne->shop_alipay_app_auth_token)) {
                return ["code" => 0, "message" => $shopOne->shop_name . "还未扫描授权二维码", "data" => ""];
            }
        }
        if (strpos($paramArray["channel"], "wxpay") !== false) //如果是微信
        {
            if (empty($shopOne->shop_wxpay_sub_mch_id)) {
                return ["code" => 0, "message" => "请在“微信配置”->“微信支付商户号”设置参数", "data" => ""];
            }
        }
        //__完整性判断
        //设置默认标题
        if (empty($paramArray["subject"])) {
            $paramArray["subject"] = $storeOne->store_name;
        }
        //如果是微信支付需要调取mch_id参数（后台填写的）
        if (in_array($order_channel_id, [1002, 1004, 1006])) {
            $savedata["order_shop_mch_id"] = $shopOne->shop_wxpay_sub_mch_id;
        }
        $savedata['order_product_code']         = isset($paramArray["product_code"]) ? $paramArray["product_code"] : ""; //支付宝，销售产品码
        $savedata['order_user_id']              = $paramArray["user_id"];
        $savedata['order_addtime']              = time();
        $savedata['order_subject']              = $paramArray["subject"];
        $savedata['order_total_amount']         = floatval($paramArray["total_amount"]);
        $savedata['order_auth_code']            = $paramArray["auth_code"];
        $savedata['order_channel_id']           = $order_channel_id;
        $savedata['order_num']                  = $this->getOrderNum();
        $savedata['order_shop_id']              = $shopOne->shop_id;
        $savedata['order_store_id']             = $storeOne->store_id;
        $savedata['order_guest_brief']          = isset($paramArray["guest_brief"]) ? $paramArray["guest_brief"] : "";
        $savedata['order_other_sale_order_num'] = isset($paramArray["sale_order_num"]) ? $paramArray["sale_order_num"] : "";
        $savedata['order_create_where']         = "app"; //订单_创建的客户端

        $this->data($savedata)->isUpdate(false)->save();
        $this->order_num = $this->order_num . $this->order_id;
        $this->save();
        //是否要预生成订单
        switch ($paramArray["channel"]) {
            case 'jsapi_alipay': //支付宝APP内，JSAPI唤起支付
                    $alipay = model("alipay");
                    if (empty($paramArray["buyer_id"])) {
                        return ["code" => 0, "message" => "buyer_id错误", "data" => ""];
                    }
                    $data["out_trade_no"]        = $this->order_num;
                    $data["discountable_amount"] = 0; //参与优惠计算的金额
                    $data["total_amount"]        = $savedata['order_total_amount'];
                    $data["subject"]             = msubstr($savedata['order_subject'], 0, 50);
                    //$data["seller_id"]           = $shopOne->shop_alipay_seller_id; //如果该值为空，则默认为商户签约账号对应的支付宝用户ID
                    $data["buyer_id"]        = $paramArray["buyer_id"];
                    $data["operator_id"]     = $savedata['order_user_id'];
                    $data["store_id"]        = $user_store_id;
                    $data["extend_params"]   = ["sys_service_provider_id" => config("sys_service_provider_id")];
                    $data["timeout_express"] = "2m";
                    trace('支付宝的正常支付：'.json_encode($data));
                    $resultCodeArray         = $alipay->request("AlipayTradeCreateRequest", $data, $shopOne->shop_alipay_app_auth_token);
                    trace('支付宝的正常支付返回结果：'.json_encode($resultCodeArray));
                    if ($resultCodeArray["code"] == 10000) {
                        $resultData["trade_no"] = $savedata['order_trade_no'] = $resultCodeArray["trade_no"];
                    } else {
                        return ["code" => 0, "message" => ($resultCodeArray["sub_msg"] ? $resultCodeArray["sub_msg"] : $resultCodeArray["msg"]), "data" => $data];
                    }

                break;
            case 'jsapi_wxpay': //微信公众号APP内，JSAPI唤起支付
                $wxpay = model("wxpay");
                if (empty($paramArray["buyer_id"])) {
                    //即openid
                    return ["code" => 0, "message" => "openid错误", "data" => ""];
                }
                $orderPayData["payment"] = [
                    'sub_merchant_id' => $this->order_shop_mch_id, //子商户号
                ];
                $orderPayData["order"] = [
                    'body'         => msubstr($this->order_subject, 0, 50),
                    'out_trade_no' => $this->order_num,
                    'trade_type'   => 'JSAPI', //这个参数是写这里，跟NATIVE不一样
                    //'total_fee'    => _bcmul($this->order_total_amount, 100, 0), // 转化成分
                    'total_fee'    => round($this->order_total_amount * 100, 0), // 转化成分
                    'openid'       => $paramArray["buyer_id"],
                ];
                $rsObject = $wxpay->request("prepare", $orderPayData);
                if ($rsObject->return_code == 'SUCCESS' && $rsObject->result_code == 'SUCCESS') {
                    $prepayId = $rsObject->prepay_id;
                    //trace($prepayId, "debug");
                    //生成 jsapi的付款json code
                    $BrandWCPayRequest = $wxpay->request("configForPayment", ["prepayId" => $prepayId]);
                    return ["code" => 1, "message" => "", "data" => $BrandWCPayRequest];
                } else {
                    return ["code" => 0, "message" => "统一下单：" . $rsObject->return_msg . $rsObject->err_code . $rsObject->err_code_des, "data" => ""];
                }
                break;
            default:
                break;
        }
        $resultData["order_id"]     = $this->order_id;
        $resultData["out_trade_no"] = $this->order_num;
        return ["code" => 1, "message" => "", "data" => $resultData];
    }


    /**
     * [订单状态改变]
     * 通常是支付宝、微信等第三方异步通知执行的结果
     * 注意：不可含有任何停止的(die,exit等)
     * @param  [type] $order_num     [订单号]
     * @param  [type] $real_pay_cash [真实支付的金额]
     * @param  [type] $trade_no         [第三方支付ID]
     * @param  [type] $otherArray    [description]
     * @param  string $channel        [description]
     * @return [type]                [description]
     * afterPayNotify($order_num, $real_pay_cash, $aboutid, $otherArray, input("channel"))
     */
    public function orderStatusChange($order_num, $real_pay_cash, $trade_no, $channel, $trade_status = "", $otherArray = [])
    {
        $orderOne   = $this->where(["order_num" => $order_num])->find();
        $changetime = time();
        switch ($channel) {
            case 'alipay':
            case 'face_alipay':
            case 'wap_alipay':
            case 'jsapi_alipay':
            case 'auto_pay_alipay'://自动代扣
                //状态值再统一下(有手动调用的，还有异步自动通知的)
                switch ($trade_status) {
                    case 'TRADE_SUCCESS':
                        $trade_status = 100;
                        break;
                    case 'TRADE_FINISHED':
                        $trade_status = 300;
                        break;
                    case 'TRADE_CLOSED':
                        $trade_status = 200;
                        break;
                    case 'WAIT_BUYER_PAY':
                        $trade_status = 600;
                        break;
                    default:
                        # code...
                        break;
                }
                $changetime = isset($otherArray["gmt_payment"]) ? strtotime($otherArray["gmt_payment"]) : $changetime;
                break;
            case 'wxpay':
            case 'face_wxpay':
                $changetime = isset($otherArray["time_end"]) ? strtotime($otherArray["time_end"]) : $changetime;
                break;
            default:
                trace("orderStatusChange未处理" . $order_num, "error");
                break;
        }
        $updateData = "";
        switch ($trade_status) {
            case 100: //支付成功
                /*trace($orderOne->order_id.":NNNN","debug");
                trace($trade_status.":KKKK","debug");*/
                if (!db("order_pay_log")->where(["order_pay_log_order_id" => $orderOne->order_id, "order_pay_log_status" => $trade_status])->find()) //防止重复执行（有同步返回和异步通知）
                {
                    $updateData = ["order_status" => $trade_status, "order_trade_no" => $trade_no, "order_pay_time" => $changetime, "order_pay_realprice" => $real_pay_cash];
                }
                break;
            case 101: //有部分退款(没有异步通知)
            case 200: //全部退款
                if ($orderOne->order_status != 200) //已退没了
                {
                    $updateData = ["order_status" => $trade_status, "order_pay_realprice" => $real_pay_cash];
                }
                break;
            default:
                $updateData = ["order_status" => $trade_status];
                break;
        }
        if ($updateData) {
            $order_pay_log_data = [
                "order_pay_log_order_id"    => $orderOne->order_id,
                "order_pay_log_addtime"     => $changetime,
                "order_pay_log_status"      => $trade_status,
                "order_pay_log_status_info" => isset($otherArray["status_info"]) ? $otherArray["status_info"] : "",
                "order_pay_log_returncode"  => json_encode($otherArray, JSON_UNESCAPED_UNICODE),
                "order_pay_log_from"        => isset($otherArray["order_pay_log_from"]) ? $otherArray["order_pay_log_from"] : "sync",
                "order_pay_log_user_id"     => isset($otherArray["user_id"]) ? $otherArray["user_id"] : 0,
                "order_pay_log_data1"       => isset($otherArray["order_pay_log_data1"]) ? $otherArray["order_pay_log_data1"] : "", //附属数据
            ];
            db("order_pay_log")->insert($order_pay_log_data);
            $orderOneObject = $this->save($updateData, ["order_id" => $orderOne->order_id]);
            //通知推送到前端
            if ($orderOne->order_create_where) {
                $payrs = model("pay")->tradeQueryFontRequest($orderOne->order_id);
                if ($payrs["data"]["trade_status"] == 100) {
                    $payrs["data"]["printhtml"] = model("printorder")->printTmp($payrs["data"]["order_num"]);
                }
                $saleOne = db("user")->where(["user_id" => $orderOne->order_user_id])->field("user_token,user_pushtoken")->find();
                switch ($orderOne->order_create_where) {
                    case 'pc': //给PC端推送
                        $user_token = $saleOne["user_token"];
                        if ($user_token) {
                            model("push")->goEasyPush($user_token, ["action" => "orderstatic", "data" => $payrs["data"]]);
                        }
                        break;
                    case 'payapp': //APPPay的订单
                        $user_pushtoken = $saleOne["user_pushtoken"];
                        if ($user_pushtoken) {
                            $pushdata = [
                                "title"     => "有新的支付动态",
                                "text"      => "查看详情",
                                "needlogin" => 0,
                                "type"      => "sendevent",
                                "event"     => [
                                    [
                                        "eventname"  => "orderchange",
                                        "eventparam" => ["action" => "orderstatic", "data" => $payrs["data"]],
                                    ],
                                ],
                            ];
                            model("push")->dopush($user_pushtoken, $pushdata);
                        }
                        break;
                    case 'guestscan': //给PC端,手机都推送
                        $user_token = $saleOne["user_token"];
                        if ($user_token) {
                            model("push")->goEasyPush($user_token, ["action" => "orderstatic", "data" => $payrs["data"]]);
                        }
                        $user_pushtoken = $saleOne["user_pushtoken"];
                        if ($user_pushtoken) {
                            $pushdata = [
                                "title"     => "有新的支付动态",
                                "text"      => "查看详情",
                                "needlogin" => 0,
                                "type"      => "sendevent",
                                "event"     => [
                                    [
                                        "eventname"  => "orderchange",
                                        "eventparam" => ["action" => "orderstatic", "data" => $payrs["data"]],
                                    ],
                                ],
                            ];
                            model("push")->dopush($user_pushtoken, $pushdata);
                        }
                        break;

                    case 'diancanxiaochengxu': //给点餐小程序
                        $user_token = $saleOne["user_token"];
                        if ($user_token) {
                            //model("push")->goEasyPush($user_token, ["action" => "orderstatic", "data" => $payrs["data"]]);
                        }
                        //给第三方做服务器推送
                        $pushdata           = $this->where(["order_id" => $orderOne->order_id])->find();
                        $Postdata["parpam"] = json_encode($pushdata, JSON_UNESCAPED_UNICODE);
                        innerHttpsPost("cmd/innerrequest/push2xcxserver", $Postdata);
                        break;

                    case 'parking': //给停车收费
                        model("parking_record")->parkingRecordStateChange($orderOne->order_other_sale_order_num);
                        break;

                    case 'parking_month'://包月停车
                        model('member_car_record')->carRecordStateChange($orderOne->order_other_sale_order_num);
                        break;
                }
            }
            //__通知推送到前端
        }
    }
    /**
     * 订单状态转中文
     * @param  [type] $status [description]
     * @return [type]         [description]
     */
    public function status2nicename($statuscode)
    {
        $status[0]   = "等待支付";
        $status[100] = "支付成功";
        $status[101] = "部分退款";
        $status[200] = "全额退款"; //支付超时关闭或全额退款
        $status[300] = "交易结束，不可退款";
        $status[400] = "支付失败";
        $status[500] = "未知异常，调用查询接口确认支付结果";
        $status[600] = "等待买家付款，如输入支付密码";
        return isset($status[$statuscode]) ? $status[$statuscode] : "没有找到状态描述";
    }

    /**
     * 订单号生成
     * @author wzs
     * @return string
     */
    function getOrderNum(){
        $order_number = date('Ymd').substr(time(),5,4).rand(100,999);
        $is_exist_num =$this->where(['order_num'=>$order_number])->count();
        if($is_exist_num){
            $order_number = $this->getOrderNum();
        }

        return $order_number;
    }
}
