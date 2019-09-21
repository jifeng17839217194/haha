<?php
namespace app\cmd\controller;

use think\Controller;

//内部请求专用
//无返回值/数据(因为是异步执行)
class Innerrequest extends Controller
{
    public function _initialize()
    {
        ignore_user_abort(true); //如果客户端断开连接，不会引起脚本abort.
        set_time_limit(0);
        $param = $_POST;
        if (isset($param["sign"])) {
            if (!checkParamEncryption(config("inner_post_secret"), $param)) {
                echo "sign签名验证错误";
                trace("sign签名验证错误");
                die();
            }
        } else {
            if (time() > strtotime("2018-1-31")) {
                echo "sign参数不存在";
                trace("sign参数不存在", "error");
                die();
            }
        }
        //echo "somethingforcult";
    }

    /**
     * 轮询进件的审核结果
     * @return [type] [description]
     */
    public function updateJinjianStatus()
    {
        model("jinjian")->updateStatus();
    }


    //goeasy 推送,goeasy 的API必需要“同步”的cult，这里是同步的，上层再嵌套一个异步的
    public function goeasypush()
    {
        $url = "http://rest-hangzhou.goeasy.io/publish";
        //异步推送，没有返回值
        echo httpsPost($url, $_POST, false); //这里可以根据返回值，做状态检测(但没有做，2017年12月19日16:59:51)
    }

    //给点餐小程序，做服务器异步推送
    public function push2xcxserver()
    {
        //trace("xcxcjh:".$_POST["parpam"],"debug");
        $parpam                    = json_decode($_POST["parpam"], 1);
        $url                       = config("xiao_cheng_xu_notice_url");
        $postdata["order_num"]     = $parpam["order_num"];
        $postdata["channel"]       = model("pay")->payWayTranslate($parpam["order_channel_id"]);
        $postdata["trade_status"]  = $parpam["order_status"];
        $postdata["total_amount"]  = $parpam["order_total_amount"];
        $postdata["pay_realprice"] = $parpam["order_pay_realprice"];
        $postdata["time"]          = time();
        //trace("============","debug");
        //trace($postdata,"debug");
        //trace("============","debug");
        $postdata["sign"] = publicRequestjiami($postdata, db("user")->where(["user_id" => $parpam["order_user_id"]])->value("user_token"));
        //异步推送，没有返回值
        httpsPost($url, $postdata, false); //这里可以根据返回值，做状态检测(但没有做，2017年12月19日16:59:51)
    }

    //极光的异步推送
    public function jiguangpush()
    {
        $push = model("push");
        $push->pushDiyMsg($_POST["registrationid"], json_decode($_POST["parpam"], 1));
    }

    //新增计划任务请求
    public function addscheduledtasks()
    {
        //只接受post过来的数据
        model("scheduled_tasks")->addOne($_POST);
        //上传中
    }

