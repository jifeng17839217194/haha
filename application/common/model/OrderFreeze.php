<?php
namespace app\common\model;

use think\Model;

/**
 * 支付宝预授权
 * https://docs.open.alipay.com/318/106381/
 */
class OrderFreeze extends Model
{
    protected $type = [
        //'sysconfig' => 'object',
        'order_freeze_addtime' => 'timestamp',
        'order_freeze_unfree_time' => 'timestamp',
    ];
    protected $autoWriteTimestamp = false;
    //关联
    public function profile()
    {
        //return $this->belongsTo('Province','city_province_id')->field('province_name');
    }
    //创建预授权的订单
    //返回 ["code"=>1,"message"=>"","data"=>[order object]]
    public function createOrder($paramArray)
    {
        if (!is_numeric($paramArray["user_id"])) {
            return ["code" => 0, "message" => "user_id 错误", "data" => ""];
        }
        if (!is_numeric($paramArray["total_amount"])) {
            return ["code" => 0, "message" => "收款金额错误", "data" => ""];
        }
        if ($paramArray["total_amount"] > 9999999) {
            return ["code" => 0, "message" => "收款金额超限", "data" => ""];
        }
        if (empty($paramArray["auth_code"])) {
            return ["code" => 0, "message" => "付款码不可为空", "data" => ""];
        }
        //根据操作员 user_id ,获取店铺等数据
        $user_id       = $paramArray["user_id"];
        $user_store_id = model("user")->where(["user_id" => $user_id])->field(true)->value("user_store_id");
        $storeOne      = model("store")->where(["store_id" => $user_store_id])->field(true)->find();
        $shopOne       = model("shop")->where(["shop_id" => $storeOne->store_shop_id])->find();
        //__操作员 user_id 检测
        //完整性判断
        if (empty($shopOne->shop_alipay_app_auth_token)) {
            return ["code" => 0, "message" => $shopOne->shop_name . "还未扫描授权二维码", "data" => ""];
        }
        if (empty($shopOne->shop_alipay_seller_id)) {
            return ["code" => 0, "message" => $shopOne->shop_name . "描授权信息中的user_id为空，请重新授权", "data" => ""];
        }

        if ($storeOne->store_open_funds_authorized!=1) {
            return ["code" => 0, "message" => "没有开通预授权功能", "data" => ""];
        }

        //__完整性判断
        //设置默认标题
        if (empty($paramArray["subject"])) {
            $paramArray["subject"] = $storeOne->store_name . "(当面资金授权)";
        }
        $savedata['order_freeze_user_id']      = $paramArray["user_id"];
        $savedata['order_freeze_addtime']      = time();
        $savedata['order_freeze_subject']      = $paramArray["subject"];
        $savedata['order_freeze_total_amount'] = floatval($paramArray["total_amount"]);
        $savedata['order_freeze_auth_code']    = $paramArray["auth_code"];
        $savedata['order_freeze_num']          = "ZZF" . time();
        $savedata['order_freeze_shop_id']      = $shopOne->shop_id;
        $savedata['order_freeze_store_id']     = $storeOne->store_id;
        $savedata['order_freeze_app_user_id']  = $shopOne->shop_alipay_seller_id;
        $this->data($savedata)->isUpdate(false)->save();
        //再次更新订单号
        $this->order_freeze_num = $this->order_freeze_num . $this->order_freeze_id;
        $this->save();
        //__再次更新订单号
        return ["code" => 1, "message" => "", "data" => $this];
    }
    //资金授权冻结
    //https://docs.open.alipay.com/318/106384/
    public function dofreeze($orderObject)
    {
        $alipay = model("alipay");
        //发起预授权
        $data["out_order_no"]   = $orderObject->order_freeze_num;
        $data["out_request_no"] = $orderObject->order_freeze_num;
        $data["auth_code"]      = $orderObject->order_freeze_auth_code;
        $data["auth_code_type"] = "bar_code";
        $data["order_title"]    = msubstr($orderObject->order_freeze_subject, 0, 50);
        $data["amount"]         = $orderObject->order_freeze_total_amount;
        $data["payee_user_id"]  = $orderObject->order_freeze_app_user_id;
        $data["pay_timeout"]    = "2m"; //该笔订单允许的最晚付款时间，逾期将关闭该笔订单
        $data["product_code"]   = "PRE_AUTH";
        //echo json_encode(["code"=>0,"message"=>"","data"=>$data]);die();
        $shopOne         = model("shop")->where(["shop_id" => $orderObject->order_freeze_shop_id])->find();
        $resultCodeArray = $alipay->request("AlipayFundAuthOrderFreezeRequest", $data, $shopOne->shop_alipay_app_auth_token);
        //标准返回以下6项
        $returnData["freeze_num"]   = $orderObject->order_freeze_num; //商户的订单号
        $returnData["status"]       = ""; //
        $returnData["total_amount"] = $orderObject->order_freeze_total_amount; //订单金额
        $returnData["pay_time"]     = ""; //支付日期
        //__标准返回以下4项
        switch ($resultCodeArray["code"]) {
            case 10000: //通信成功 授权成功
                switch ($resultCodeArray["status"]) {
                    case 'SUCCESS': //完成
                        $orderObject->order_freeze_status       = 100;
                        $orderObject->order_freeze_auth_no      = $resultCodeArray["auth_no"];
                        $orderObject->order_freeze_operation_id = $resultCodeArray["operation_id"];
                        $orderObject->order_freeze_pay_time     = strtotime($resultCodeArray["gmt_trans"]);
                        $orderObject->save();
                        $message                = "授权成功";
                        $returnData["status"]   = 100;
                        $returnData["pay_time"] = strtotime($resultCodeArray["gmt_trans"]);
                        break;
                    case 'CLOSED': //关闭
                        $orderObject->order_freeze_status = 200;
                        $returnData["status"]             = 200;
                        $orderObject->save();
                        $message = ($resultCodeArray["sub_msg"] ?: $resultCodeArray["msg"]);
                        break;
                    case 'INIT': //初始
                        $returnData["status"] = 0; //
                        $message              = "初始";
                        break;
                    default:
                        # code...
                        break;
                }
                return ["code" => 1, "message" => $message, "data" => $returnData];
                break;
            case 40004: //授权失败 记录授权结果并在终端显示错误信息（display_message)。
                $orderObject->order_freeze_status = 400;
                $orderObject->save();
                $returnData["status"] = 400;
                return ["code" => 1, "message" => ($resultCodeArray["sub_msg"] ?: $resultCodeArray["msg"]), "data" => $returnData];
                break;
            case 10003:
            case 20000:
                //10003 发起轮询流程：等待5秒后调用资金授权操作查询接口alipay.fund.auth.operation.detail.query通过授权时传入的商户授权订单号(out_order_no)和资金操作流水号(out_request_no)查询授权结果（返回参数status），如果仍然返回等待用户授权（INIT），则再次等待5秒后继续查询，直到返回确切的授权结果（成功SUCCESS 或 已关闭CLOSED），或是超出轮询时间（建议轮询时间为30s）。在最后一次查询仍然返回等待用户授权的情况下，必须立即调用资金授权撤销接口alipay.fund.auth.operation.cancel将这笔授权操作撤销，避免用户继续操作。
                //20000 调用查询接口确认授权结果，详见异常处理。
                if (isset($resultCodeArray["auth_no"])) {
                    $orderObject->order_freeze_auth_no = $resultCodeArray["auth_no"];
                }
                if (isset($resultCodeArray["operation_id"])) {
                    $orderObject->order_freeze_operation_id = $resultCodeArray["operation_id"];
                }
                $orderObject->order_freeze_status = 600;
                $orderObject->save();
                $returnData["status"] = 600;
                //加入任务池
                innerHttpsPost("cmd/innerrequest/addscheduledtasks", [
                    "scheduled_tasks_title"         => "支付宝预授权结果查询",
                    "scheduled_tasks_start_time"    => time(), //马上开始
                    "scheduled_tasks_end_time"      => time() + 5 * 6, //只查询30秒,超时结束掉订单
                    "scheduled_tasks_time_interval" => json_encode([5], JSON_UNESCAPED_UNICODE), //间隔5秒
                    "scheduled_tasks_name"          => "check_alipay_order_freeze_status", //检测预授权订单的状态
                    "scheduled_tasks_param"         => json_encode(["out_trade_no" => $orderObject->order_freeze_num], JSON_UNESCAPED_UNICODE),
                ]);
                //__加入任务池
                return ["code" => 1, "message" => "等待用户完成支付", "data" => $returnData];
                break;
            default:
                $returnData["status"] = $resultCodeArray["code"];
                return ["code" => 1, "message" => ($resultCodeArray["sub_msg"] ?: $resultCodeArray["msg"]), "data" => $returnData];
        }
    }



