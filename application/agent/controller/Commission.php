<?php
namespace app\agent\controller;
use app\agent\controller\Agentbase;
//2017-1-22，
class Commission extends Agentbase
{
    public function _initialize()
    {
        parent::_initialize();
    }
    public function index()
    {
        //我的商户列表
        $where["agent_parent_agent_id|agent_id"] = session("agent_id"); //我自己及下级代理商
        $agentlist                               = model("agent")->field("agent_id,agent_name")->where($where)->order("agent_id asc")->select();
        $this->assign("agentlist", $agentlist);
        return view();
    }
    // public function getsearchdata()
    // {
    //     $order_month      = input("order_month")?:date("Y-m", time());
    //     $order_channel_id = input("order_channel_id", "");
    //     $data_from        = input("data_from", "");
    //     if ($data_from == "official") {
    //         return $this->getsearchdatafromofficial();
    //     }
    //     $agent_id         = input("agent_id", "");
    //     if ($data_from == "official") {
    //         return ["code" => 0, "message" => "暂无官方数据", "data" => ""];
    //     }
    //     $order_month_from = strtotime($order_month . "-1");
    //     $order_month_end  = strtotime($order_month . "-1 next month");
    //     $whereseardata["order_addtime"]  = ["egt", $order_month_from];
    //     $whereseardata["order_addtime "] = ["lt", $order_month_end];
    //     $whereseardata["order_status"]   = ["in", [101, 100]];
    //     if ($agent_id) {
    //         $whereseardata["shop_agent_id"] = $agent_id;
    //     }
    //     //支付通道
    //     $pay                    = model("pay");
    //     $order_channel_id_array = [];
    //     $order_channel_id       = input("order_channel_id");
    //     if ($order_channel_id) {
    //         switch ($order_channel_id) {
    //             case 'alipay':
    //                 $name2id = $pay->payChannel(2);
    //                 foreach ($name2id as $key => $value) {
    //                     if (strpos($key, "alipay") !== false) {
    //                         $order_channel_id_array[] = $value;
    //                     }
    //                 }
    //                 break;
    //             case 'wxpay':
    //                 $name2id = $pay->payChannel(2);
    //                 foreach ($name2id as $key => $value) {
    //                     if (strpos($key, "wxpay") !== false) {
    //                         $order_channel_id_array[] = $value;
    //                     }
    //                 }
    //                 break;
    //             default:
    //                 # code...
    //                 break;
    //         }
    //         $whereseardata["order_channel_id"] = ["in", $order_channel_id_array];
    //     }
    //     $order = model("order");
    //     $rs = $order->join("__SHOP__", "shop_id = order_shop_id")->join("__AGENT__", "agent_id = shop_agent_id")->field("sum(order_pay_realprice) as order_pay_realprice,shop_agent_id,agent_name,agent_proportion,agent_company_name,agent_mobile")->group("shop_agent_id")->where($whereseardata)->select();
    //     if($rs)
    //     {
    //         foreach ($rs as $rsone) {
    //             //$rsone->commission = _bcmul($rsone->order_pay_realprice,$rsone->agent_proportion,2);
    //             $rsone->commission = round($rsone->order_pay_realprice*$rsone->agent_proportion,2);
    //         }
    //     }
    //     return ["code" => 1, "message" => "", "data" => ["list" => $rs]];
    // }
    //查看官方的月报数据
    public function getsearchdatafromofficial()
    {
        $order_month      = input("order_month") ?: date("Y-m", time());
        $order_channel_id = input("order_channel_id", "");
        $agent_id         = input("agent_id", "");
        $order_month                    = strtotime($order_month . "-1");
        $whereseardata["commission_ym"] = $order_month;
        if ($order_channel_id) {
            $whereseardata["commission_site"] = $order_channel_id;
        }
        $agent = model("agent");
        if ($agent_id) {
            $agent_id_array = [session("agent_id")];
        } else {
            $agent_id_array = $agent->where(["agent_parent_agent_id|agent_id" => session("agent_id")])->column("agent_id"); //根代理商
        }
        $commission = model("commission");
        $rsdata = [];
        foreach ($agent_id_array as $agent_id_one) {
            $shop_id_array = $agent->getAllShopIdByAgentId($agent_id_one);
            $whereseardata["commission_shop_id"] = ["in", $shop_id_array];
            $agentOne = $agent->where(["agent_id" => $agent_id_one])->find();
            $dataone = ["agent_name" => $agentOne->agent_name, "agent_company_name" => $agentOne->agent_company_name, "agent_company_name" => $agentOne->agent_company_name, "agent_mobile" => $agentOne->agent_mobile, "agent_proportion" => $agentOne->agent_proportion, "order_pay_realprice" => 0, "commission" => 0];
            $rsone = $commission->where($whereseardata)->field("sum(commission_settle_amount) as commission_settle_amount")->find();
            if ($rsone) {
                $dataone["commission_settle_amount"] = ($rsone->commission_settle_amount ?: 0);
            }
            //统计给商户回点的佣金
            $shop_attr_rates_cash = 0;
            $rsdata2              = $commission->join("__SHOP_ATTR__", "commission_shop_id=shop_attr_shop_id", "left")->where($whereseardata)->field("qs_commission.*,shop_attr_wxpay_rates,shop_attr_alipay_rates")->order("commission_id desc")->select();
            if ($rsdata2) {
                foreach ($rsdata2 as $rsdataOne2) {
                    //计算返佣的金额
                    $shop_attr_rates = 0;
                    if ($rsdataOne2->commission_site == "alipay") {
                        $shop_attr_rates = $rsdataOne2->shop_attr_alipay_rates;
                    } elseif ($rsdataOne2->commission_site == "wxpay") {
                        $shop_attr_rates = $rsdataOne2->shop_attr_wxpay_rates;
                    }
                    $shop_attr_rates_cash += round($shop_attr_rates * $rsdataOne2->commission_settle_amount / $rsdataOne2->commission_feilv, 2);
                    //__计算返佣的金额
                }
            }
            $dataone["commission_settle_amount"] -= $shop_attr_rates_cash;
            //__统计给商户回点的佣金
            $yongJinBili = 1;
            if ($dataone["commission_settle_amount"] > 0) {
                //统计(扣除上层……上层吃掉的)佣金2018-4-20 11:43:14
                $ParentsAgentslists = model("agent")->getParentsAllAgentByAgentId(session("agent_id"));
                if ($ParentsAgentslists) {
                    foreach ($ParentsAgentslists as $ParentsAgentslistsOne) {
                        if($ParentsAgentslistsOne->agent_id!=session("agent_id"))$yongJinBili*= $ParentsAgentslistsOne->agent_proportion; //把层层吃掉的佣金乘起来(先不计算自己的)
                    }
                }
                //__统计佣金
            }
            else
            {
                $yongJinBili=0;
            }

            $dataone["commission_settle_amount"] = round(($dataone["commission_settle_amount"] ?: 0) * $yongJinBili,2);//待分成佣金

            $dataone["commission"] = round($dataone["commission_settle_amount"] * $agentOne->agent_proportion, 2);//计算该服务商应得的佣金
            //$dataone["commission"] = $yongJinBili;
            $rsdata[] = $dataone;
        }
        //$rsdata                              = $commission->join("__SHOP__", "commission_pin_mch_id=shop_alipay_seller_id or commission_pin_mch_id=shop_wxpay_sub_mch_id", "left")->where($whereseardata)->field("qs_commission.*,shop_name")->order("commission_id desc")->select();
        if (!$rsdata) {
            return ["code" => 0, "message" => "没找到数据", "data" => ""];
        } else {
            return ["code" => 1, "message" => "", "data" => ["list" => $rsdata]];
        }
    }
}