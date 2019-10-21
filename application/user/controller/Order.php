<?php
namespace app\user\controller;

use think\Request;

class Order extends Apibase
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

         try{
             $user_id=input("user_id");
             $order_num = input("order_num","");
             $order_status = input("order_status","");
             $store_id = input("store_id","");
             $page = input("page",0);
             $per_page = input("per_page",15);
             $order_addtime_from = input("order_addtime_from","");
             $order_addtime_end = input("order_addtime_end","");
             $channel=input("channel","");
             $sale_id=input("sale_id",0);
             $this->verifyPostDataHelper($user_id);
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
                 case 1: //未完成

                     $whereOrderSearch["order_status"] = 0;

                     break;

                 case 2: //已完成

                     $whereOrderSearch["order_status"] = ["in", [100, 600]];

                     break;

                 case 3: //有退款的订单

                     $whereOrderSearch["order_status"] = ["in", [200, 101]];

                     break;

                 default:
                     if (is_numeric($order_status)) {
                         $whereOrderSearch["order_status"] = intval($order_status);
                     } else { //全部订单
                         //$whereOrderSearch["order_status"] = ["egt", 0];
                     }
                     break;
             }
             // 支付渠道  （微信支付宝现金）
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


             if($order_status==1 ||$order_status==2){
                 $data['total'] = $order
                     ->where($whereOrderSearch)
                     ->field('sum(order_total_amount) as total_amount,count(*) as total_conut ')
                     ->find();
                 $data['refund'] = [];
                 $data['refund']['refund_amount'] = 0 ;
                 $data['refund']['refund_conut'] = 0 ;
                 $data['actual'] = [];
                 $data['actual']['actual_amount'] = 0 ;
                 $data['actual']['actual_count'] = 0 ;
             } elseif($order_status==3) {
                 $data['refund'] = $order
                     ->where($whereOrderSearch)
                     ->field('sum(order_total_amount) as refund_amount,count(*) as refund_conut ')
                     ->find();
                 $data['total'] = [];
                 $data['total']['total_amount'] = 0;
                 $data['total']['total_conut'] = 0;
                 $data['actual'] = [];
                 $data['actual']['actual_amount'] = 0;
                 $data['actual']['actual_count'] = 0;
             } else{
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
                 $data['actual']['actual_amount'] = ($data['total']['total_amount']*100 -$data['refund']['refund_amount']*100)/100 ;
                 $data['actual']['actual_count'] = $data['total']['total_conut'] -$data['refund']['refund_conut'] ;
             }

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

             $data['list'] = $lists;
             return json(["code" => 200, "msg" => "请求成功", "data" => $data]);
         }catch (\Exception $e){
             return json(["code" => 400, "msg" => "请求失败", "data" =>$e->getMessage()]);
         }
    }

    /**
     *home统计数据
     */
    public function total()
    {
        try{
        $user_id = input("user_id");
        $store_id = input("store_id","");
        $order_addtime_from = input("order_addtime_from","");
        $order_addtime_end = input("order_addtime_end","");

        $this->verifyPostDataHelper($user_id);

        //店长、店老板看全部，收银员看自己
        //model("report")->getOrderList($user_id);
        $user    = model("user");
        $userOne = $user->where(["user_id" => $user_id])->field(true)->find();
        if($userOne->user_role!=0){
            return json(["code" => 400, "msg" => "权限不足", "data" =>'']);
        }


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

        $shop_id                           = model("store")->where(["store_id" => $userOne->user_store_id])->value("store_shop_id");
        $whereOrderSearch["order_shop_id"] = $shop_id;

         if (intval($store_id) > 0) //指定了经营场地
         {
             $whereOrderSearch["order_store_id"] = $store_id;
          }else{
             $whereOrderSearch["order_store_id"] = $userOne->user_store_id; //暂时不支持跨经营场地查询（前端UI没准备好，2017-12-22 16:40:52）
         }

            //统计
            $data = $this->statistics($whereOrderSearch);
            //最近订单列表
            $lists = $this->order_list($whereOrderSearch);
            //活跃商户
            $active_shop = $this->active_shop($shop_id);
            $data['active_shop'] = $active_shop;
            $data['list'] = $lists;

            return json(["code" => 200, "message" => "请求成功", "data" => $data]);

        }catch (\Exception $e){
            return json(["code" => 400, "message" => "请求失败", "data" => $e->getMessage()]);

        }
    }

    /**
     * @param $shop_id
     * @return mixed
     */
    public function active_shop($shop_id){
        $order   = model("order");
        $whereOrderSearch["order_addtime"] = [["egt", strtotime('-10 day')], ["lt", time()], "and"];
        $whereOrderSearch["order_shop_id"] = $shop_id;
        $active_shop = $order
            ->alias('o')
            ->where($whereOrderSearch)
            ->join('qs_store s','s.store_id = o.order_store_id')
            ->field('order_store_id,sum(order_total_amount) as total_amount,store_name')
            ->group('order_store_id')
            ->order('total_amount desc')
            ->select();

        return $active_shop;
    }
    /**
     * 订单
     * @param $whereOrderSearch
     */
    public function order_list($whereOrderSearch){
        $order   = model("order");
        unset($whereOrderSearch['order_addtime']);

        $field = ["order_id","store_name",'order_user_id','order_store_id',"user_realname","order_num", "order_addtime", "order_status", "order_channel_id", "order_total_amount"];

        $lists = $order
            ->alias('o')
            ->join('qs_store s','s.store_id = o.order_store_id')
            ->join('qs_user u','u.user_id = o.order_user_id')
            ->where($whereOrderSearch)
            ->field($field)
            ->order("order_id desc")
            ->limit(5)
            ->select();
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
        return  $lists;

    }
    /**
     * 统计
     */
    public function statistics($whereOrderSearch){
        $order   = model("order");

        //实收金额
        //支付宝，统计
        $realprice =array();
        $realprice["ali"] = $order
            ->where(array_merge($whereOrderSearch,["order_status" =>100 ], ["order_channel_id" => ["in", $this->ali_arr]]))
            ->field('sum(order_total_amount) as ali_realprice_total,count(*) as ali_realprice_count')
            ->find();
        if (!$realprice['ali']['ali_realprice_total']){
            $realprice['ali']['ali_realprice_total']=0.00;
        }
//微信，统计
        $realprice["wx"] = $order
            ->where(array_merge($whereOrderSearch,["order_status" =>100 ], ["order_channel_id" => ["in", $this->wx_arr]]))
            ->field('sum(order_total_amount) as wx_realprice_total,count(*) as wx_realprice_count')
            ->find();
        if (!$realprice['wx']['wx_realprice_total']){
            $realprice['wx']['wx_realprice_total']=0.00;
        }
        $realprice["cash"] = $order
            ->where(array_merge($whereOrderSearch,["order_status" =>100 ], ["order_channel_id" => 1008]))
            ->field('sum(order_total_amount) as cash_realprice_total,count(*) as cash_realprice_count')
            ->find();
        if (!$realprice['cash']['cash_realprice_total']){
            $realprice['cash']['cash_realprice_total']=0.00;
        }
        $realprice['member'] = [];

        $realprice['member']['member_realprice_total']=0.00;
        $realprice['member']['member_realprice_count']=0;

        $realprice['realprice']=($realprice['cash']['cash_realprice_total']*100 + $realprice['wx']['wx_realprice_total'] *100 + $realprice['ali']['ali_realprice_total'] *100)/100;
        $data['realprice']=$realprice;

//退款金额（目前只包含全额退款的订单）
//支付宝，统计
        $refund =array();
        $refund["ali"] = $order
            ->where(array_merge($whereOrderSearch,["order_status" =>200 ], ["order_channel_id" => ["in", $this->ali_arr]]))
            ->field('sum(order_total_amount) as ali_refund_total,count(*) as ali_refund_count')
            ->find();
        if (!$refund['ali']['ali_refund_total']){
            $refund['ali']['ali_refund_total']=0.00;
        }
//微信，统计
        $refund["wx"] = $order
            ->where(array_merge($whereOrderSearch,["order_status" =>200 ], ["order_channel_id" => ["in", $this->wx_arr]]))
            ->field('sum(order_total_amount) as wx_refund_total,count(*) as wx_refund_count')
            ->find();
        if (!$refund['wx']['wx_refund_total']){
            $refund['wx']['wx_refund_total']=0.00;
        }
        $refund["cash"] = $order
            ->where(array_merge($whereOrderSearch,["order_status" =>200 ], ["order_channel_id" => 1008]))
            ->field('sum(order_total_amount) as cash_refund_total,count(*) as cash_refund_count')
            ->find();
        if (!$refund['cash']['cash_refund_total']){
            $refund['cash']['cash_refund_total']=0.00;
        }
        $refund['member'] = [];

        $refund['member']['member_refund_total']=0.00;
        $refund['member']['member_refund_count']=0;
        $refund['refund']=($refund['cash']['cash_refund_total']*100 + $refund['wx']['wx_refund_total'] *100 + $refund['ali']['ali_refund_total'] *100)/100;

        $data['refund']=$refund;
//            订单金额
//支付宝，统计
        $orders =array();
        $orders["ali"] = $order
            ->where(array_merge($whereOrderSearch,["order_status" =>100 ], ["order_channel_id" => ["in", $this->ali_arr]]))
            ->field('sum(order_total_amount) as ali_orders_total,count(*) as ali_orders_count')
            ->find();
        if (!$orders['ali']['ali_orders_total']){
            $orders['ali']['ali_orders_total']=0.00;
        }
//微信，统计
        $orders["wx"] = $order
            ->where(array_merge($whereOrderSearch,["order_status" =>100 ], ["order_channel_id" => ["in", $this->wx_arr]]))
            ->field('sum(order_total_amount) as wx_orders_total,count(*) as wx_orders_count')
            ->find();
        if (!$orders['wx']['wx_orders_total']){
            $orders['wx']['wx_orders_total']=0.00;
        }
        $orders["cash"] = $order
            ->where(array_merge($whereOrderSearch,["order_status" =>100 ], ["order_channel_id" => 1008]))
            ->field('sum(order_total_amount) as cash_orders_total,count(*) as cash_orders_count')
            ->find();
        if (!$orders['cash']['cash_orders_total']){
            $orders['cash']['cash_orders_total']=0.00;
        }
        $orders['member']['member_orders_total']=0.00;
        $orders['member']['member_orders_count']=0;

        $orders['orders']=($orders['cash']['cash_orders_total']*100 + $orders['wx']['wx_orders_total'] *100 + $orders['ali']['ali_orders_total'] *100)/100;

        $data['orders']=$orders;
        return  $data;

    }


}
