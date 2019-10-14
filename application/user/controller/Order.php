<?php
namespace app\user\controller;

use think\Controller;
use think\Request;

class Order extends Controller
{
    public $request;
    public $wx_arr;
    public $ali_arr;

    public function _initialize()
    {
        $this->request = Request::instance();
        $this->wx_arr = [1002,1004,1006];
        $this->ali_arr = [1001,1003,1005,1007];
    }

    /**
     * 获取订单的数据
     * @param  [type] $user_id [description]
     * @return [type]          [description]
     *增加了sale_id（收银员） create：2018-2-5 17:19
     */
     public function list() {

        $user_id=input("user_id");
        $order_num = input("order_num","");
        $order_status = input("order_status","");
        $store_id = input("store_id","");
        $page = input("page",0);
        $per_page = input("per_page",20);
        $order_addtime_from = input("order_addtime_from","");
        $order_addtime_end = input("order_addtime_end","");
        $channel=input("channel","");
        $sale_id=input("sale_id",0);
        //店长、店老板看全部，收银员看自己
        //model("report")->getOrderList($user_id);
        $user     = model("user");
        $userOne  = $user->where(["user_id" => $user_id])->field(true)->find();
        $order    = model("order");
        $per_page = intval($per_page);
        if ($per_page > 100) {
            $per_page = 100;
        }
        /*
        *by
        *create date:2018-2-5 15:53
        *搜索关键词，不限制日期，如果传递有日期就放到里面
        */
        $order_date=false;
        if(empty($order_addtime_from)&&empty($order_addtime_end)){
            $order_date=true;
        }
        //最大每页100条；暂定100，没有其它约束

        if(!empty($order_addtime_end)&&!empty($order_addtime_from)){
            $Days = round((strtotime($order_addtime_end) - strtotime($order_addtime_from)) / 3600 / 24,2);
            if ($Days < 0) {
                return ["code" => 0, "message" => "开始日期不能大于结束日期", "data" => ""];
            } else {
                if ($Days > 31) {
                    return ["code" => 0, "message" => "日期间隔不能大于31天", "data" => ""];
                }
            }
        }

        //组合查询的条件
        $whereOrderSearch = [];

        //订单号
        if ($order_num) {
            $whereOrderSearch["order_num"] = ["like", "%" . $order_num . "%"];
            /*     
            *by
            *create date:2018-2-5 15:53
            *搜索关键词，不限制日期，如果传递有日期就放到里面
            */
            if(!$order_date){
                $whereOrderSearch["order_addtime"] = [["egt", strtotime($order_addtime_from)], ["lt", strtotime($order_addtime_end . " +1 day ")], "and"];
            }
        }else{
            /*
            by
            create:2018-2-5 17:30
            增加了一个if判断，因为默认日期去掉了
            */
            if(!empty($order_addtime_end)&&!empty($order_addtime_from)){
                $whereOrderSearch["order_addtime"] = [["egt", strtotime($order_addtime_from)], ["lt", strtotime($order_addtime_end . " +1 day ")], "and"];
            }
        }



        switch ($userOne->user_role) {
            case 0: //老板看全部经营场地的数据
                $shop_id = model("store")->where(["store_id" => $userOne->user_store_id])->value("store_shop_id");
                $whereOrderSearch["order_shop_id"] = $shop_id;

                if (intval($store_id) > 0) //指定了经营场地
                {
                    $whereOrderSearch["order_store_id"] = $store_id;
                } //else {
                //     $whereOrderSearch["order_store_id"] = $userOne->user_store_id; //暂时不支持跨经营场地查询（前端UI没准备好，2017-12-22 16:40:52）
                // }
                /* 增加了收银员 create：2018-2-5 17:19*/
                if((int)$sale_id>0){
                    $whereOrderSearch["order_user_id"]=(int)$sale_id;
                }
                break;
            case 1: //店长，经营场地的数据            
                $whereOrderSearch["order_store_id"] = $userOne->user_store_id;
                /* 增加了收银员 create：2018-2-5 17:19*/
                if((int)$sale_id>0){
                    $whereOrderSearch["order_user_id"]=(int)$sale_id;
                }
                break;
            case 2: //收银，看自己的数据
                $whereOrderSearch["order_store_id"] = $userOne->user_store_id;
                $whereOrderSearch["order_user_id"]  = $userOne->user_id;
                break;

            default:
                # code...
                break;
        }

        switch ($order_status) {
            case 'ordertrue': //有效订单（支付成功，有部分退款，已经成交的订单）

                $whereOrderSearch["order_status"] = ["in", [100, 101, 600]];

                break;

            case 'fundorder': //有退款的订单

                $whereOrderSearch["order_status"] = ["in", [101, 200]];

                break;

            case 'orderfailed': //无效订单

                $whereOrderSearch["order_status"] = ["in", [200, 400]];

                break;

            default:
                if (is_numeric($order_status)) {
                    $whereOrderSearch["order_status"] = intval($order_status);
                } else { //全部订单
                    //$whereOrderSearch["order_status"] = ["egt", 0];
                }
                break;
        }
        switch ($channel) {
            case 1:
                $whereOrderSearch['order_channel_id'] = ['in',$this->ali_arr];
                break;
            case 2:
                $whereOrderSearch['order_channel_id'] = ['in',$this->wx_arr];
                break;

            case 3:
                $whereOrderSearch['order_channel_id'] = 1008;
                break;

            default:
                break;
        }

        //__组合查询的条件

        $field = ["order_id","store_name",'order_user_id','order_store_id',"user_realname","order_num", "order_addtime", "order_status", "order_channel_id", "order_total_amount", "order_pay_realprice"];

        $lists = $order
            ->alias('o')
            ->join('qs_store s','s.store_id = o.order_store_id')
            ->join('qs_user u','u.user_id = o.order_user_id')
            ->where($whereOrderSearch)
            ->field($field)
            ->order("order_id desc")
            ->paginate($per_page);

         $data['total'] = $order
             ->where($whereOrderSearch)
             ->field('sum(order_total_amount) as total_amount,count(*) as total_conut ')
             ->find();
         $whereOrderSearch["order_status"] = 200;
         $data['refund'] = $order
             ->where($whereOrderSearch)
             ->field('sum(order_total_amount) as refund_amount,count(*) as refund_conut ')
             ->find();
         if(empty($data['refund']['refund_amount'])){
             $data['refund']['refund_amount'] = 0.00;
         }
         $data['actual'] = [];
         $data['actual']['actual_amount'] = $data['total']['total_amount'] -$data['refund']['refund_amount'] ;
         $data['actual']['actual_count'] = $data['total']['total_conut'] -$data['refund']['refund_conut'] ;



         if ($lists) {
            foreach ($lists as $listsOne) {
                $listsOne->order_status_info  = $order->status2nicename($listsOne->order_status);
                if(in_array($listsOne['order_channel_id'],$this->wx_arr)){
                    $listsOne['order_channel_id'] = '微信';
                }elseif(in_array($listsOne['order_channel_id'],$this->ali_arr)){
                    $listsOne['order_channel_id'] = '支付宝';
                }elseif($listsOne['order_channel_id']=1008){
                $listsOne['order_channel_id'] = '现金';
                }
            }
        }
        $lists = json_decode(json_encode($lists), 1);

        $data['list'] = $lists['data'];
         return ["code" => 200, "msg" => "请求成功", "data" => $data];
    }

