<?php
namespace app\dl\controller;

use app\dl\controller\Adminbase;

class Commissionreport extends Adminbase
{
    public function _initialize()
    {
        parent::_initialize();
    }

    public function index()
    {
        Adminbase::checkActionAuth();

        //我的商户列表
        $where["agent_parent_agent_id"] = 0;
        $agentlist                      = model("agent")->field("agent_id,agent_name")->where($where)->order("agent_name asc")->select();
        $this->assign("agentlist", $agentlist);

        return view();
    }

    // public function getsearchdata()
    // {
    //     Adminbase::checkActionAuth(request()->controller() . "/index", "view");
    //     $data_from = input("data_from", "");
    //     if ($data_from == "official") {
    //         return $this->getsearchdatafromofficial();
    //     }
    //     $order_month      = input("order_month") ?: date("Y-m", time());
    //     $order_channel_id = input("order_channel_id", "");
    //     $agent_id         = input("agent_id", "");

    //     $order_month_from = strtotime($order_month . "-1");
    //     $order_month_end  = strtotime($order_month . "-1 next month");

    //     $whereseardata["order_addtime"]  = ["egt", $order_month_from];
    //     $whereseardata["order_addtime "] = ["lt", $order_month_end];
    //     $whereseardata["order_status"]   = ["in", [101, 100]];

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
    //     $agent = model("agent");

    //     if ($agent_id) {
    //         $agent_id_array = [$agent_id];
    //     } else {
    //         $agent_id_array = $agent->where(["agent_parent_agent_id" => 0])->column("agent_id"); //自己的，及下级代理的数据
    //     }

    //     $rsdata = [];
    //     foreach ($agent_id_array as $agent_id_one) {
    //         $shop_id_array                  = $agent->getAllShopIdByAgentId($agent_id_one);
    //         $agentOne                       = $agent->where(["agent_id" => $agent_id_one])->find();
    //         $dataone                        = ["agent_name" => $agentOne->agent_name, "agent_company_name" => $agentOne->agent_company_name, "agent_company_name" => $agentOne->agent_company_name, "agent_mobile" => $agentOne->agent_mobile, "agent_proportion" => $agentOne->agent_proportion, "order_pay_realprice" => 0, "commission" => 0];
    //         $whereseardata["order_shop_id"] = ["in", $shop_id_array];
    //         $rsone                          = $order->field("sum(order_pay_realprice) as order_pay_realprice")->where($whereseardata)->find();

    //         if ($rsone) {
    //             $dataone["order_pay_realprice"] = ($rsone->order_pay_realprice ?: 0);
    //             $dataone["commission"]          = round(($rsone->order_pay_realprice ?: 0) * $agentOne->agent_proportion, 2);
    //         }
    //         $rsdata[] = $dataone;
    //     }

    //     return ["code" => 1, "message" => "", "data" => ["list" => $rsdata]];
    // }

    //查看官方的月报数据
    public function getsearchdatafromofficial()
    {
        Adminbase::checkActionAuth(request()->controller() . "/index", "view");

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
            $agent_id_array = [$agent_id];
        } else {
            $agent_id_array = $agent->where(["agent_parent_agent_id" => 0])->column("agent_id"); //根代理商
        }

        $commission = model("commission");

        $rsdata = [];
        foreach ($agent_id_array as $agent_id_one) {
            $shop_id_array = $agent->getAllShopIdByAgentId($agent_id_one);

            $whereseardata["commission_shop_id"] = ["in", $shop_id_array];

            $agentOne = $agent->where(["agent_id" => $agent_id_one])->find();

            $dataone = ["agent_name" => $agentOne->agent_name,"agent_id" => $agentOne->agent_id, "agent_company_name" => $agentOne->agent_company_name, "agent_company_name" => $agentOne->agent_company_name, "agent_mobile" => $agentOne->agent_mobile, "agent_proportion" => $agentOne->agent_proportion, "commission_total_amount" => 0,"commission_settle_amount"=>0, "order_pay_realprice_clear" => 0, "commission" => 0];

            $rsone = $commission->where($whereseardata)->field("sum(commission_total_amount) as commission_total_amount,sum(commission_settle_amount) as commission_settle_amount")->find();

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

            //__统计给商户回点的佣金

            if ($rsone) {
                $dataone["commission_total_amount"] = ($rsone->commission_total_amount ?: 0);//有效成交额
                $dataone["commission_settle_amount"] = ($rsone->commission_settle_amount - $shop_attr_rates_cash ?: 0);//全部佣金(仅仅剔除返佣)
                $dataone["commission"]          = round($dataone["commission_settle_amount"] * $agentOne->agent_proportion, 2);//该代理商应得佣金
            }
            $rsdata[] = $dataone;
        }

        //$rsdata                              = $commission->join("__SHOP__", "commission_pin_mch_id=shop_alipay_seller_id or commission_pin_mch_id=shop_wxpay_sub_mch_id", "left")->where($whereseardata)->field("qs_commission.*,shop_name")->order("commission_id desc")->select();
        if (!$rsdata) {
            return ["code" => 0, "message" => "没找到数据", "data" => ""];
        } else {
            return ["code" => 1, "message" => "", "data" => ["list" => $rsdata]];
        }
    }

    public function commissionreportlist()
    {
        Adminbase::checkActionAuth(request()->controller() . "/index", "view");
        return view();
    }

    public function commissionreportlistdata()
    {
        Adminbase::checkActionAuth(request()->controller() . "/index", "view");
        $order_month      = input("order_month") ?: date("Y-m", time());
        $order_channel_id = input("order_channel_id", "");
        $agent_id    = input("agent_id", "");
        
        $shop_attr_rates  = input("shop_attr_rates", 0);

        if ($agent_id) {
            $whereseardata["shop_id"] = ["in",model("agent")->getAllShopIdByAgentId($agent_id)];
        }

        if ($shop_attr_rates) {
            $whereseardata["shop_attr_wxpay_rates|shop_attr_alipay_rates"] = ["gt", 0];
        }

        $order_month                    = strtotime($order_month . "-1");
        $whereseardata["commission_ym"] = $order_month;
        if ($order_channel_id) {
            $whereseardata["commission_site"] = $order_channel_id;
        }

        $commission = model("commission");

        $rsdata = $commission->join("__SHOP__", "shop_id=commission_shop_id", "left")->join("__SHOP_ATTR__", "shop_id=shop_attr_shop_id", "left")->where($whereseardata)->field("qs_commission.*,shop_name,shop_attr_wxpay_rates,shop_attr_alipay_rates")->order("commission_id desc")->select();
        if ($rsdata) {
            foreach ($rsdata as $rsdataOne) {
                //计算返佣的金额
                $shop_attr_rates = 0;
                if ($rsdataOne->commission_site == "alipay") {
                    $shop_attr_rates = $rsdataOne->shop_attr_alipay_rates;
                } elseif ($rsdataOne->commission_site == "wxpay") {
                    $shop_attr_rates = $rsdataOne->shop_attr_wxpay_rates;
                }

                $rsdataOne->shop_attr_rates_cash = round($shop_attr_rates * $rsdataOne->commission_settle_amount / $rsdataOne->commission_feilv, 2);
                //__计算返佣的金额
                
            }
        }

        return ["code" => 1, "message" => "", "data" => ["list" => $rsdata]];
    }
}
