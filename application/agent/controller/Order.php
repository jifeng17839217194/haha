<?php
namespace app\agent\controller;

use app\agent\controller\Agentbase;

class Order extends Agentbase
{
    public function _initialize()
    {
        parent::_initialize();
    }
    public function index()
    {

        //我的商户列表
        $shoplist = model("shop")->where(["shop_agent_id" => $this->getMyagentId()])->field("shop_id,shop_name")->order("shop_name asc")->select();
        $this->assign("shoplist", $shoplist);

        return view();
    }

    /**
     * 搜索数据获取
     * @return [type] [description]
     */
    public function getsearchdata()
    {


        $Order            = model("Order");
        $where            = [];
        $order_addtime_s  = input("get.order_addtime_s", "");
        $order_addtime_e  = input("get.order_addtime_e", "");
        $keyword          = input("get.keyword", "");
        $order_channel_id = input("get.order_channel_id", "");
        $order_status     = input("get.order_status", "");
        $order_shop_id    = input("get.order_shop_id/d", "");
        $order_store_id   = input("get.order_store_id/d", "");
        $order_user_id    = input("get.order_user_id/d", "");
        $pagesize    = input("get.pagesize/d", 10);

        $where["shop_agent_id"] = $this->getMyagentId();

        if ($order_addtime_s) {
            $where['order_addtime'] = ["egt", strtotime($order_addtime_s)];
        }
        if ($order_addtime_e) {
            $where['order_addtime '] = ["lt", strtotime($order_addtime_e . " +1 day")];
        }
        //if ($order_addtime_s && $order_addtime_e) {$where['order_addtime'] = ["between", '$order_addtime_s ,'.strtotime($order_addtime_e)];}
        if ($keyword) {
            $where['order_num|order_guest_brief'] = ["like", "%" . $keyword . "%"];
        }
        //支付通道
        $pay                    = model("pay");
        $order_channel_id_array = [];
        if ($order_channel_id) {
            switch ($order_channel_id) {
                case 'alipay':
                    $name2id = $pay->payChannel(2);
                    foreach ($name2id as $key => $value) {
                        if (strpos($key, "alipay") !== false) {
                            $order_channel_id_array[] = $value;
                        }
                    }
                    break;
                case 'wxpay':
                    $name2id = $pay->payChannel(2);
                    foreach ($name2id as $key => $value) {
                        if (strpos($key, "wxpay") !== false) {
                            $order_channel_id_array[] = $value;
                        }
                    }
                    break;
                default:
                    # code...
                    break;
            }
            $where["order_channel_id"] = ["in", $order_channel_id_array];
        }
        if ($order_shop_id) {
            $where["order_shop_id"] = $order_shop_id;
        }
        if ($order_store_id) {
            $where["order_store_id"] = $order_store_id;
        }
        if ($order_user_id) {
            $where["order_user_id"] = $order_user_id;
        }
        //支付状态
        if ($order_status) {
            switch ($order_status) {
                case 'success':
                    $where["order_status"] = ["in",[100,101]];
                    break;
                case 'close':
                    $where["order_status"] = 200;
                default:
                    # code...
                    break;
            }
        }
        $lists = $Order->join("__SHOP__", "shop_id=order_shop_id", "left")->where($where)->order('order_addtime', 'desc')->paginate($pagesize);
        if ($lists) {
            $Pay           = model("pay");
            $order_pay_log = model("order_pay_log");
            foreach ($lists as $listsOne) {
                $listsOne->order_channel_id_info = $Pay->payWayTranslate($listsOne->order_channel_id, true)["cn"];
                $listsOne->order_status_info     = $Order->status2nicename($listsOne->order_status);
                $listsOne->refundlist            = [];
                if ($listsOne->order_status == 101 || $listsOne->order_status == 200) {
                    $refundlist           = $order_pay_log->where(["order_pay_log_order_id" => $listsOne->order_id, "order_pay_log_status" => ["in", [101, 200]]])->order("order_pay_log_id desc")->join("__USER__", "order_pay_log_user_id=user_id", "left")->field("order_pay_log_addtime,user_realname,order_pay_log_data1")->select();
                    $listsOne->refundlist = $refundlist;
                }
            }
        }
        $lists = json_decode(json_encode($lists), 1);
        //统计
        //交易总金额
        $lists["tj_total"] = $Order->join("__SHOP__", "shop_id=order_shop_id", "left")->where($where)->sum("order_pay_realprice");
        if (!$lists["tj_total"]) {
            $lists["tj_total"] = 0;
        }
        //return ["code"=>0,"message"=>json_encode($where),"data"=>""];
        $name2id = $pay->payChannel(2);
        //交易总金额(支付宝)
        foreach ($name2id as $key => $value) {
            if (strpos($key, "alipay") !== false) {
                $tj_alipay_total_array[] = $value;
            }
        }
        $tj_alipay_total_where                      = $where;
        $tj_alipay_total_where["order_channel_id "] = ["in", $tj_alipay_total_array];
        $lists["tj_alipay_total"]                   = $Order->join("__SHOP__", "shop_id=order_shop_id", "left")->where($tj_alipay_total_where)->sum("order_pay_realprice");
        if (!$lists["tj_alipay_total"]) {
            $lists["tj_alipay_total"] = 0;
        }
        if ($lists["tj_total"] == 0) {
            $lists["tj_alipay_zb"] = 0;
        } else {
            //$lists["tj_alipay_zb"] = _bcdiv($lists["tj_alipay_total"], $lists["tj_total"], 4) * 100;
            $lists["tj_alipay_zb"] = round($lists["tj_alipay_total"] / $lists["tj_total"] * 100, 2);
        }
        //交易总金额(微信)
        foreach ($name2id as $key => $value) {
            if (strpos($key, "wxpay") !== false) {
                $tj_wxpay_total_array[] = $value;
            }
        }
        $tj_wxpay_total_where                      = $where;
        $tj_wxpay_total_where["order_channel_id "] = ["in", $tj_wxpay_total_array];
        $lists["tj_wxpay_total"]                   = $Order->join("__SHOP__", "shop_id=order_shop_id", "left")->where($tj_wxpay_total_where)->sum("order_pay_realprice");
        if (!$lists["tj_wxpay_total"]) {
            $lists["tj_wxpay_total"] = 0;
        }
        if ($lists["tj_total"] == 0) {
            $lists["tj_wxpay_zb"] = 0;
        } else {
            //$lists["tj_wxpay_zb"] = _bcdiv($lists["tj_wxpay_total"], $lists["tj_total"], 4) * 100;
            $lists["tj_wxpay_zb"] = round($lists["tj_wxpay_total"] / $lists["tj_total"] * 100, 2);
        }
        //退款总金额
        $tj_refund_total_where = $where;
        $lists["total_amount"] = $Order->join("__SHOP__", "shop_id=order_shop_id", "left")->where($where)->sum("order_total_amount");
        if (!$lists["total_amount"]) {
            $lists["total_amount"] = 0;
        }
        //$lists["tj_refund_total"]= _bcsub($lists["total_amount"] ,$lists["tj_total"],2);
        $lists["tj_refund_total"]= round($lists["total_amount"] - $lists["tj_total"],2);
        //__统计
        return $lists;
    }

