<?php
namespace app\api\controller;

use app\api\controller\Apibase;

class Notifyurl extends Apibase
{
    public function _initialize()
    {
        // if (time() > 1501383645) {
        //     parent::_initialize();
        // }
    }

    public function index()
    {
        $channel = input("channel", "");
        switch ($channel) {
            case 'alipay':

                //支付宝的异步返回
                //dump(input("get."));die();
                if (input("post.out_trade_no") != "") {
                    //total_amount=2.00&buyer_id=2088102116773037&body=大乐透2.1&trade_no=2016071921001003030200089909&refund_fee=0.00&notify_time=2016-07-19 14:10:49&subject=大乐透2.1&sign_type=RSA2&charset=utf-8&notify_type=trade_status_sync&out_trade_no=0719141034-6418&gmt_close=2016-07-19 14:10:46&gmt_payment=2016-07-19 14:10:47&trade_status=TRADE_SUCCESS&version=1.0&sign=kPbQIjX+xQc8F0/A6/AocELIjhhZnGbcBN6G4MM/HmfWL4ZiHM6fWl5NQhzXJusaklZ1LFuMo+lHQUELAYeugH8LYFvxnNajOvZhuxNFbN2LhF0l/KL8ANtj8oyPM4NN7Qft2kWJTDJUpQOzCzNnV9hDxh5AaT9FPqRS6ZKxnzM=&gmt_create=2016-07-19 14:10:44&app_id=2015102700040153&seller_id=2088102119685838&notify_id=4a91b7a78a503640467525113fb7d8bg8e

                    $aop  = model("alipay")->requestBase();
                    $flag = $aop->rsaCheckV1($_POST, null, $_POST["sign_type"]);
                    //写入数据库 2017-3-1 19:12:01
                    if ($flag) {
                        $notify_id                      = $_POST["notify_id"]; //通知ID
                        $data["order_pay_notify_notify_id"] = $notify_id;
                        $data["order_pay_notify_from"]      = "alipay";
                        $data["order_pay_notify_order_num"] = input("post.out_trade_no");
                        $db_order_pay_notify                = db("order_pay_notify");
                        if (!$db_order_pay_notify->where($data)->find()) {
                            $db_order_pay_notify->insert($data);
                            $order_num                    = input("post.out_trade_no");
                            //$real_pay_cash                = input("post.buyer_pay_amount");//这里会有丢失红包的问题
                            $real_pay_cash                = input("post.receipt_amount");
                            $aboutid                      = input("post.trade_no");
                            $paramArray                   = $_POST;
                            $paramArray["order_pay_log_from"] = "asynchronous";
                            model("order")->orderStatusChange($order_num, $real_pay_cash, $aboutid, $channel, input("post.trade_status"), $paramArray);
                        }
                    }
                    //Log::record('aop=>',json_encode($flag));
                    echo "success";die();
                }

                break;
            case 'wxpay':
                //微信的异步通知
                $aop      = model("wxpay")->request();
                $response = $aop->payment->handleNotify(function ($notify, $successful) {
                    //$notify->total_fee

                    $data["order_pay_notify_notify_id"] = 0;
                    $data["order_pay_notify_from"]      = "wxpay";
                    $data["order_pay_notify_order_num"] = $notify->out_trade_no;
                    db("order_pay_notify")->insert($data);

                    $order_num                    = $notify->out_trade_no;
                    $aboutid                      = $notify->transaction_id;
                    $paramArray                   = $notify;
                    $paramArray["order_pay_log_from"] = "asynchronous";

                    if ($successful) {
                        //$real_pay_cash = _bcdiv($notify->total_fee, 100, 2);
                        $real_pay_cash = round($notify->total_fee/100, 2);
                        model("order")->orderStatusChange($order_num, $real_pay_cash, $aboutid, "wxpay", 100, $paramArray);
                    } else {
                        model("order")->orderStatusChange($order_num, 0, $aboutid, "wxpay", 400, $paramArray);
                    }

                    return true; // 或者错误消息
                });
                $response->send(); // Laravel 里请使用：return $response;

                break;
        }
    }
}