    //微信、支付宝订单状态查询(支付后，未有直接结果时的，数据查询)
    //来自计划任务的请求
    public function checkpayorderstatus()
    {
        $out_trade_no        = input("out_trade_no");
        $scheduled_tasks_id  = input("scheduled_tasks_id");
        $scheduled_tasks     = model("scheduled_tasks");
        $scheduled_tasks_one = $scheduled_tasks->where(["scheduled_tasks_id" => $scheduled_tasks_id])->find();
        //trace("进入任务开始时间：".date("Y-m-d H:i:s",time()),"debug");
        if ($scheduled_tasks_one) {
            $scheduled_tasks_one->scheduled_tasks_status = "doing"; //改完执行中
            $scheduled_tasks_one->save();

            $pay = model("pay");
            $rs  = $pay->tradeQueryRequest($out_trade_no);
            //trace("============CJH", "debug");
            //trace($out_trade_no, "debug");
            //trace($rs, "debug");
            $scheduled_tasks_status = "realy"; //任务结束

            if ($rs["code"] == 1) {
                $order             = model("order");
                $orderOne          = $order->where(["order_num" => $out_trade_no])->field(true)->find();
                $this_trade_status = floatval($rs["data"]["trade_status"]);

                if ($this_trade_status != $orderOne->order_status) //状态变化
                {

                    switch ($this_trade_status) {
                        case 100: //支付成功
                            //trace("状态有变更".$this_trade_status.":".$orderOne->order_status,"debug");
                            $scheduled_tasks_status          = "end"; //任务结束
                            $rs_source                       = $rs["data"]["rs_source"];
                            $rs_source["order_pay_log_from"] = "sync";

                            switch ($scheduled_tasks_one->scheduled_tasks_name) {
                                case 'check_wxpay_order_status':
                                    //$total_fee = _bcdiv($rs_source["total_fee"],100,2);
                                    $total_fee = round($rs_source["total_fee"] / 100, 2);
                                    break;

                                case 'check_alipay_order_status':
                                    $total_fee = $rs_source["total_amount"];
                                    break;
                                default:
                                    # code...
                                    break;
                            }

                            $order->orderStatusChange($out_trade_no, $total_fee, $rs_source["transaction_id"], $pay->payWayTranslate($orderOne->order_channel_id), $this_trade_status, $rs_source);
                            break;

                        case 101: //全部退款
                        case 200: //全部退款
                        case 400: //支付失败
                            $scheduled_tasks_status          = "end"; //任务结束
                            $rs_source["order_pay_log_from"] = "sync";
                            $order->orderStatusChange($out_trade_no, 0, "", $pay->payWayTranslate($orderOne->order_channel_id), $this_trade_status, $rs_source);
                            break;
                        default:
                            $rs_source["order_pay_log_from"] = "sync";
                            $order->orderStatusChange($out_trade_no, 0, "", $pay->payWayTranslate($orderOne->order_channel_id), $this_trade_status, $rs_source);
                            break;
                    }
                }

                $scheduled_tasks_time_interval = json_decode($scheduled_tasks_one->scheduled_tasks_time_interval, 1)[0];
                //trace("执行的时间间隔是：".$scheduled_tasks_time_interval,"debug");
                //trace("预计下次执行时间：".date("Y-m-d H:i:s",bcadd(time() , $scheduled_tasks_time_interval)),"debug");
                //以下是查询了24次，还没有支付，或支付状态不正常，就结束掉订单
                if (
                    (
                        (time() + $scheduled_tasks_time_interval >= $scheduled_tasks_one->scheduled_tasks_end_time && $scheduled_tasks_one->scheduled_tasks_end_time > 0)
                        ||
                        (($scheduled_tasks_one->scheduled_tasks_times_this + 1) >= $scheduled_tasks_one->scheduled_tasks_times_limit && $scheduled_tasks_one->scheduled_tasks_times_limit > 0)
                    )
                    && $scheduled_tasks_status != "end"
                ) {
                    if (in_array($this_trade_status, [500, 600])) {
                        $thismodename = "";
                        switch ($scheduled_tasks_one->scheduled_tasks_name) {
                            case 'check_wxpay_order_status':
                                $thismodename = "wxpay";
                                //撤销订单
                                $rsArray = model($thismodename)->reverseOrder(["out_trade_no" => $out_trade_no, "payment" => ['sub_merchant_id' => $orderOne->order_shop_mch_id]]);
                                break;

                            case 'check_alipay_order_status':
                                $thismodename = "alipay";
                                if ($this_trade_status == 500) {
                                    $rsArray = model($thismodename)->cancelOrder(["out_trade_no" => $out_trade_no, "order_shop_id" => $orderOne->order_shop_id]);
                                }
                                if ($this_trade_status == 600) //等待买家付款,可以关闭订单
                                {
                                    $rsArray = model($thismodename)->closeOrder(["out_trade_no" => $out_trade_no, "order_shop_id" => $orderOne->order_shop_id]);
                                }

                                break;
                            default:
                                # code...
                                break;
                        }

                        if ($rsArray["code"] == 1) {
                            $scheduled_tasks_status          = "end";
                            $rs_source["order_pay_log_from"] = "sync";
                            //将订单标示为已关闭
                            $order->orderStatusChange($out_trade_no, 0, "", $pay->payWayTranslate($orderOne->order_channel_id), 400, $rs_source);
                        } else {

                        }
                    }

                    if ($orderOne->order_status == 100 &&  $this_trade_status==100) // 本地已经100的状态了，但是“qs_scheduled_tasks”计划任务状态没有被改变，属于异常状态
                    {
                        $scheduled_tasks_status="end";//强制结束
                        trace("强制结束","debug");
                    }

                }

            }

        } else {
            $scheduled_tasks_status = "end"; //任务结束
            trace("订单不存在", "error");
        }

        $scheduled_tasks_one->scheduled_tasks_last_time = time(); //记录执行时间
        $scheduled_tasks_one->scheduled_tasks_times_this++; //次数加1
        $scheduled_tasks_one->scheduled_tasks_status = $scheduled_tasks_status;
        $scheduled_tasks_one->save();
        //trace("任务单次结束时间：".date("Y-m-d H:i:s",time()),"debug");
        //trace("===============================","debug");
    }

