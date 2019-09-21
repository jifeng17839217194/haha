<?php
namespace app\common\model;

use think\Model;

class Pay extends Model
{
    protected $type = [
        //'sysconfig' => 'object',
        //'user_last_logintime' => 'timestamp',
    ];
    //支付通道数据
    public function payChannel($type = 0)
    {
        //枚举，固定死了
        $id2name = [
            1001 => "face_alipay",
            1002 => "face_wxpay",
            1003 => "wap_alipay",
            1004 => "wap_wxpay",
            1005 => "jsapi_alipay",
            1006 => "jsapi_wxpay",
            1007 => "auto_pay_alipay",
            1008 => "cash_pay",
        ];
        $id2cnname = [
            1001 => "付款码(支付宝)",
            1002 => "付款码(微信)",
            1003 => "wap_alipay",
            1004 => "wap_wxpay",
            1005 => "聚合收款码(支付宝)",
            1006 => "聚合收款码(微信)",
            1007 => "代扣(支付宝)",
            1008 => "现金支付",
        ];
        $name2id = [
            "face_alipay"     => 1001,
            "face_wxpay"      => 1002,
            "wap_alipay"      => 1003,
            "wap_wxpay"       => 1004,
            "jsapi_alipay"    => 1005,
            "jsapi_wxpay"     => 1006,
            "auto_pay_alipay" => 1007,
            "cash_pay" => 1008,
        ];
        switch ($type) {
            case 0:
                return $id2name;
                break;
            case 1:
                return $id2cnname;
                break;
            case 2:
                return $name2id;
                break;
            default:
                # code...
                break;
        }
    }
    //支付方式转义
    //$isArray 是否返回全信息数组
    public function payWayTranslate($payway, $isArray = false)
    {
        //枚举，固定死了
        $id2name   = $this->payChannel(0);
        $id2cnname = $this->payChannel(1);
        $name2id   = $this->payChannel(2);
        if (is_numeric($payway)) {
            if ($isArray == true) {
                return isset($id2name[$payway]) ? ["num" => $payway, "name" => $id2name[$payway], "cn" => $id2cnname[$payway]] : false;
            } else {
                return isset($id2name[$payway]) ? $id2name[$payway] : false;
            }
        } else {
            if ($isArray == true) {
                return isset($name2id[$payway]) ? ["num" => $payway, "name" => $name2id[$payway], "cn" => $id2cnname[$payway]] : false;
            } else {
                return isset($name2id[$payway]) ? $name2id[$payway] : false;
            }
        }
    }
    /**
     * 订单前端状态查询，只查本地数据库(订单状态统一由轮训机制自行更新)
     * @param  [type] $orderidOrordernum [description]
     * @return [type]                    [description]
     */
    public function tradeQueryFontRequest($orderidOrordernum)
    {
        return $this->tradeQueryRequest($orderidOrordernum, $onlylocationdb = true);
    }
    //统一收单线下交易查询(通常只给服务器内部查询)
    //含远程查询
    //参数只允许order_id 或 order_num (目前只自动识别这二个)
    //先本地数据库查询，不是成功状态就到api查询
    public function tradeQueryRequest($orderidOrordernum, $onlylocationdb = false)
    {
        if (!preg_match("/^[0-9]{1,}$/", $orderidOrordernum)) {
            $where["order_num|order_trade_no"] = $orderidOrordernum;
        } else {
            $where["order_id"] = $orderidOrordernum;
        }
        $order    = model("order");
        $orderOne = $order->where($where)->find();
        if ($orderOne) {
            $channel = $this->payWayTranslate($orderOne->order_channel_id);
            //统一返回格式
            $returnData = [
                "channel"       => $channel, //支付通道
                "trade_status"  => $orderOne->order_status, //支付状态
                "order_num"     => $orderOne->order_num, //订单号
                "pay_time"      => $orderOne->order_pay_time, //支付时间
                "total_amount"  => $orderOne->order_total_amount, //这个下面“修改订单状态”用到 //订单金额
                "pay_realprice" => $orderOne->order_pay_realprice, //真实支付金额
                "create_where"  => $orderOne->order_create_where,
            ];
            if ($orderOne->order_status == 300) //已经是支付完成的
            {
                return ["code" => 1, "message" => "ok", "data" => $returnData];
            }
            if ($orderOne->order_status == 400 || $orderOne->order_status == 200) //订单已关闭
            {
                return ["code" => 1, "message" => "订单已关闭", "data" => $returnData];
            }
            //只要求本地查询,终止后面的远程查询
            if ($onlylocationdb) {
                return ["code" => 1, "message" => "", "data" => $returnData];
            }
            //统一编制返回的数据
            //code 以支付宝为准，完全失败统一为0
            $tongyiRsData = function ($channel, $code, $rs_source, $orderOne) use ($returnData) {
                $code           = $code; //code 是数值型
                $message        = "";
                $order_pay_time = 0; //支付时间
                switch ($channel) {
                    case 'face_alipay': //以阿里的为标准
                        switch (floatval($code)) {
                            //https://docs.open.alipay.com/194/105170
                            case 10000: //通信成功
                                switch ($rs_source["trade_status"]) {
                                    case 'WAIT_BUYER_PAY': //等待支付
                                        $returnData["total_amount"] = $rs_source["total_amount"];
                                        $returnData["trade_status"] = 600; //等待支付
                                        $message                    = "等待支付";
                                        break;
                                    case 'TRADE_SUCCESS': //支付完成
                                        $order_pay_time              = strtotime($rs_source["send_pay_date"]);
                                        $returnData["pay_time"]      = $order_pay_time;
                                        $returnData["pay_realprice"] = $rs_source["receipt_amount"];
                                        $returnData["trade_status"]  = 100; //
                                        $returnData["rs_source"]     = $rs_source; //订单原始数据（异步查收需要这个数据）
                                        $message                     = "支付完成";
                                        break;
                                    case 'TRADE_CLOSED': //未付款交易超时关闭，或支付完成后全额退款
                                        $returnData["trade_status"] = 200; //
                                        $message                    = "超时关闭或已退款";
                                        break;
                                    case 'TRADE_FINISHED': //交易结束
                                        $returnData["trade_status"] = 300; //
                                        $message                    = "支付完成";
                                        break;
                                    default:
                                        # code...
                                        break;
                                }
                                break;
                            default:
                                $returnData["trade_status"] = $orderOne->order_status;
                                $message                    = isset($rs_source["sub_msg"]) ? trim($rs_source["sub_msg"]) : trim($rs_source["message"]);
                                break;
                        }
                        break;
                    case "face_wxpay":
                        switch ($code) {
                            case 'SUCCESS': //支付成功
                                $returnData["trade_status"]  = 100;
                                $returnData["pay_time"]      = date("Y-m-d H:i:s", strtotime($rs_source["time_end"]));
                                $returnData["pay_realprice"] = $rs_source["cash_fee"]; //这个下面“修改订单状态”用到
                                $returnData["rs_source"]     = $rs_source; //订单原始数据（异步查收需要这个数据）
                                $message                     = "成功"; //微信成功时，没有trade_state_desc返回
                                break;
                            case 'REFUND': //转入退款
                            case 'CLOSED': //
                            case 'REVOKED': //
                                $returnData["trade_status"] = 200;
                                $message                    = $rs_source["trade_state_desc"] . "(" . $code . ")";
                                break;
                            case 'trade_state': //订单未支付
                                $returnData["trade_status"] = 500;
                                $message                    = $rs_source["trade_state_desc"] . "(" . $code . ")";
                                break;
                            case "USERPAYING": //等待5秒，然后调用被扫订单结果查询API，查询当前订单的不同状态，决定下一步的操作。
                                $returnData["trade_status"] = 600;
                                $message                    = $rs_source["trade_state_desc"] . "(" . $code . ")";
                                break;
                            default:
                                $returnData["trade_status"] = 400;
                                break;
                        }
                        break;
                    default:
                        return ["code" => 0, "message" => "-10006未处理的支付通道" . $channel, "data" => []];
                        break;
                }
                //修改订单状态
                //保存成功日志（这个日志在轮训api要用到）
                //这里只做查询，不在记录数据库，因为支付结果会异步推送时已经处理过了，，2017-9-14 14:39:37
                // if ($returnData["trade_status"] != 600) //日志量太大了
                // {
                //     model("order")->orderStatusChange($orderOne->order_num, 0, "", $channel, $returnData["trade_status"], $rs_source);
                // }
                //__修改订单状态
                return ["code" => 1, "message" => $message, "data" => $returnData];
            };
            switch ($channel) {
                case 'face_alipay':
                    $shop_alipay_app_auth_token = model("shop")->where(["shop_id" => $orderOne->order_shop_id])->value("shop_alipay_app_auth_token");
                    $result                     = model("alipay")->request("AlipayTradeQueryRequest", ["out_trade_no" => $orderOne->order_num], $shop_alipay_app_auth_token);
                    break;
                case 'face_wxpay':
                    $rsObject = model("wxpay")->request("WxpayTradeQueryRequest", ["out_trade_no" => $orderOne->order_num, "payment" => ['sub_merchant_id' => $orderOne->order_shop_mch_id]]); //cjh_wait
                    $result   = json_decode((json_encode($rsObject, JSON_UNESCAPED_UNICODE)), 1);
                    if ($rsObject->return_code == "SUCCESS") {
                        $result["code"] = $rsObject->trade_state;
                    } else {
                        return ["code" => 0, "message" => $rsObject->err_code . "(" . $channel . ")", "data" => ""];
                    }
                    break;
                default:
                    return ["code" => 0, "message" => "未知的请求" . $channel, "data" => ""];
                    break;
            }
            //return ["code" => 1, "message" => $result, "data" => ""];
            return $tongyiRsData($channel, $result["code"], $result, $orderOne);
        } else {
            return ["code" => 0, "message" => "本地订单不存在", "data" => ""];
        }
    }
    /**
     * [doPay 统一的发起支付入口]
     * @param  [type] $order_num [订单号]
     * @return [type]            [内部支付成功OR第三方支付的加密code]
     */
    public function doPay($order_id)
    {
        $order    = model("order");
        $orderOne = $order->find($order_id);
        if ($orderOne) {
            //统一编制返回的数据
            //code 以支付宝为准，完全失败统一为0
            $tongyiRsData = function ($channel, $code, $rs_source, $orderOne) {
                //$code       = $code;
                $returnData = [];
                $message    = "";
                $rscode     = 1;
                $trade_no   = ""; //第三方订单号
                //标准返回以下6项
                $returnData["channel"]       = $channel;
                $returnData["order_num"]     = $orderOne->order_num; //商户的订单号
                $returnData["trade_status"]  = ""; //
                $returnData["total_amount"]  = $orderOne->order_total_amount; //订单金额
                $returnData["pay_time"]      = ""; //支付日期
                $returnData["pay_realprice"] = $orderOne->order_pay_realprice; //真实支付金额
                $returnData["create_where"]  = $orderOne->order_create_where;
                //__标准返回以下4项

                $scheduled_tasks_time_interval = config("scheduled_tasks_limits");
                if ($orderOne->order_create_where == "moxiaohe") {
                    $scheduled_tasks_time_interval = 6; //超市端的方案，缩短状态时间,6次是30秒
                }

                //统一状态值
                switch ($channel) {
                    case 'face_alipay': //
                        switch (floatval($code)) {
                            //https://docs.open.alipay.com/194/105170
                            case 10000: //完全成功
                                $trade_no                   = $rs_source["trade_no"];
                                $returnData["trade_status"] = 100; //
                                //$returnData["pay_realprice"] = $rs_source["total_amount"];
                                $returnData["pay_realprice"] = $rs_source["receipt_amount"];
                                $returnData["pay_time"]      = isset($rs_source["gmt_payment"]) ? $rs_source["gmt_payment"] : time(); //2018-1-20 18:14:35 ,如果是预授权转支付是没有这个参数的
                                //$message                    = trim($rs_source["message"]);
                                $message = isset($rs_source["sub_msg"]) ? trim($rs_source["sub_msg"]) : trim($rs_source["msg"]);
                                break;
                            case 10003: //等待用户付款,发起轮询流程：等待5秒后调用交易查询接口alipay.trade.query通过支付时传入的商户订单号(out_trade_no)查询支付结果
                                $trade_no                   = "";
                                $returnData["trade_status"] = 600; //
                                //$message                    = trim($rs_source["message"]);
                                $message = isset($rs_source["sub_msg"]) ? trim($rs_source["sub_msg"]) : trim($rs_source["msg"]);
                                //加入任务池

                                innerHttpsPost("cmd/innerrequest/addscheduledtasks", [
                                    "scheduled_tasks_title"         => "支付宝支付结果查询",
                                    "scheduled_tasks_start_time"    => time(), //马上开始
                                    "scheduled_tasks_end_time"      => time() + config("scheduled_tasks_interval") * $scheduled_tasks_time_interval, //只查询120秒,超时结束掉订单
                                    "scheduled_tasks_time_interval" => json_encode([5], JSON_UNESCAPED_UNICODE), //间隔5秒
                                    "scheduled_tasks_name"          => "check_alipay_order_status", //检测微信订单的状态
                                    "scheduled_tasks_param"         => json_encode(["out_trade_no" => $rs_source["out_trade_no"]], JSON_UNESCAPED_UNICODE),
                                ]);
                                //__加入任务池
                                break;
                            case 20000: //未知异常,调用查询接口确认支付结果
                                $trade_no                   = $rs_source["trade_no"];
                                $returnData["trade_status"] = 500; //
                                $message                    = trim($rs_source["message"]);
                                //加入任务池
                                innerHttpsPost("cmd/innerrequest/addscheduledtasks", [
                                    "scheduled_tasks_title"         => "支付宝支付结果查询",
                                    "scheduled_tasks_start_time"    => time(), //马上开始
                                    "scheduled_tasks_end_time"      => time() + config("scheduled_tasks_interval") * $scheduled_tasks_time_interval, //只查询120秒,超时结束掉订单
                                    "scheduled_tasks_time_interval" => json_encode([5], JSON_UNESCAPED_UNICODE), //间隔5秒
                                    "scheduled_tasks_name"          => "check_alipay_order_status", //检测微信订单的状态
                                    "scheduled_tasks_param"         => json_encode(["out_trade_no" => $rs_source["out_trade_no"]], JSON_UNESCAPED_UNICODE),
                                ]);
                                //__加入任务池
                                break;
                            default:
                                $returnData["trade_status"] = 400; //
                                $trade_no                   = "";
                                $message                    = isset($rs_source["sub_msg"]) ? trim($rs_source["sub_msg"]) : trim($rs_source["msg"]);
                        }
                        break;
                    case 'wap_alipay': //
                        $returnData["trade_status"] = 0; //
                        $returnData["html"]         = $rs_source["data"];
                        break;
                    case "face_wxpay":
                        if ($code == "SUCCESS") //支付成功
                        {
                            $trade_no                    = $rs_source["transaction_id"];
                            $returnData["trade_status"]  = 100; //
                            $returnData["pay_realprice"] = round($rs_source["total_fee"] / 100, 2);
                            $returnData["pay_time"]      = date("Y-m-d H:i:s", strtotime($rs_source["time_end"])); //2014-11-27 15:45:57
                            $message                     = "支付成功";
                        } else //FAIL
                        {
                            switch ($rs_source["err_code"]) {
                                case 'SYSTEMERROR': //支付结果未知,请立即调用被扫订单结果查询API，查询当前订单状态，并根据订单的状态决定下一步的操作。
                                case "BANKERROR": //银行端超时
                                    $trade_no                   = "";
                                    $returnData["trade_status"] = 500; //
                                    $message                    = "支付结果未知,请查询支付结果";
                                    //加入任务池
                                    innerHttpsPost("cmd/innerrequest/addscheduledtasks", [
                                        "scheduled_tasks_title"         => "微信支付结果查询",
                                        "scheduled_tasks_start_time"    => time(), //马上开始
                                        "scheduled_tasks_end_time"      => time() + config("scheduled_tasks_interval") * $scheduled_tasks_time_interval, //只查询120秒,超时结束掉订单
                                        "scheduled_tasks_time_interval" => json_encode([5], JSON_UNESCAPED_UNICODE), //间隔5秒
                                        "scheduled_tasks_name"          => "check_wxpay_order_status", //检测微信订单的状态
                                        "scheduled_tasks_param"         => json_encode(["out_trade_no" => $orderOne->order_num], JSON_UNESCAPED_UNICODE),
                                    ]);
                                    //__加入任务池
                                    break;
                                case "USERPAYING": //等待5秒，然后调用被扫订单结果查询API，查询当前订单的不同状态，决定下一步的操作。
                                    $trade_no                   = "";
                                    $returnData["trade_status"] = 600; //
                                    $message                    = $rs_source["err_code_des"];
                                    //加入任务池
                                    innerHttpsPost("cmd/innerrequest/addscheduledtasks", [
                                        "scheduled_tasks_title"         => "微信支付结果查询",
                                        "scheduled_tasks_start_time"    => time(), //马上开始
                                        "scheduled_tasks_end_time"      => time() + config("scheduled_tasks_interval") * $scheduled_tasks_time_interval, //只查询120秒,超时结束掉订单
                                        "scheduled_tasks_time_interval" => json_encode([5], JSON_UNESCAPED_UNICODE), //间隔5秒
                                        "scheduled_tasks_name"          => "check_wxpay_order_status", //检测微信订单的状态
                                        "scheduled_tasks_param"         => json_encode(["out_trade_no" => $orderOne->order_num], JSON_UNESCAPED_UNICODE),
                                    ]);
                                    //__加入任务池
                                    break;
                                default:
                                    $message                    = $rs_source["err_code_des"];
                                    $trade_no                   = "";
                                    $returnData["trade_status"] = 400; //
                                    $rscode                     = 0;
                                    break;
                            }
                        }
                        break;
                    default:
                        $returnData = $rs_source;
                        $message    = "-10003未处理的" . $channel;
                        $rscode     = 0;
                        break;
                }
                //更新订单状态
                if ($returnData["trade_status"] != 0) //新订单不用二次操作(wap订单，只是获取了下支付html代码，没有变更)
                {
                    $rs_source["status_info"] = $message;
                    model("order")->orderStatusChange($orderOne->order_num, $returnData["pay_realprice"], $trade_no, $channel, $returnData["trade_status"], $rs_source);
                }
                //__修改订单状态
                return ["code" => $rscode, "message" => $message, "data" => $returnData];
            };
            //____=====================================================
            $resultCodeArray = [];
            switch ($orderOne->order_channel_id) {
                case 1003: //支付宝wap付款(没有使用到  2017-12-12 21:26:19)
                    //https://docs.open.alipay.com/203/107090/
                    $alipay                     = model("alipay");
                    $data["out_trade_no"]       = $orderOne->order_num;
                    $data["product_code"]       = $orderOne->order_product_code;
                    $data["total_amount"]       = $orderOne->order_total_amount;
                    $data["subject"]            = msubstr($orderOne->order_subject, 0, 50);
                    $data["store_id"]           = $orderOne->order_store_id;
                    $data["extend_params"]      = ["sys_service_provider_id" => config("sys_service_provider_id")];
                    $data["timeout_express"]    = "2m";
                    $shop_alipay_app_auth_token = model("shop")->where(["shop_id" => $orderOne->order_shop_id])->value("shop_alipay_app_auth_token");
                    $htmlcode                   = $alipay->request("AlipayTradeWapPayRequest", $data, $shop_alipay_app_auth_token);
                    $resultCodeArray["code"]    = 1;
                    $resultCodeArray["data"]    = $htmlcode;
                    break;
                case 1001: //支付宝扫描枪收款
                    //https://docs.open.alipay.com/194/105205/
                    $alipay                      = model("alipay");
                    $data["out_trade_no"]        = $orderOne->order_num;
                    $data["scene"]               = "bar_code";
                    $data["product_code"]        = $orderOne->order_product_code;
                    $data["auth_code"]           = $orderOne->order_auth_code;
                    $data["discountable_amount"] = 0; //参与优惠计算的金额
                    $data["total_amount"]        = $orderOne->order_total_amount;
                    $data["subject"]             = msubstr($orderOne->order_subject, 0, 50);
                    $data["operator_id"]         = $orderOne->order_user_id;
                    $data["store_id"]            = $orderOne->order_store_id; //如果不传，会导致在商家自营销活动发放的券无法核销。
                    $data["extend_params"]       = ["sys_service_provider_id" => config("sys_service_provider_id")];
                    $data["timeout_express"]     = "2m";
                    $shopOne                     = model("shop")->join("shop_attr", "shop_attr_shop_id=shop_id")->where(["shop_id" => $orderOne->order_shop_id])->field("shop_alipay_app_auth_token,shop_alipay_seller_id")->find();
                    $shop_alipay_app_auth_token  = $shopOne->shop_alipay_app_auth_token;
                    if ($orderOne->order_product_code == "PRE_AUTH") //预授权转支付,要多加参数2018-1-20
                    {
                        //https://docs.open.alipay.com/318/106384/
                        //auth_code
                        unset($data["auth_code"]);
                        unset($data["scene"]);
                        $orderFreezeOne = model("order_freeze")->where(["order_freeze_auth_no" => $orderOne->order_auth_code])->find();
                        //return ["code"=>0,"message"=>"","data"=>$orderFreezeOne];
                        $data["buyer_id"]  = $orderFreezeOne->order_freeze_app_user_id;
                        $data["seller_id"] = $shopOne->shop_alipay_seller_id;
                        $data["auth_no"]   = $orderFreezeOne->order_freeze_auth_no;
                        //return ["code"=>0,"message"=>"","data"=>$data];
                    }
                    $resultCodeArray = $alipay->request("AlipayTradePayRequest", $data, $shop_alipay_app_auth_token);
                    break;
                case 1002: //微信扫描枪收款
                    //https://pay.weixin.qq.com/wiki/doc/api/micropay_sl.php?chapter=9_10&index=1
                    $wxpay                   = model("wxpay");
                    $orderPayData["payment"] = [
                        'sub_merchant_id' => $orderOne->order_shop_mch_id, //, //子商户号_杭州顺航科技有限公司
                        'trade_type'      => 'MICROPAY', // JSAPI，NATIVE，APP...
                    ];
                    $orderPayData["order"] = [
                        'body'         => msubstr($orderOne->order_subject, 0, 50),
                        'out_trade_no' => $orderOne->order_num,
                        //'total_fee'    => _bcmul($orderOne->order_total_amount, 100, 0), // 转化成分
                        'total_fee'    => round($orderOne->order_total_amount * 100, 0), // 转化成分
                        'auth_code'    => $orderOne->order_auth_code,
                    ];
                    $rsObject = $wxpay->request("order", $orderPayData);
                    if ($rsObject->return_code == "SUCCESS") {
                        $resultCodeArray         = json_decode((json_encode($rsObject, JSON_UNESCAPED_UNICODE)), 1);
                        $resultCodeArray["code"] = $rsObject->result_code;
                    } else {
                        $resultCodeArray["err_code_des"] = $rsObject->return_msg;
                        $resultCodeArray["code"]         = $resultCodeArray["err_code"]         = "error";
                    }
                    break;
                default:
                    return ["code" => 0, "message" => "-10002未处理的支付" . $orderOne->order_channel_id, "data" => $orderOne->order_channel_id];
                    break;
            }
            if (!empty($resultCodeArray["code"])) {
                return $tongyiRsData($this->payWayTranslate($orderOne->order_channel_id), $resultCodeArray["code"], $resultCodeArray, $orderOne);
                //订单完成
                //等待用户支付
            } else {
                //出错了,通常是网络通信
                return $tongyiRsData($this->payWayTranslate($orderOne->order_channel_id), 0, $resultCodeArray, $orderOne);
            }
        }
    }
    /**
     * 检测退款、解冻类的基本权限测试
     * @param  [type] $userObjectOruser_id [用户ＩＤ，或Object]
     * @return [type]                      [description]
     */
    public function checkbackauth($order_shop_id, $order_store_id, $order_user_id, $userObjectOruser_id, $password)
    {
        $user = model("user");
        if (is_numeric($userObjectOruser_id)) {
            $userOne = $user->join("__STORE__", "store_id=user_store_id", "left")->join("__SHOP__", "shop_id=store_shop_id", "left")->where(["user_id" => $userObjectOruser_id])->field(["qs_user.*", "shop_id", "store_id", "shop_active", "store_name"])->find();
        } else {
            $userOne = $userObjectOruser_id;
        }
        if (!$userOne->user_refund_auth) {
            return ["code" => 0, "message" => "该账号没有退款权限", "data" => ""];
        }
        if (!$userOne->user_refund_password) {
            return ["code" => 0, "message" => "该账号未设置操作密码", "data" => ""];
        }
        if ($userOne->user_refund_password != $user->passwordSetMd5($password)) {
            return ["code" => 0, "message" => "密码错误", "data" => ""];
        }
        //退款权限限制
        switch ($userOne->user_role) {
            //商户管理员能退全部门店的所有的钱
            case 0:
                if ($userOne->shop_id != $order_shop_id) {
                    return ["code" => 0, "message" => "没有该订单退款权限", "data" => ""];
                }
                break;
            case 1: //店长 店长能退店内所有的钱
                if ($userOne->store_id != $order_store_id) {
                    return ["code" => 0, "message" => "没有该订单退款权限" . $userOne->store_id . "=" . $order_store_id, "data" => ""];
                }
                break;
            case 2: ////收银员只能退自己收的钱
                if ($userOne->user_id != $order_user_id) {
                    return ["code" => 0, "message" => "没有该订单退款权限", "data" => ""];
                }
                break;
            default:
                # code...
                break;
        }
        return ["code" => 1, "message" => "", "data" => ""];
    }
    /**
     * 退款权限判断
     * 收银员只能退款自己收到钱
     * 店长退整个店铺的钱
     * 商户管理员退所有店铺的钱
     * @param  [type] $orderidOrordernum         [订单id]
     * @param  [type] $userObjectOruser_id [用户对象（join了qs_shop,qs_store表），或者直接是user_id]
     * @param  退款密码
     * @return [type]                      [code,message,data]
     */
    public function refundBefaultCheck($orderidOrordernum, $userObjectOruser_id, $password)
    {
        if (!preg_match("/^[0-9]{1,}$/", $orderidOrordernum)) {
            $where["order_num|order_trade_no"] = $orderidOrordernum;
        } else {
            $where["order_id"] = $orderidOrordernum;
        }
        $order       = model("order");
        $orderObject = $order->where($where)->find();
        if ($orderObject) {
            $checkrs = $this->checkbackauth($orderObject->order_shop_id, $orderObject->order_store_id, $orderObject->order_user_id, $userObjectOruser_id, $password);
            if ($checkrs["code"] != 1) {
                return $checkrs;
            }
        } else {
            return ["code" => 0, "message" => "订单不存在", "data" => ""];
        }
        //__退款权限限制
        return ["code" => 1, "message" => "", "data" => ""];
    }
    //发起退款
    //纯粹退款
    public function doRefundRequest($orderidOrordernum, $cash = 0, $user_id = 0)
    {
        if (!preg_match("/^[0-9]{1,}$/", $orderidOrordernum)) {
            $where["order_num|order_trade_no"] = $orderidOrordernum;
        } else {
            $where["order_id"] = $orderidOrordernum;
        }
        $order    = model("order");
        $orderOne = $order->where($where)->find();
        if ($orderOne) {
            if ($cash == 0) {
                $cash = $orderOne->order_pay_realprice;
            }
            //默认全额退款
            if ($cash > $orderOne->order_pay_realprice) {
                return ["code" => 0, "message" => "可退金额不足", "data" => ""];
            }
            if (!in_array($orderOne->order_status, [100, 101])) {
                return ["code" => 0, "message" => "当前状态不可退款", "data" => ""];
            }
            $channel = $this->payWayTranslate($orderOne->order_channel_id);
            //统一返回格式
            $returnData = [
                "channel"    => $channel,
                "refund_fee" => 0, //退款总金额
            ];
            // if ($orderOne->order_status == 0) //新订单
            // {
            //     $returnData = [
            //         "channel"      => $channel,
            //     ];
            //     return ["code" => 0, "message" => "ok", "data" => $returnData];
            // }
            // if ($orderOne->order_status == 400||$orderOne->order_status == 200) //订单已关闭
            // {
            //     $returnData = [
            //         "channel"      => $channel,
            //         "trade_status" => $orderOne->order_status,
            //         "total_amount" => $orderOne->order_total_amount,
            //     ];
            //     return ["code" => 1, "message" => "订单已关闭", "data" => $returnData];
            // }
            //统一编制返回的数据
            //code 以支付宝为准，完全失败统一为0
            $tongyiRsData = function ($channel, $code, $rs_source, $orderOne, $user_id) use ($returnData, &$cash) {
                $message = "";
                switch ($channel) {
                    case 'face_alipay':
                    case 'wap_alipay':
                    case 'jsapi_alipay':
                        switch (floatval($code)) {
                            //https://docs.open.alipay.com/194/105170
                            case 10000: //通信成功
                                if ($rs_source["fund_change"] == "Y") //本次退款是否发生了资金变化
                                {
                                    $code                     = 1;
                                    $returnData["refund_fee"] = $cash;
                                    //$returnData["other"]      = $rs_source;//不知道干什么用的，造成接口多返回了数据，先关闭掉， 2017-11-22 09:53:22
                                    $message = "退款成功";
                                } else {
                                    $code    = 0;
                                    $message = "已经退过款了";
                                }
                                break;
                            default:
                                $code    = 0;
                                $message = isset($rs_source["sub_msg"]) ? trim($rs_source["sub_msg"]) : trim($rs_source["message"]);
                                break;
                        }
                        break;
                    case 'face_wxpay':
                    case 'wap_wxpay':
                    case 'jsapi_wxpay':
                        switch ($code) {
                            //https://pay.weixin.qq.com/wiki/doc/api/micropay.php?chapter=9_4
                            case "SUCCESS": //通信成功
                                if ($rs_source["result_code"] == "SUCCESS") //SUCCESS/FAIL,SUCCESS退款申请接收成功，结果通过退款查询接口查询,FAIL 提交业务失败
                                {
                                    $code                     = 1;
                                    $returnData["refund_fee"] = $cash;
                                    //$returnData["other"]      = $rs_source;//不知道干什么用的，造成接口多返回了数据，先关闭掉， 2017-11-22 09:53:22
                                    $message = "退款成功";
                                } else {
                                    $code    = 0;
                                    $message = $rs_source["err_code"] . $rs_source["err_code_des"];
                                }
                                break;
                            default: //通信失败
                                $code    = 0;
                                $message = $rs_source["return_msg"];
                                break;
                        }
                        break;
                    default:
                        $code    = 0;
                        $message = "退款数据未处理" . $channel;
                        break;
                }
                //修改订单状态
                //保存成功日志
                if ($code == 1) {
                    if (round($orderOne->order_pay_realprice - $cash, 2) > 0) {
                        $order_status = 101;
                    } else {
                        $order_status = 200; //全部退完了
                    }
                    $otherArray                        = $rs_source;
                    $otherArray["user_id"]             = $user_id; //操作者user_id
                    $otherArray["order_pay_log_data1"] = $cash; //退款金额
                    model("order")->orderStatusChange($orderOne->order_num, round($orderOne->order_pay_realprice - $cash, 2), "", $channel, $order_status, $otherArray);
                    //这里应该单独开个table记录每一次的退款记录
                }
                //__修改订单状态
                return ["code" => $code, "message" => $message, "data" => $returnData];
            };
            switch ($channel) {
                case 'face_alipay':
                case 'wap_alipay':
                case 'jsapi_alipay':
                    $shop_alipay_app_auth_token = model("shop")->where(["shop_id" => $orderOne->order_shop_id])->value("shop_alipay_app_auth_token");
                    $result                     = model("alipay")->request("AlipayTradeRefundRequest", [
                        "out_trade_no"   => $orderOne->order_num,
                        "refund_amount"  => $cash,
                        "out_request_no" => time(), //标识一次退款请求，同一笔交易多次退款需要保证唯一，如需部分退款，则此参数必传。
                        "operator_id"    => $user_id, //
                    ], $shop_alipay_app_auth_token);
                    break;
                case 'face_wxpay':
                case 'wap_wxpay':
                case 'jsapi_wxpay':
                    $rsObject = model("wxpay")->refund([
                        "out_trade_no"  => $orderOne->order_num,
                        "sub_mch_id"    => $orderOne->order_shop_mch_id,
                        "total_amount"  => $orderOne->order_total_amount,
                        "operator_id"   => $user_id, //
                        "refund_amount" => $cash, //退款金额
                        "out_refund_no" => time(), //标识一次退款请求，同一笔交易多次退款需要保证唯一，如需部分退款，则此参数必传。
                    ], true);
                    $result         = json_decode(json_encode($rsObject, JSON_UNESCAPED_UNICODE), 1);
                    $result["code"] = $rsObject->return_code;
                    break;
                default:
                    return ["code" => 0, "message" => "未知的退款类型" . $channel, "data" => ""];
                    break;
            }
            //return ["code" => 1, "message" => $result, "data" => ""];
            return $tongyiRsData($channel, ($result["code"]), $result, $orderOne, $user_id);
        } else {
            return ["code" => 0, "message" => "本地订单不存在", "data" => ""];
        }
    }
}