    /*
    解冻前的权限判断测试
    */
    public function dounfreezeBefaultCheck($order_freeze_num, $user_id, $password)
    {
        
        $where["order_freeze_num"] = $order_freeze_num;
        
        $orderObject = $this->where($where)->find();

        if ($orderObject) {
            $checkrs = model("pay")->checkbackauth($orderObject->order_freeze_shop_id,$orderObject->order_freeze_store_id,$orderObject->order_freeze_user_id,$user_id,$password);
            if($checkrs["code"]!=1)
            {
                return $checkrs;
            }
        } else {
            return ["code" => 0, "message" => "订单不存在", "data" => ""];
        }

        //__退款权限限制
        return ["code" => 1, "message" => "", "data" => ""];
    }
    


    //解冻
    //当资金授权发生之后一段时间内，由于买家或者商家等其他原因需要要解冻资金，商家可通过资金授权解冻接口将授权资金进行解冻，支付宝将在收到解冻请求并验证成功后，按解冻规则将冻结资金按原路进行解冻
    //$total_amount 真实消费金额
    public function dounfreeze($orderidOrordernumisObj = '', $user_id, $total_amount = 0)
    {

        if (is_object($orderidOrordernumisObj)) {
            $orderFreezeObject = $orderidOrordernumisObj;
        } else {

            if (!preg_match("/^[0-9]{1,}$/", $orderidOrordernumisObj)) {
                $where["order_freeze_num|order_freeze_trade_no"] = $orderidOrordernumisObj;
            } else {
                $where["order_freeze_id"] = $orderidOrordernumisObj;
            }
            $orderFreezeObject = $this->where($where)->find();
        }


        $returnData["status"]  = $orderFreezeObject->order_freeze_status;
        $returnData["message"] = "";

        if ($orderFreezeObject->order_freeze_status != 100) {
            return ["code" => 0, "message" => "当前状态不可操作", "data" => ["status"=>$orderFreezeObject->order_freeze_status]];
        }

        if (floatval($total_amount) > $orderFreezeObject->order_freeze_total_amount) {
            return ["code" => 0, "message" => "解冻金额不可大于已冻结金额", "data" => ""];
        }

        $shopOne  = model("shop")->where(["shop_id" => $orderFreezeObject->order_freeze_shop_id])->find();
        $storeOne = model("store")->where(["store_id" => $orderFreezeObject->order_freeze_store_id])->find();

        /**
         * [$fununfreeze description]
         * $total_amount  还给消费者的钱的金额
         * @var [type]
         */
        $fununfreeze = function ($total_amount) use (&$orderFreezeObject, &$storeOne, &$returnData, &$shopOne) {
            $data["auth_no"]        = $orderFreezeObject->order_freeze_auth_no;
            $data["out_request_no"] = $orderFreezeObject->order_freeze_auth_no;
            $data["amount"]         = $total_amount;
            $data["remark"]         = $storeOne->store_name . "(当面资金授权解冻)";
            $resultCodeArray        = model("alipay")->request("AlipayFundAuthOrderUnfreezeRequest", $data, $shopOne->shop_alipay_app_auth_token);
            switch ($resultCodeArray["code"]) {
                case 10000: //成功
                    if ($orderFreezeObject->order_freeze_status == 100) {
                        $orderFreezeObject->order_freeze_status         = 300;
                        $orderFreezeObject->order_freeze_operation_type = "UNFREEZE";
                        $orderFreezeObject->order_freeze_unfree_time    = strtotime($resultCodeArray["gmt_trans"]);
                        $orderFreezeObject->save();
                    }
                    $returnData["status"]  = 300;
                    $returnData["message"] = "资金授权已解冻,交易完成";

                    return ["code" => 1, "message" => "", "data" => $returnData];
                    break;
                default:
                    $returnData["status"] = $resultCodeArray["code"];
                    return ["code" => 1, "message" => ($resultCodeArray["sub_msg"] ?: $resultCodeArray["msg"]), "data" => $returnData];
            }
        };

        if ($total_amount > 0) {
            //授权资金解冻转支付给卖家
            $orderObj = model("order")->createOrder(["channel" => "face_alipay", "user_id" => $user_id, "total_amount" => $total_amount, "subject" => $storeOne->store_name . "(冻结转支付)", "product_code" => "PRE_AUTH", "auth_code" => $orderFreezeObject->order_freeze_auth_no, "create_where" => "pc"]);
            if ($orderObj["code"] == 0) {
                return $orderObj; //正常是不会出现这个状态的，so，没有接收处理  2018-1-20 18:23:24
            } else {
                $payrs = model("pay")->dopay($orderObj["data"]["order_id"]);
                if ($payrs["data"]["trade_status"] == 100) {
                    if (floatval($total_amount) < $orderFreezeObject->order_freeze_total_amount) //解除剩余的金额
                    {
                        $unfreezers = $fununfreeze(bcsub($orderFreezeObject->order_freeze_total_amount, floatval($total_amount), 2));
                    } else {
                        //关闭当前的预授权订单
                        $orderFreezeObject->order_freeze_status         = 300;
                        $orderFreezeObject->order_freeze_operation_type = "UNFREEZE";
                        $orderFreezeObject->order_freeze_unfree_time    = time();
                        $orderFreezeObject->save();
                        $unfreezers["code"]    = 1;
                        $unfreezers["message"] = "";
                    }
                    //$payrs["data"]["printhtml"] = model("printorder")->printTmp($payrs["data"]["order_num"]);
                }
                $unfreezers["data"]["pay"] = $payrs["data"];
                return $unfreezers;
            }
            //__授权资金解冻转支付给卖家
        } else {
            //全额解冻
            return $fununfreeze($orderFreezeObject->order_freeze_total_amount);
        }

    }