    /**
     * 获取订单的数据统计
     * 消费总金额：成功的订单、部分退款订单、全额退款的订单 ["in", [100,101,200]]
     * 实收金额：净额，老板真实已经收到的钱；已经成功的订单 ["in", [100, 101]]
     * 订单笔数：成功的订单、部分退款订单、全额退款的订单 ["in", [100,101,200]]
     * 退款金额：部分退款订单、全额退款的订单的退款金额 ["in", [101,200]]
     * @param  [type] $user_id [description]
     * @param  [string] order_addtime_from 2017-12-22
     * @return [type]          [description]
     */
    public function statistics()
    {
        $user_id = input("user_id");
        $store_id = input("store_id","");
        $order_addtime_from = input("order_addtime_from","");
        $order_addtime_end = input("order_addtime_end","");

        $this->verifyPostDataHelper($user_id);

        //店长、店老板看全部，收银员看自己
        //model("report")->getOrderList($user_id);
        $user    = model("user");
        $userOne = $user->where(["user_id" => $user_id])->field(true)->find();
        $order   = model("order");

        if (!$order_addtime_from) {
            $order_addtime_from = date("Y-m-d", time());
        }

        if (!$order_addtime_end) {
            $order_addtime_end = date("Y-m-d", time());
        }

        $Days = round((strtotime($order_addtime_end) - strtotime($order_addtime_from)) / 3600 / 24,2);
        if ($Days < 0) {
            return ["code" => 0, "message" => "开始日期不能大于结束日期", "data" => ""];
        } else {
            if ($Days > 31) {
                return ["code" => 0, "message" => "日期间隔不能大于31天", "data" => ""];
            }
        }

        //组合查询的条件
        $whereOrderSearch                  = [];
        $whereOrderSearch["order_addtime"] = [["egt", strtotime($order_addtime_from)], ["lt", strtotime($order_addtime_end . " +1 day ")], "and"];

        switch ($userOne->user_role) {
            case 0: //老板看全部经营场地的数据
                $shop_id                           = model("store")->where(["store_id" => $userOne->user_store_id])->value("store_shop_id");
                $whereOrderSearch["order_shop_id"] = $shop_id;

                if (intval($store_id) > 0) //指定了经营场地
                {
                    $whereOrderSearch["order_store_id"] = $store_id;
                } else {
                    $whereOrderSearch["order_store_id"] = $userOne->user_store_id; //暂时不支持跨经营场地查询（前端UI没准备好，2017-12-22 16:40:52）
                }

                break;
            case 1: //店长，经营场地的数据
                $whereOrderSearch["order_store_id"] = $userOne->user_store_id;
                break;
            case 2: //收银，看自己的数据
                $whereOrderSearch["order_store_id"] = $userOne->user_store_id;
                $whereOrderSearch["order_user_id"]  = $userOne->user_id;
                break;

            default:
                # code...
                break;
        }

        //__组合查询的条件


        //实际支付总金额
        $rsdata["total_amount"] = $order->where(
            array_merge(
                $whereOrderSearch,
                ["order_status" => ["in", [100, 101]]]
            )
        )->sum("order_pay_realprice");

        if (!$rsdata["total_amount"]) {
            $rsdata["total_amount"] = 0;
        }

        //消费笔数
        $rsdata["total_count"] = $order->where(
            array_merge(
                $whereOrderSearch,
                ["order_status" => ["in", [100, 101, 200]]]
            )
        )->count();

        //$rsdata["lastsql"]= $order->getlastsql();

        //消费总金额
        $rsdata["total_order_amount"] = $order->where(
            array_merge(
                $whereOrderSearch,
                ["order_status" => ["in", [100, 101, 200]]]
            )
        )->sum("order_total_amount");

        if (!$rsdata["total_order_amount"]) {
            $rsdata["total_order_amount"] = 0;
        }
        //退款总金额
        $rsdata["total_refund_amount"] = round($rsdata["total_order_amount"] - $rsdata["total_amount"],2); //退款金额

        //支付通道
        $pay                        = model("pay");
        $order_channel_alipay_array = [];
        $order_channel_wxpay_array  = [];
        $name2id                    = $pay->payChannel(2);
        foreach ($name2id as $key => $value) {
            if (strpos($key, "alipay") !== false) {
                $order_channel_alipay_array[] = $value;
            }
        }

        foreach ($name2id as $key => $value) {
            if (strpos($key, "wxpay") !== false) {
                $order_channel_wxpay_array[] = $value;
            }
        }

        //支付宝，统计
        $rsdata["total_alipay_count"] = $order->where(array_merge($whereOrderSearch,["order_status" => ["in", [100, 101]]], ["order_channel_id" => ["in", $order_channel_alipay_array]]))->count();
        $rsdata["total_alipay_sum"] = $order->where(array_merge($whereOrderSearch,["order_status" => ["in", [100, 101]]], ["order_channel_id" => ["in", $order_channel_alipay_array]]))->sum("order_pay_realprice")?:0;
        //微信，统计
        $rsdata["total_wxpay_count"] = $order->where(array_merge($whereOrderSearch,["order_status" => ["in", [100, 101]]], ["order_channel_id" => ["in", $order_channel_wxpay_array]]))->count(); //消费笔数
        $rsdata["total_wxpay_sum"] = $order->where(array_merge($whereOrderSearch,["order_status" => ["in", [100, 101]]], ["order_channel_id" => ["in", $order_channel_wxpay_array]]))->sum("order_pay_realprice")?:0;

        return ["code" => 1, "message" => "", "data" => $rsdata];
    }

