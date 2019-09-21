<?php
namespace app\agent\controller;

use app\agent\controller\Agentbase;

class Orderreport extends Agentbase
{
    public function _initialize()
    {
        parent::_initialize();
    }

    //月报表
    public function monthindex()
    {
        
        //我的商户列表
        
        $where["shop_agent_id"] = session("agent_id");
        $shoplist = model("shop")->where($where)->field("shop_id,shop_name")->order("shop_name asc")->select();

        $this->assign("shoplist", $shoplist);

        return view();
    }

    public function getsearchdata()
    {
        $order_month      = input("order_month")?:date("Y-m", time());
        $order_channel_id = input("order_channel_id", "");
        $order_channel_id = input("order_channel_id", "");
        $data_from        = input("data_from", "");
        $order_shop_id    = input("order_shop_id", "");

        

        // if ($data_from == "official") {
        //     return ["code" => 0, "message" => "暂无官方数据", "data" => ""];
        // }

        $order_month_from = strtotime($order_month . "-1");
        $order_month_end  = strtotime($order_month . "-1 next month");

        $whereseardata["order_addtime"]  = ["egt", $order_month_from];
        $whereseardata["order_addtime "] = ["lt", $order_month_end];
        $whereseardata["order_status"]   = ["in", [101, 100]];

        if ($order_shop_id) {
            $whereseardata["order_shop_id"] = $order_shop_id;
        }

        $whereseardata["shop_agent_id"] = session("agent_id");

        //支付通道
        $pay                    = model("pay");
        $order_channel_id_array = [];
        $order_channel_id       = input("order_channel_id");
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
            $whereseardata["order_channel_id"] = ["in", $order_channel_id_array];
        }

        $order = model("order");

        //$rs = $order->join("__SHOP__", "shop_id = order_shop_id")->join("__AGENT__", "agent_id = shop_agent_id")->field("sum(order_pay_realprice) as order_pay_realprice,shop_agent_id,agent_name,agent_proportion,agent_company_name,agent_mobile")->group("shop_agent_id")->where($whereseardata)->select();
        $rs = $order->join("__SHOP__", "shop_id = order_shop_id")->field("sum(order_pay_realprice) as order_pay_realprice,sum(order_total_amount) as order_total_amount,shop_name")->group("order_shop_id")->order("order_pay_realprice desc")->where($whereseardata)->select();
        if($rs)
        {
            foreach ($rs as $rsone) {
                //$rsone->order_refund_amount = _bcsub($rsone->order_total_amount,$rsone->order_pay_realprice,2);
                $rsone->order_refund_amount = round($rsone->order_total_amount-$rsone->order_pay_realprice,2);
            }
        }

        return ["code" => 1, "message" => "", "data" => ["list" => $rs]];
    }
}