    //支付宝 预授权
    //来自计划任务的请求
    public function checkorderfreezestatus()
    {
        $out_trade_no        = input("out_trade_no");
        $scheduled_tasks_id  = input("scheduled_tasks_id");
        $scheduled_tasks     = model("scheduled_tasks");
        $scheduled_tasks_one = $scheduled_tasks->where(["scheduled_tasks_id" => $scheduled_tasks_id])->find();
        //trace("进入任务开始时间：".date("Y-m-d H:i:s",time()),"debug");
        if ($scheduled_tasks_one) {
            $scheduled_tasks_one->scheduled_tasks_status = "doing"; //改完执行中
            $scheduled_tasks_one->save();

            $order_freeze = model("order_freeze");
            $rs           = $order_freeze->doquery($out_trade_no);
            //print_r($rs);die();
            //trace($rs,"error");
            $scheduled_tasks_status = "realy"; //任务结束

            if ($rs["code"] == 1) {
//都是1的；

                $orderFreezeOne = $order_freeze->where(["order_freeze_num" => $out_trade_no])->field("", false)->find();
                $this_status    = floatval($rs["data"]["status"]);

                /*
                print_r(json_encode($orderFreezeOne));die();
                echo $this_status;
                echo "<br />";
                echo $orderFreezeOne->order_freeze_status;
                die();
                 */

                if ($this_status != $orderFreezeOne->order_freeze_status) //状态变化
                {
                    switch ($this_status) {
                        case 100: //支付成功
                        case 400: //失败
                            //trace("状态有变更".$this_status.":".$orderOne->order_status,"debug");
                            $scheduled_tasks_status = "end"; //任务结束
                            break;
                        default:
                            break;
                    }
                    $order_freeze->orderFreezeStatusChange($orderFreezeOne, $rs["data"]);
                }

                $scheduled_tasks_time_interval = json_decode($scheduled_tasks_one->scheduled_tasks_time_interval, 1)[0];
                //trace("执行的时间间隔是：".$scheduled_tasks_time_interval,"debug");
                //trace("预计下次执行时间：".date("Y-m-d H:i:s",bcadd(time() , $scheduled_tasks_time_interval)),"debug");
                //以下是查询了24次，还没有支付，或支付状态不正常，就结束掉订单
                if (
                    (
                        (time() + $scheduled_tasks_time_interval >= $scheduled_tasks_one->scheduled_tasks_end_time && $scheduled_tasks_one->scheduled_tasks_end_time > 0)
                        ||
                        (($scheduled_tasks_one->scheduled_tasks_times_this + 1) >= $scheduled_tasks_one->scheduled_tasks_times_limit && $scheduled_tasks_one->scheduled_tasks_times_limit > 0)
                    )
                    && $scheduled_tasks_status != "end"
                ) {
                    if (in_array($this_status, [0, 600])) {

                        $rsArray = $order_freeze->docancel($out_trade_no);

                        if ($rsArray["code"] == 1) {
                            $scheduled_tasks_status = "end";
                            $rs["data"]["status"]   = 400;
                            $order_freeze->orderFreezeStatusChange($orderFreezeOne, $rs["data"]);
                        } else {

                        }
                    }

                }

            }

        } else {
            $scheduled_tasks_status = "end"; //任务结束
            trace("订单不存在", "error");
        }

        $scheduled_tasks_one->scheduled_tasks_last_time = time(); //记录执行时间
        $scheduled_tasks_one->scheduled_tasks_times_this++; //次数加1
        $scheduled_tasks_one->scheduled_tasks_status = $scheduled_tasks_status;
        $scheduled_tasks_one->save();

        //trace("任务单次结束时间：".date("Y-m-d H:i:s",time()),"debug");
        //trace("===============================","debug");
    }
}
