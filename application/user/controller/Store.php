<?php
namespace app\user\controller;

use think\Exception;

class Store extends Apibase
{

    /**
     * 获取商户的列表
     * @param  [type] $user_id [description]
     * @return [type]          [description]
     */
    public function store_list()
    {
        try{
            $user_id             = input("user_id");
            if(!$user_id){
                return json(["code" => 400, "message" => "参数错误", "data" =>'']);
            }
            //店长、老板看全部，收银员看自己
            //model("report")->getOrderList($user_id);
            $user    = model("user");
            $userOne = $user->where(["user_id" => $user_id])->field(true)->find();
            if ($userOne->user_role != 0) {
                return json(["code" => 400, "message" => "没有权限查看经营场地列表", "data" => ""]);
            } else {
                $user_store_id = $userOne->user_store_id;
                $store         = model("store");
                $where["store_shop_id"] = $store
                    ->where(["store_id" => $user_store_id])
                    ->value("store_shop_id");
                $shoplist            = model("store")
                    ->where($where)
                    ->field("store_id,store_name")
                    ->order("store_addtime desc")
                    ->select();
                return json(["code" => 200, "message" => "请求成功", "data" => $shoplist]);
            }
        }catch (Exception $e){
            return json(["code" => 400, "message" => "请求失败", "data" =>'']);

        }

    }
}
