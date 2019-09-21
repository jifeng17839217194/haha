<?php
namespace app\agent\controller;

use app\agent\controller\Agentbase;

class Store extends Agentbase
{
    public function _initialize()
    {
        parent::_initialize();
    }

    public function index()
    {

        $store = model("store");

        $where                  = ["store_shop_id" => input("store_shop_id", 0)];
        $keyword                = input("get.keyword", "");
        $where["shop_agent_id"] = $this->getMyagentId();
        if ($keyword) {
            $where["store_name"] = ["like", "%" . $keyword . "%"];
        }
        $lists = $store->join("__SHOP__", "shop_id=store_shop_id")->where($where)->paginate(15);

        if ($lists) {
            /*$cityData=model("City")->column("city_id,city_name");
        foreach ($lists as $listsOne) {
        $listsOne->cityname= implode(",",self::idGetVal($listsOne->agents_city_id,$cityData));
        }*/
        }

        $this->assign('lists', $lists);
        return view();
    }

    public function add()
    {

        $store = model("store");
        $one   = [];
        if (input("store_id")) {
            $one = $store->find(input("store_id"));
        }
        $this->assign("one", $one);
        return view();
        //继续做角色新增的动作11
    }

    public function delete()
    {
        $store_id = input("store_id");
        if ($store_id) {

            //权限范围判断
            if (!model("shop")->join("__STORE__", "shop_id=store_shop_id", "right")->where(["shop_agent_id" => $this->getMyagentId(), "store_id" => $store_id])->find()) {
                return ["code" => 0, "message" => "没有操作权限", "data" => ""];
            }
            //__权限范围判断

            $store = model("store");

            if (db("user")->where(["user_store_id" => $store_id])->count() > 0) {
                return ["code" => 0, "message" => "该店铺有下属员工，不可删除", "wait" => 1];
            }
            $store->where(["store_id" => $store_id])->delete(); //不支持批量删除，因为要查询下面有没有用户
            return ["code" => 1, "message" => "删除成功", "wait" => -1, "url" => url('index?store_shop_id=' . input("store_shop_id/d") . '&target=self')];
        } else {
            return ["code" => 0, "message" => "没有数据删除", "wait" => 1];
        }

    }

    //保存或新增
    public function save()
    {

        $store = model("store");

        //权限范围判断
        if (!model("shop")->where(["shop_agent_id" => $this->getMyagentId(), "shop_id" => input("store_shop_id/d")])->find()) {
            return ["code" => 0, "message" => "没有操作权限", "data" => ""];
        }
        //__权限范围判断

        //判断重复
        $wherehave["store_name"] = input("store_name");
        if (input("store_id/d") > 0) {
            $wherehave["store_id"] = ["neq", input("store_id/d")];
        }
        if ($store->where($wherehave)->find()) {
            return ["code" => 0, "message" => input("store_name") . " 已经存在!", "url" => "#"];
        }

        $store_open_reward           = input("store_open_reward/d", 0);
        $store_open_funds_authorized = input("store_open_funds_authorized/d", 0);

        if (config('saleversion') == 1) //基础版本，不开启打赏功能
        {
            $store_open_reward = 0;
        }

        if (config('saleversion') <= 2) {
            $store_open_funds_authorized = 0;
        }

        $newData = [
            'store_name'                  => input("store_name"),
            'store_shop_id'               => input("store_shop_id/d"),
            'store_address'               => input("store_address", ""),
            'store_open_reward'           => $store_open_reward,
            'store_open_funds_authorized' => $store_open_funds_authorized,
            'store_mobile'                => input("store_mobile", ""),
            "store_pay_after_ad"          => input("store_pay_after_ad", "", null),
            "store_pay_after_ad_active"   => input("store_pay_after_ad_active", 0),
        ];
        
        if (input("store_id/d") > 0) {
            $newData["store_updatetime"] = time();
        } else {
            $newData["store_addtime"] = input("store_addtime", time());
        }

        $store->save($newData, input("store_id") ? ['store_id' => input("store_id")] : null);
        return ["code" => 1, "message" => "保存成功", "wait" => -1, "url" => url('index?store_shop_id=' . input("store_shop_id/d") . '&target=self')];
    }

}