    /**
     * 获取订单的详情数据
     * @param  [type] $user_id [description]
     * @return [type]          [description]
     */
    public function detail()
    {
        $user_id=input("user_id");
        $order_num = input("order_num","");
        $return_printhtml = input("return_printhtml",0);

        $this->verifyPostDataHelper($user_id);
        if (!($order_num)) {
            return ["code" => 0, "message" => "订单号不可为空", "data" => ""];
        }
        //店长、店老板看全部，收银员看自己
        //model("report")->getOrderList($user_id);
        $user    = model("user");
        $userOne = $user->where(["user_id" => $user_id])->field(true)->find();

        $order = model("order");

        $whereOrderSearch              = [];
        $whereOrderSearch["order_num"] = $order_num;
        switch ($userOne->user_role) {
            case 0: //老板看全部经营场地的数据
                $shop_id                           = model("store")->where(["store_id" => $userOne->user_store_id])->value("store_shop_id");
                $whereOrderSearch["order_shop_id"] = $shop_id;

                break;
            case 1: //店长，经营场地的数据
                $whereOrderSearch["order_store_id"] = $userOne->user_store_id;
                break;
            case 2: //收银，看自己的数据
                $whereOrderSearch["order_store_id"] = $userOne->user_store_id;
                $whereOrderSearch["order_user_id"]  = $userOne->user_id;
                break;

            default:
                # code...
                break;
        }

        $orderOne = $order->where($whereOrderSearch)->field(true)->find();
        if (!$orderOne) {
            return ["code" => 0, "message" => "没有查到订单，或无权限查看", "data" => ""];
        } else {
            $rsdata["order_num"]         = $orderOne->order_num;
            $rsdata["order_status"]      = $orderOne->order_status;
            $rsdata["order_status_info"] = $order->status2nicename($orderOne->order_status);

            $Pay                           = model("pay");
            $paychannel                    = $Pay->payWayTranslate($orderOne->order_channel_id, true);
            $rsdata["order_channel_info"]  = $paychannel["cn"];
            $rsdata["order_channel"]       = $paychannel["name"];
            $rsdata["order_addtime"]       = $orderOne->order_addtime;
            $rsdata["order_guest_brief"]       = $orderOne->order_guest_brief;
            $rsdata["order_pay_time"]      = strtotime($orderOne->order_pay_time) == 0 ? "无" : $orderOne->order_pay_time;
            $rsdata["order_total_amount"]  = $orderOne->order_total_amount;
            $rsdata["order_pay_realprice"] = $orderOne->order_pay_realprice;
            $rsdata["user_name"]           = $user->where(["user_id" => $orderOne->order_user_id])->value("user_realname");
            $rsdata["store_name"]          = model("store")->where(["store_id" => $orderOne->order_store_id])->value("store_name");
            if ($return_printhtml == 1) {
                $rsdata["printhtml"] = model("printorder")->printTmp($orderOne->order_num);
            } else {
                $rsdata["printhtml"] = "";
            }

            if ($orderOne->order_status == 101 || $orderOne->order_status == 200) {
                $rsdata["refund_list"] = model("order_pay_log")->where(["order_pay_log_order_id" => $orderOne->order_id, "order_pay_log_status" => ["in", [101, 200]]])->order("order_pay_log_id desc")->join("__USER__", "order_pay_log_user_id=user_id", "left")->field("order_pay_log_addtime as refund_time,order_pay_log_data1 as refund_amount")->select();
            } else {
                $rsdata["refund_list"] = "";
            }

            return ["code" => 1, "message" => "", "data" => $rsdata];
        }
    }