    //资金授权操作查询接口 alipay.fund.auth.operation.detail.query
    //统一收单线下交易查询(通常只给服务器内部查询)
    //含远程查询
    //参数只允许order_id 或 order_freeze_num (目前只自动识别这二个)
    //先本地数据库查询，不是成功状态就到api查询
    public function doquery($orderidOrordernum, $onlylocationdb = false)
    {
        if (!preg_match("/^[0-9]{1,}$/", $orderidOrordernum)) {
            $where["order_freeze_num|order_freeze_trade_no"] = $orderidOrordernum;
        } else {
            $where["order_freeze_id"] = $orderidOrordernum;
        }
        if(!($orderObject = $this->where($where)->find()))
        {
            return ["code"=>0,"message"=>"订单不存在","data"=>""];
        }

        //回调参数
        $returnData["status"]         = $orderObject->order_freeze_status;
        $returnData["message"]        = "";
        $returnData["operation_type"] = $orderObject->order_freeze_operation_type;
        $returnData["pay_time"]       = $orderObject->order_freeze_pay_time;
        $returnData["operation_id"]   = $orderObject->order_freeze_operation_id;
        $returnData["auth_no"]        = $orderObject->order_freeze_auth_no;

        //__回调参数

        //只要求本地查询,终止后面的远程查询
        if ($onlylocationdb) {
            return ["code" => 1, "message" => "", "data" => $returnData];
        }
        switch ($orderObject->order_freeze_status) {
            case 200:
                $returnData["message"] = "授权已关闭";
                return ["code" => 1, "message" => "", "data" => $returnData];
                break;
            case 0:
                $returnData["message"] = "新订单，授权未完成";
                return ["code" => 1, "message" => "", "data" => $returnData];
                break;
            case 400:
                $returnData["message"] = "授权失败";
                return ["code" => 1, "message" => "", "data" => $returnData];
                break;
            default:
                # code...
                break;
        }
        $alipay               = model("alipay");
        $shopOne              = model("shop")->where(["shop_id" => $orderObject->order_freeze_shop_id])->find();
        $data["auth_no"]      = $orderObject->order_freeze_auth_no;
        $data["operation_id"] = $orderObject->order_freeze_operation_id;
        $resultCodeArray      = $alipay->request("AlipayFundAuthOperationDetailQueryRequest", $data, $shopOne->shop_alipay_app_auth_token);

        switch ($resultCodeArray["code"]) {
            case 10000: //接口调用成功，调用结果请参考具体的API文档所对应的业务返回参数

                switch ($resultCodeArray["status"]) {
                    case 'SUCCESS': //完成

                        $returnData["operation_type"] = $resultCodeArray["operation_type"];
                        $returnData["status"]         = 100;
                        $returnData["message"]        = "成功";
                        $returnData["pay_time"]       = strtotime($resultCodeArray["gmt_trans"]);
                        $returnData["operation_id"]   = $resultCodeArray["operation_id"];
                        $returnData["auth_no"]        = $resultCodeArray["auth_no"];

                        break;
                    case 'CLOSED': //关闭
                        $returnData["operation_type"] = $resultCodeArray["operation_type"];
                        $returnData["status"]         = 400;
                        $returnData["message"]        = "已关闭";

                        break;
                    case 'INIT': //初始,其实这里没有变化（可能等待用户输入支付密码）

                        $returnData["operation_type"] = $resultCodeArray["operation_type"];
                        $returnData["status"]         = 0;
                        $returnData["message"]        = "新订单";

                        break;
                    default:
                        # code...
                        break;
                }
                return ["code" => 1, "message" => "", "data" => $returnData];
                break;
            case 40004: //授权失败 记录授权结果并在终端显示错误信息（display_message)。
                $returnData["operation_type"] = $resultCodeArray["operation_type"];
                $returnData["status"]         = 400;
                $returnData["message"]        = ($resultCodeArray["sub_msg"] ?: $resultCodeArray["msg"]);

                return ["code" => 1, "message" => ($resultCodeArray["sub_msg"] ?: $resultCodeArray["msg"]), "data" => $returnData];
                break;
            case 10003:
            case 20000:
                if (isset($resultCodeArray["auth_no"])) {
                    $returnData["auth_no"] = $resultCodeArray["auth_no"];
                }
                if (isset($resultCodeArray["operation_id"])) {
                    $returnData["operation_id"] = $resultCodeArray["operation_id"];
                }
                $returnData["operation_type"] = $resultCodeArray["operation_type"];
                $returnData["status"]         = 600;
                $returnData["message"]        = ($resultCodeArray["sub_msg"] ?: $resultCodeArray["msg"]);

                return ["code" => 1, "message" => ($resultCodeArray["sub_msg"] ?: $resultCodeArray["msg"]), "data" => $returnData];
                break;
            default:
                $returnData["status"] = $resultCodeArray["code"];
                return ["code" => 1, "message" => ($resultCodeArray["sub_msg"] ?: $resultCodeArray["msg"]), "data" => $returnData];
        }
    }