    //获取后台默认首页的数据
    public function gettjdata()
    {

        $Order = model("order");

        $where["shop_agent_id"]  = $this->getMyagentId();
        $where["order_addtime"]  = ["egt", strtotime("today")];
        $where["order_addtime "] = ["lt", strtotime("+1 day")];
        $where["order_status"]   = ["in", [100, 101, 200]];
        //今日订单数
        $data["tj_count"] = $Order->join("__SHOP__", "shop_id=order_shop_id", "left")->where($where)->count();
        //今日交易额
        $data["tj_total"] = $Order->join("__SHOP__", "shop_id=order_shop_id", "left")->where($where)->sum("order_total_amount");
        if (!$data["tj_total"]) {
            $data["tj_total"] = 0;
        }
        //总商户数
        $data["tj_shop_count"] = model("shop")->where(["shop_agent_id" => $this->getMyagentId()])->count();
        //总门店数
        $data["tj_store_count"] = model("store")->join("__SHOP__", "shop_id=store_shop_id", "left")->where(["shop_agent_id" => $this->getMyagentId()])->count();

        //活跃门店(最近10天)
        $huoyuemendianlist_sql = $Order->join("__SHOP__", "shop_id=order_shop_id", "left")->where(["shop_agent_id" => $this->getMyagentId()])->where([
            "order_addtime" => ["egt", strtotime("-9 days")],
            "order_status"  => 100,
        ])->group("order_store_id")->field(["order_store_id", "sum(order_total_amount) as order_total_amount"])->order("order_total_amount desc")->limit(7)->buildsql();

        $huoyuemendianlist         = model("shop")->join("__STORE__", " shop_id = store_shop_id ", "right")->where(["shop_agent_id" => $this->getMyagentId()])->join($huoyuemendianlist_sql . " as qs_order", "order_store_id=store_id", "right")->field("store_name,shop_name,order_total_amount")->select();
        $data["huoyuemendianlist"] = $huoyuemendianlist;

        //登入首页，交易流水走势(15天)，功能
        $ls=model("agent")->getAllShopIdByAgentId($this->getMyagentId());
        
        $yiliushuizoushilist = $Order->join("__SHOP__", "shop_id=order_shop_id", "left")->where(["shop_agent_id" => $this->getMyagentId()])->where([
            "order_addtime" => ["egt", strtotime("-30 days")],
            "order_status"  => ["in",[100,101]],
        ])->group("order_addtime_ymh")->field("from_unixtime(order_addtime,'%Y-%m-%d') as order_addtime_ymh,count(*) as count,sum(order_pay_realprice) as order_pay_realprice_sum")->order("order_addtime_ymh asc")->select();
        $data["yiliushuizoushilist"]=$yiliushuizoushilist;

        //__登入首页，交易流水走势，功能


        return ["code" => 1, "message" => "", "data" => $data];
    }

    //获取“签约商户” 二级 select 的数据
    public function getstorelist($shop_id = 0)
    {

        $rs = [];
        if ($shop_id) {
            $shoplist = model("store")->where(["store_shop_id" => $shop_id])->field("store_id,store_name")->order("store_name asc")->select();
            $rs       = $shoplist;
        }
        return $rs;
    }

    //获取“签约商户” 三级 收银员 select 的数据
    public function getstoreuserlist($store_id = 0)
    {

        $rs = [];
        if ($store_id) {
            $list = model("user")->where(["user_store_id" => $store_id])->field("user_id,user_realname,user_mobile")->order("user_realname asc")->select();
            $rs   = $list;
        }
        return $rs;
    }
}
