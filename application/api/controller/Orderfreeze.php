<?php
namespace app\api\controller;

class Orderfreeze extends Apibase
{
    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 扫描枪、APP条码：冻结资金
     * @param  string  $user_id      [description]
     * @param  integer $total_amount [description]
     * @param  string  $auth_code    [description]
     * @param  string  $version      [description]
     * @param  string  $sign         [description]
     * @param  string  $time         [description]
     * @return [type]                [description]
     */
    public function freeze()
    {
        $user_id      = input("user_id");
        $total_amount = input("total_amount", 0);
        $auth_code    = input("auth_code");
        $version      = input("version");
        $sign         = input("sign");
        $time         = input("time");
        $create_where = input("create_where", "pc");

        $this->verifyPostDataHelper($user_id);

        $total_amount = trim($total_amount);
        $auth_code2   = "code" . $auth_code; //下面正则判断需要转成字符串

        if (!preg_match("/^code(25|26|27|28|29|30){1}[0-9]{14,22}$/", $auth_code2)) //支付宝将会在2017年9月底对支付宝的用户付款码做升级处理。付款码将由原来的28开头扩充到25-30开头，长度由原来的16-18位扩充到16-24位
        {
            return ["code" => 0, "message" => "目前只支持支付宝", "data" => ""];
        }

        $order_freeze = model("order_freeze");
        $rs           = $order_freeze->createOrder(["user_id" => $user_id, "total_amount" => $total_amount, "auth_code" => $auth_code, "create_where" => $create_where]);
        if ($rs["code"] == 0) {
            return $rs;
        } else {
            $payrs = $order_freeze->dofreeze($rs["data"]);
            if ($payrs["data"]["status"] == 100) {
                //$payrs["data"]["printhtml"] = model("printorder")->printTmp($payrs["data"]["order_freeze_num"]);
            }
            return $payrs;
        }
    }

    //订单查询
    // public function tradequeryrequest($order_freeze_num, $user_id , $returnpricedata=0)
    // {
    //     $this->verifyPostDataHelper($user_id);
    //     $rs=model("order_freeze")->doquery($order_freeze_num,true);
    //     /*if($rs["data"]["trade_status"]==100&&$returnpricedata)
    //     {
    //         //$rs["data"]["printhtml"] = model("printorder")->printTmp($rs["data"]["order_num"]);
    //     }*/
    //     return $rs;
    // }