    //$resultCodeArray 来自 doquery
    public function orderFreezeStatusChange($orderidOrordernumisObj = '', $resultCodeArray = [])
    {
        if (is_object($orderidOrordernumisObj)) {
            $orderObject = $orderidOrordernumisObj;
        } else {

            if (!preg_match("/^[0-9]{1,}$/", $orderidOrordernumisObj)) {
                $where["order_freeze_num|order_freeze_trade_no"] = $orderidOrordernumisObj;
            } else {
                $where["order_freeze_id"] = $orderidOrordernumisObj;
            }
            $orderObject = $this->where($where)->find();
        }
        switch ($resultCodeArray["status"]) {
            case 100:

                if ($orderObject->order_freeze_status != 100 || $resultCodeArray["operation_type"] != $orderObject->order_freeze_operation_type) {
                    $orderObject->order_freeze_status         = 100;
                    $orderObject->order_freeze_operation_type = $resultCodeArray["operation_type"];
                    $orderObject->order_freeze_auth_no        = $resultCodeArray["auth_no"];
                    $orderObject->order_freeze_operation_id   = $resultCodeArray["operation_id"];
                    $orderObject->order_freeze_pay_time       = $resultCodeArray["pay_time"];
                    $orderObject->save();
                }

                break;
            case 400:

                if ($orderObject->order_freeze_status != 400 || $resultCodeArray["operation_type"] != $orderObject->order_freeze_operation_type) {
                    $orderObject->order_freeze_status         = 400;
                    $orderObject->order_freeze_operation_type = $resultCodeArray["operation_type"];
                    $orderObject->save();
                }

                break;
            case 600:

                if ($orderObject->order_freeze_status != 600 || $resultCodeArray["operation_type"] != $orderObject->order_freeze_operation_type) {
                    if (isset($resultCodeArray["auth_no"])) {
                        $orderObject->order_freeze_auth_no = $resultCodeArray["auth_no"];
                    }
                    if (isset($resultCodeArray["operation_id"])) {
                        $orderObject->order_freeze_operation_id = $resultCodeArray["operation_id"];
                    }
                    $orderObject->order_freeze_operation_type = $resultCodeArray["operation_type"];
                    $orderObject->order_freeze_status         = 600;
                    $orderObject->save();
                }
                break;

            default:
                # code...
                break;
        }

    }

