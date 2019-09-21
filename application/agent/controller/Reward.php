<?php
namespace app\agent\controller;

use app\agent\controller\Agentbase;

class Reward extends Agentbase
{
    public function _initialize()
    {
        parent::_initialize();
    }

    public function index()
    {
        
        $reward = model("reward");
        $where  = [];
        $where  = ["reward_store_id" => input("user_store_id", 0)];

        $where["shop_agent_id"] = $this->getMyagentId();

        $lists = $reward->join("__STORE__","store_id=reward_store_id","left")->join("__SHOP__","shop_id=store_shop_id","left")->where($where)->order([])->paginate(15);

        if ($lists) {
            /*$cityData=model("City")->column("city_id,city_name");
        foreach ($lists as $listsOne) {
        $listsOne->cityname= implode(",",self::idGetVal($listsOne->agents_city_id,$cityData));
        }*/
        }

        $this->assign('lists', $lists);
        return view();
    }

    //保存或新增
    public function save()
    {
        
        $reward          = model("reward");
        $reward_store_id = input("user_store_id");
        if (!$reward_store_id) {
            return ["code" => 0, "message" => "reward_store_id参数不可为空", "data" => ""];
        }

        $reward_cash= input("reward_cash", 0,"floatval");

        //判断重复
        $wherehave["reward_cash"]     = $reward_cash;
        $wherehave["reward_store_id"] = $reward_store_id;
        if ($reward->where($wherehave)->find()) {
            return ["code" => 0, "message" => "金额" . input("reward_cash") . " 已经存在!", "url" => "#"];
        }

        
        //跨数据权限判断
        if(!model("store")->join("__SHOP__","shop_id=store_shop_id","left")->where(["store_id"=>$reward_store_id,"shop_agent_id"=>$this->getMyagentId()])->find())
        {
            return ["code"=>0,"message"=>"没有操作权限","data"=>""];
        }
        //__跨数据权限判断



        $newData = [
            'reward_cash' => $reward_cash,
            'reward_store_id' => $reward_store_id,
        ];

        $reward->save($newData);
        return ["code" => 1, "message" => "保存成功", "wait" => -1, "url" => url('index?user_store_id='.$reward_store_id)];
    }



    public function delete()
    {
        
        $reward_id = input("reward_id/a");
        $reward_store_id = input("user_store_id");
        if ($reward_id) {

            //跨数据权限判断
            if(!model("store")->join("__SHOP__","shop_id=store_shop_id","left")->where(["store_id"=>$reward_store_id,"shop_agent_id"=>$this->getMyagentId()])->find())
            {
                return ["code"=>0,"message"=>"没有操作权限","data"=>""];
            }
            //__跨数据权限判断

            $reward = model("reward");
            $reward->where(["reward_id"=>["in", $reward_id],"reward_store_id"=>$reward_store_id])->delete();
            return ["code" => 1, "message" => "删除成功", "wait" => -1, "url" => url('index?user_store_id='.$reward_store_id)];
        } else {
            return ["code" => 0, "message" => "没有数据删除", "wait" => 1];
        }

    }


    public function setdefault()
    {
        
        $reward_id = input("reward_id/d");
        $reward_store_id = input("user_store_id");
        if ($reward_id) {

            //跨数据权限判断
            if(!model("store")->join("__SHOP__","shop_id=store_shop_id","left")->where(["store_id"=>$reward_store_id,"shop_agent_id"=>$this->getMyagentId()])->find())
            {
                return ["code"=>0,"message"=>"没有操作权限","data"=>""];
            }
            //__跨数据权限判断

            $reward = model("reward");
            $reward->isUpdate(true)->save(["reward_is_default"=>0],["reward_is_default"=>1,"reward_store_id"=>$reward_store_id]);
            $reward->isUpdate(true)->save(["reward_is_default"=>1],["reward_id"=>$reward_id,"reward_store_id"=>$reward_store_id]);
            return ["code" => 1, "message" => "设置成功", "wait" => 0, "url" => url('index?user_store_id='.$reward_store_id)];
        } else {
            return ["code" => 0, "message" => "没有数据删除", "wait" => 1];
        }

    }
}