    /**
     * [listforpc预授权订单列表]
     * @param  string  $order_freeze_status [指定状态，100预授权中，300成功解除的历史记录，默认全部（100+300）]
     * @param  [type]  $user_id             [当前登入的user_id]
     * @param  string  $store_id            [指定店铺ID]
     * @param  integer $page                [description]
     * @param  integer $page                [description]
     * @param  integer $per_page            [description]
     * @param  string  $sale_id             [指定收银员记录的ID]
     * @param  string  $order_freeze_num    [指定订单号查询]
     * @return [type]                       [description]
     */
    public function listforpc()
    {
        $order_freeze_status       = input("order_freeze_status", "");
        $user_id                   = input("user_id");
        $store_id                  = input("store_id", "");
        $page                      = input("page", 1);
        $per_page                  = input("page", 20);
        $sale_id                   = input("sale_id", "");
        $order_freeze_num          = input("order_freeze_num", "");
        $order_freeze_addtime_from = input("order_freeze_addtime_from", "");
        $order_freeze_addtime_end  = input("order_freeze_addtime_end", "");

        $this->verifyPostDataHelper($user_id);
        //店长、店老板看全部，收银员看自己
        //model("report")->getOrderList($user_id);
        $user         = model("user");
        $userOne      = $user->where(["user_id" => $user_id])->field(true)->find();
        $order_freeze = model("order_freeze");
        $per_page     = intval($per_page);
        if ($per_page > 100) {
            $per_page = 100;
        }
        //最大每页100条；暂定100，没有其它约束

        if (!$order_freeze_addtime_from) {
            $order_freeze_addtime_from = date("Y-m-d", strtotime("- 2 days"));
        }

        if (!$order_freeze_addtime_end) {
            $order_freeze_addtime_end = date("Y-m-d", strtotime("+ 1 days"));
        }

        $Days = round((strtotime($order_freeze_addtime_end) - strtotime($order_freeze_addtime_from)) / 3600 / 24, 2);
        if ($Days < 0) {
            return ["code" => 0, "message" => "开始日期不能大于结束日期", "data" => ""];
        } else {
            if ($Days > 31) {
                return ["code" => 0, "message" => "日期间隔不能大于31天", "data" => ""];
            }
        }

        //组合查询的条件
        $whereOrderSearch = [];

        //订单号
        if ($order_freeze_num) {
            $whereOrderSearch["order_freeze_num"] = ["like", "%" . $order_freeze_num . "%"];
        }

        $whereOrderSearch["order_freeze_addtime"] = [["egt", strtotime($order_freeze_addtime_from)], ["lt", strtotime($order_freeze_addtime_end . " +1 day ")], "and"];

        switch ($userOne->user_role) {
            case 0: //老板看全部经营场地的数据
                $shop_id                                  = model("store")->where(["store_id" => $userOne->user_store_id])->value("store_shop_id");
                $whereOrderSearch["order_freeze_shop_id"] = $shop_id;

                if (intval($store_id) > 0) //指定了经营场地
                {
                    $whereOrderSearch["order_freeze_store_id"] = $store_id;
                } else {
                    $whereOrderSearch["order_freeze_store_id"] = $userOne->user_store_id; //暂时不支持跨经营场地查询（前端UI没准备好，2017-12-22 16:40:52）
                }

                break;
            case 1: //店长，经营场地的数据
                $whereOrderSearch["order_freeze_store_id"] = $userOne->user_store_id;
                break;
            case 2: //收银，看自己的数据
                $whereOrderSearch["order_freeze_store_id"] = $userOne->user_store_id;
                $whereOrderSearch["order_freeze_user_id"]  = $userOne->user_id;
                break;

            default:
                # code...
                break;
        }

        $whereOrderSearch2 = "";
        switch ($order_freeze_status) {
            case 'freezeing': //有效订单（支付成功，有部分退款，已经成交的订单）

                $whereOrderSearch2 = "order_freeze_status=100";

                break;
            case 'pay': //已经转支付

                $whereOrderSearch2 = "order_freeze_status=300 and order_freeze_operation_type='UNFREEZE'";
                break;

            default:

                $whereOrderSearch2 = "(order_freeze_status=300 and order_freeze_operation_type='UNFREEZE') or (order_freeze_status=100 and order_freeze_operation_type='FREEZE')";

                break;
        }

        //__组合查询的条件

        $field = ["order_freeze_num", "order_freeze_addtime", "order_freeze_status", "order_freeze_total_amount", "order_freeze_unfree_time"];

        $lists = $order_freeze->where($whereOrderSearch)->where($whereOrderSearch2)->field($field)->order("order_freeze_id desc")->paginate($per_page);
        //echo $order_freeze->getlastsql();die();
        if ($lists) {
            // foreach ($lists as $listsOne) {
            //     if(strpos($listsOne->order_freeze_unfree_time,"1970")!==false)
            //     {
            //         $listsOne->order_freeze_unfree_time="";
            //     }
            // }
        }

        $lists = json_decode(json_encode($lists), 1);
        return ["code" => 1, "message" => "", "data" => $lists];
    }

    /**
     * 资金授权解冻接口
     * @param  string $value [description]
     * @return [type]        [description]
     */
    public function dounfreeze()
    {
        $order_freeze_num = input("order_freeze_num");
        $user_id          = input("user_id");
        $password         = input("password", "", null);
        $total_amount     = input("total_amount", 0);
        $this->verifyPostDataHelper($user_id);
        //判断有没有操作权限

        if (!$order_freeze_num) {
            return ["code" => 0, "message" => "订单号错误", "data" => ""];
        }

        if (!$password) {
            return ["code" => 0, "message" => "操作密码不可为空", "data" => ""];
        }

        //退款权限检测
        $rsRefundBefaultCheck = model("order_freeze")->dounfreezeBefaultCheck($order_freeze_num, $user_id, $password);
        if ($rsRefundBefaultCheck["code"] == 0) {
            return $rsRefundBefaultCheck;
        }
        //__退款权限检测

        $rs = model("order_freeze")->dounfreeze($order_freeze_num, $user_id, $total_amount);

        return $rs;
    }

}