    //资金授权撤销接口 alipay.fund.auth.operation.cancel
    //只有商户由于业务系统处理超时需要终止后续业务处理或者授权结果未知时可调用撤销
    //https://docs.open.alipay.com/api_28/alipay.fund.auth.operation.cancel/
    public function docancel($orderidOrordernumisObj)
    {
        if (is_object($orderidOrordernumisObj)) {
            $orderObject = $orderidOrordernumisObj;
        } else {

            if (!preg_match("/^[0-9]{1,}$/", $orderidOrordernumisObj)) {
                $where["order_freeze_num|order_freeze_trade_no"] = $orderidOrordernumisObj;
            } else {
                $where["order_freeze_id"] = $orderidOrordernum;
            }
            $orderObject = $this->where($where)->find();
        }
        
        $returnData["status"]  = $orderObject->order_freeze_status;
        $returnData["message"] = "";
        if ($orderObject->order_freeze_status != 600 && $orderObject->order_freeze_status != 0) {
            return ["code" => 0, "message" => "当前状态不可撤销", "data" => ["status" => $orderObject->order_freeze_status]];
        } else {
            $alipay                 = model("alipay");
            $shopOne                = model("shop")->where(["shop_id" => $orderObject->order_freeze_shop_id])->find();
            $data["auth_no"]        = $orderObject->order_freeze_auth_no;
            $data["out_order_no"]   = $orderObject->order_freeze_num;
            $data["operation_id"]   = $orderObject->order_freeze_operation_id;
            $data["out_request_no"] = $orderObject->order_freeze_operation_id;
            $data["remark"]         = "授权撤销";
            $resultCodeArray        = $alipay->request("AlipayFundAuthOperationCancelRequest", $data, $shopOne->shop_alipay_app_auth_token);
            switch ($resultCodeArray["code"]) {
                case 10000: //撤销成功
                    if ($orderObject->order_freeze_status != 200) {
                        $orderObject->order_freeze_status = 200;
                        $orderObject->save();
                    }
                    $returnData["status"]  = 200;
                    $returnData["message"] = "授权已撤销";
                    return ["code" => 1, "message" => "", "data" => $returnData];
                    break;
                default:
                    $returnData["status"] = $resultCodeArray["code"];
                    return ["code" => 0, "message" => ($resultCodeArray["sub_msg"] ?: $resultCodeArray["msg"]), "data" => $returnData];
                    break;
            }
        }
    }
}