    /**
     * 获取订单的数据统计 PC 专用
     * 消费总金额：成功的订单、部分退款订单、全额退款的订单 ["in", [100,101,200]]
     * 实收金额：净额，老板真实已经收到的钱；已经成功的订单 ["in", [100, 101]]
     * 订单笔数：成功的订单、部分退款订单、全额退款的订单 ["in", [100,101,200]]
     * 退款金额：部分退款订单、全额退款的订单的退款金额 ["in", [101,200]]
     * @param  [type] $user_id [description]
     * @param  [string] order_addtime_from 2017-12-22
     * @return [type]          [description]
     */
    public function statisticsforpc()
    {
        $user_id=input("user_id");
        $store_id = input("store_id","");

        $this->verifyPostDataHelper($user_id);

        //店长、店老板看全部，收银员看自己
        //model("report")->getOrderList($user_id);
        $user    = model("user");
        $userOne = $user->where(["user_id" => $user_id])->field(true)->find();
        $order   = model("order");

        //组合查询的条件
        $whereOrderSearch = [];
        $whereliushui=[];
        switch ($userOne->user_role) {
            case 0: //老板看全部经营场地的数据
                $shop_id                           = model("store")->where(["store_id" => $userOne->user_store_id])->value("store_shop_id");
                $whereOrderSearch["order_shop_id"] = $shop_id;
                $whereliushui["order_shop_id"] = $shop_id;
                if (intval($store_id) > 0) //指定了经营场地
                {
                    $whereOrderSearch["order_store_id"] = $store_id;
                    $whereliushui["order_store_id"] = $shop_id;
                } else {
                    $whereliushui["order_store_id"] = $userOne->user_store_id;
                    $whereOrderSearch["order_store_id"] = $userOne->user_store_id; //暂时不支持跨经营场地查询（前端UI没准备好，2017-12-22 16:40:52）
                }

                break;
            case 1: //店长，经营场地的数据
                $whereliushui["order_store_id"] = $userOne->user_store_id;
                $whereOrderSearch["order_store_id"] = $userOne->user_store_id;
                break;
            case 2: //收银，看自己的数据
                $whereliushui["order_store_id"] = $userOne->user_store_id;
                $whereliushui["order_user_id"]  = $userOne->user_id;
                $whereOrderSearch["order_store_id"] = $userOne->user_store_id;
                $whereOrderSearch["order_user_id"]  = $userOne->user_id;
                break;

            default:
                # code...
                break;
        }

        //__组合查询的条件

        //今日订单_总数
        $rsdata["today_order_count"] = $order->where(
            array_merge($whereOrderSearch,
                [
                    "order_addtime" => [["egt", strtotime("today")], ["lt", strtotime(" +1 day ")], "and"],
                    "order_status"  => ["in", [100, 101, 200]],
                ]
            )
        )->count();

        //这周订单_总数
        $rsdata["week_order_count"] = $order->where(
            array_merge($whereOrderSearch,
                [
                    "order_addtime" => [["egt", strtotime(("last monday"))], ["lt", strtotime("next monday")], "and"],
                    "order_status"  => ["in", [100, 101, 200]],
                ]
            )
        )->count();

        //$rsdata["lastsql"] = $order->getLastSql();

        //这月订单_总数
        $rsdata["month_order_count"] = $order->where(
            array_merge($whereOrderSearch,
                [
                    "order_addtime" => [["egt", strtotime("first day of this month")], ["lt", strtotime("first day of next month")], "and"],
                    "order_status"  => ["in", [100, 101, 200]],
                ]
            )
        )->count();

        //今日订单_金额
        $rsdata["today_order_total_amount"] = $order->where(
            array_merge($whereOrderSearch,
                ["order_addtime" =>
                    [["egt", strtotime("today")], ["lt", strtotime(" +1 day ")], "and"],
                    "order_status"   => ["in", [100, 101, 200]],
                ]
            )
        )->sum("order_total_amount");

        $rsdata["week_order_total_amount"] = $order->where(
            array_merge($whereOrderSearch,
                [
                    "order_addtime" => [["egt", strtotime(("last monday"))], ["lt", strtotime("next monday")], "and"],
                    "order_status"  => ["in", [100, 101, 200]],
                ]
            )
        )->sum("order_total_amount");

        $rsdata["month_order_total_amount"] = $order->where(
            array_merge($whereOrderSearch,
                [
                    "order_addtime" => [["egt", strtotime("first day of this month")], ["lt", strtotime("first day of next month")], "and"],
                    "order_status"  => ["in", [100, 101, 200]],
                ]
            )
        )->sum("order_total_amount");

        //今日订单_实收金额
        $rsdata["today_order_real_amount"] = $order->where(
            array_merge($whereOrderSearch,
                [
                    "order_addtime" => [["egt", strtotime("today")], ["lt", strtotime(" +1 day ")], "and"],
                    "order_status"  => ["in", [100, 101]],
                ]
            )
        )->sum("order_pay_realprice");

        $rsdata["week_order_real_amount"] = $order->where(
            array_merge($whereOrderSearch,
                [
                    "order_addtime" => [["egt", strtotime(("last monday"))], ["lt", strtotime("next monday")], "and"],
                    "order_status"  => ["in", [100, 101]],
                ]
            )
        )->sum("order_pay_realprice");

        $rsdata["month_order_real_amount"] = $order->where(
            array_merge($whereOrderSearch,
                [
                    "order_addtime" => [["egt", strtotime("first day of this month")], ["lt", strtotime("first day of next month")], "and"],
                    "order_status"  => ["in", [100, 101]],
                ]
            )
        )->sum("order_pay_realprice");

        if ($rsdata) {
            foreach ($rsdata as $key => $value) {
                if (!is_numeric($rsdata[$key])) //数据修复
                {
                    $rsdata[$key] = 0;
                }
            }
        }

        //退款金额
        //$rsdata["today_order_refund_amount"] = _bcsub($rsdata["today_order_total_amount"] , $rsdata["today_order_real_amount"],2);
        $rsdata["today_order_refund_amount"] = round($rsdata["today_order_total_amount"] - $rsdata["today_order_real_amount"],2);

        //$rsdata["week_order_refund_amount"]  = _bcsub($rsdata["week_order_total_amount"] , $rsdata["week_order_real_amount"],2);
        $rsdata["week_order_refund_amount"]  = round($rsdata["week_order_total_amount"] - $rsdata["week_order_real_amount"],2);

        //$rsdata["month_order_refund_amount"] = _bcsub($rsdata["month_order_total_amount"] , $rsdata["month_order_real_amount"],2);
        $rsdata["month_order_refund_amount"] = round($rsdata["month_order_total_amount"] - $rsdata["month_order_real_amount"],2);

        //登入首页，交易流水走势(15天)，功能
        $yiliushuizoushilist = model("order")->where([
            //"order_addtime" => ["egt", strtotime("-14 days")],
            "order_status"  => ["in",[100,101]],
        ])->where($whereliushui)->group("order_addtime_ymh")->field("from_unixtime(order_addtime,'%Y-%m-%d') as order_addtime_ymh,count(*) as count,sum(order_pay_realprice) as order_pay_realprice_sum")->limit(15)->order("order_addtime_ymh asc")->select();
        $rsdata["yiliushuizoushilist"]=$yiliushuizoushilist;

        return ["code" => 1, "message" => "", "data" => $rsdata];
    }
    /*
    *by
    *create:2018-2-5 17:48
    *update:2018-2-6
    *PC统计
    */
    public function ordersstatisticsforpc() {

        $user_id=input("user_id");
        $order_num=input("order_num","");
        $order_status=input("order_status","");
        $store_id=input("store_id","");
        $order_addtime_from=input("order_addtime_from","");
        $order_addtime_end=input("order_addtime_end","");
        $channel=input("channel","");
        $sale_id=input("sale_id",0);

        $this->verifyPostDataHelper($user_id);
        //店长、店老板看全部，收银员看自己
        //model("report")->getOrderList($user_id);
        $user     = model("user");
        $userOne  = $user->where(["user_id" => $user_id])->field(true)->find();
        $order    = model("order");

        if (!$order_addtime_from) {
            $order_addtime_from = date("Y-m-d",strtotime("today"));
        }

        if (!$order_addtime_end) {
            $order_addtime_end = date("Y-m-d", strtotime("+ 1 days"));
        }

        $Days = round((strtotime($order_addtime_end) - strtotime($order_addtime_from)) / 3600 / 24,2);
        if ($Days < 0) {
            return ["code" => 0, "message" => "开始日期不能大于结束日期", "data" => ""];
        }
        $date_between=date("Y.m.d",strtotime($order_addtime_from)).'-'.date("Y.m.d",strtotime($order_addtime_end));
        //组合查询的条件
        $whereOrderSearch = [];
        $whereOrderSearch["order_addtime"] = [["egt", strtotime($order_addtime_from)], ["lt", strtotime($order_addtime_end . " +1 day ")], "and"];


        switch ($userOne->user_role) {
            case 0: //老板看全部经营场地的数据
                $shop_id = model("store")->where(["store_id" => $userOne->user_store_id])->value("store_shop_id");
                $whereOrderSearch["order_shop_id"] = $shop_id;

                if (intval($store_id) > 0) //指定了经营场地
                {
                    $whereOrderSearch["order_store_id"] = $store_id;
                }
                /*
                *by
                *经营场地限制暂时去掉
                *create：2018-2-6 14:19
                */
                // else {
                //     $whereOrderSearch["order_store_id"] = $userOne->user_store_id; //暂时不支持跨经营场地查询（前端UI没准备好，2017-12-22 16:40:52）
                // }
                /* 增加了收银员 create：2018-2-5 17:19*/
                if((int)$sale_id>0){
                    $whereOrderSearch["order_user_id"]=(int)$sale_id;
                }
                break;
            case 1: //店长，经营场地的数据            
                $whereOrderSearch["order_store_id"] = $userOne->user_store_id;
                /* 增加了收银员 create：2018-2-5 17:19*/
                if((int)$sale_id>0){
                    $whereOrderSearch["order_user_id"]=(int)$sale_id;
                }
                break;
            case 2: //收银，看自己的数据
                $whereOrderSearch["order_store_id"] = $userOne->user_store_id;
                $whereOrderSearch["order_user_id"]  = $userOne->user_id;
                break;

            default:
                # code...
                break;
        }

        switch ($order_status) {
            case 'ordertrue': //有效订单（支付成功，有部分退款，已经成交的订单）

                $whereOrderSearch["order_status"] = ["in", [100, 101, 600]];

                break;

            case 'fundorder': //有退款的订单

                $whereOrderSearch["order_status"] = ["in", [101, 200]];

                break;

            case 'orderfailed': //无效订单

                $whereOrderSearch["order_status"] = ["in", [200, 400]];

                break;

            default:
                if (is_numeric($order_status)) {
                    $whereOrderSearch["order_status"] = intval($order_status);
                } else { //全部订单
                    //$whereOrderSearch["order_status"] = ["egt", 0];
                }
                break;
        }

        //支付通道
        $pay                        = model("pay");
        $order_channel_alipay_array = [];
        $order_channel_wxpay_array  = [];
        $name2id                    = $pay->payChannel(2);
        foreach ($name2id as $key => $value) {
            if (strpos($key, "alipay") !== false) {
                $order_channel_alipay_array[] = $value;
            }
        }

        foreach ($name2id as $key => $value) {
            if (strpos($key, "wxpay") !== false) {
                $order_channel_wxpay_array[] = $value;
            }
        }
        switch ($channel) {
            case 'alipay':
                $whereOrderSearch["order_channel_id"]=["in",$order_channel_alipay_array];
                break;
            case 'wxpay':
                $whereOrderSearch["order_channel_id"]=["in",$order_channel_wxpay_array];
                break;

            default:
                break;
        }
        $return_data=[];
        if(empty($channel)){
            //按照收银员统计总金额
            // $list = $order->field("sum(order_total_amount) as total_count,order_user_id")->where($whereOrderSearch)->group("order_user_id")->select();
            $list = $order->where($whereOrderSearch)->group("order_user_id")->column('sum(order_pay_realprice) as total_count,order_user_id', 'order_user_id');
            //echo $order->getLastSql();
            //全部合计
            $order_total= $order->where($whereOrderSearch)->value("sum(order_pay_realprice)");
            //按照收银员统计支付宝总金额
            $alipay_list= $order->where($whereOrderSearch)->where("order_channel_id","in",$order_channel_alipay_array)->group("order_user_id")->column("sum(order_pay_realprice) as total_count,order_user_id","order_user_id");
            //支付宝总计
            $order_total_alipay=$order->where($whereOrderSearch)->where("order_channel_id","in",$order_channel_alipay_array)->value("sum(order_pay_realprice)");
            //echo $order->getLastSql();
            //按照收银员统计微信总金额
            $wxpay_list= $order->where($whereOrderSearch)->where("order_channel_id","in",$order_channel_wxpay_array)->group("order_user_id")->column("sum(order_pay_realprice) as total_count,order_user_id","order_user_id");
            //微信总计
            $order_total_wxpay=$order->where($whereOrderSearch)->where("order_channel_id","in",$order_channel_wxpay_array)->value("sum(order_pay_realprice)");
            //echo $order->getLastSql();
            /*合并数据*/
            $return_data=array();
            foreach ($list as $key => $value) {
                $userInfo=$user->where("user_id",$key)->field("user_realname,user_mobile")->find();
                $return_data[]=array("user_id"=>$key,"total_count"=>$value,"user_info"=>$userInfo,"alipay_total"=>isset($alipay_list[$key])?$alipay_list[$key]:0,"wxpay_total"=>isset($wxpay_list[$key])?$wxpay_list[$key]:0);
            }
        }else{
            //按照收银员统计总金额
            $list = $order->where($whereOrderSearch)->group("order_user_id")->column('sum(order_pay_realprice) as total_count,order_user_id', 'order_user_id');;
            //全部合计
            $order_total= $order->where($whereOrderSearch)->value("sum(order_pay_realprice)");
            switch ($channel) {
                case 'alipay':
                    foreach ($list as $key => $value) {
                        $userInfo=$user->where("user_id",$key)->field("user_realname,user_mobile")->find();
                        $return_data[]=array("user_id"=>$key,"total_count"=>$value,"user_info"=>$userInfo,"alipay_total"=>$value,"wxpay_total"=>0);
                    }
                    //支付宝总计
                    $order_total_alipay=$order->where($whereOrderSearch)->where("order_channel_id","in",$order_channel_alipay_array)->value("sum(order_pay_realprice)");
                    break;
                case 'wxpay':
                    foreach ($list as $key => $value) {
                        $userInfo=$user->where("user_id",$key)->field("user_realname,user_mobile")->find();
                        $return_data[]=array("user_id"=>$key,"total_count"=>$value,"user_info"=>$userInfo,"alipay_total"=>0,"wxpay_total"=>$value);
                    }
                    //微信总计
                    $order_total_wxpay=$order->where($whereOrderSearch)->where("order_channel_id","in",$order_channel_wxpay_array)->value("sum(order_pay_realprice)");
                    break;
                default:
                    # code...
                    break;
            }
        }
        $order_total=empty($order_total)?0:$order_total;
        $order_total_alipay=empty($order_total_alipay)?0:$order_total_alipay;
        $order_total_wxpay=empty($order_total_wxpay)?0:$order_total_wxpay;
        return ["code" => 1, "message" => "", "data" => array("date_between"=>$date_between,"total"=>count($return_data),"list"=>$return_data,"order_total"=>$order_total,"order_total_alipay"=>$order_total_alipay,"order_total_wxpay"=>$order_total_wxpay)];
    }
}
