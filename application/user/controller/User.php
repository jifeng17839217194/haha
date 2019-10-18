<?php
namespace app\user\controller;

use think\Exception;

class User extends Apibase
{

   /*
     *收营账号列表
     *@param  [type]  $user_id           [description]
     *@param  string  $store_name        [description]
     *
     */
    public function user_list()
    {
        try{
            $user_id       = input("user_id");
            $user_store_id = input("user_store_id", 0);

            $this->verifyPostDataHelper($user_id);

            $user    = model("user");
            $userOne = $user->where(["user_id" => $user_id])->field(true)->find();
            if ($userOne->user_role == 2) {
                return json(["code" => 400, "message" => "没有查看权限", "data" => ""]);
            } else {
                $where = [];
                if ($userOne->user_role == 1) {
                    $where['user_role'] = ['in','1,2'];
                }
                if ($user_store_id <= 0) {
                    if ($userOne->user_role == 0) {
                        $store_shop_id          = model("store")->where("store_id", $userOne->user_store_id)->value('store_shop_id');
                        $store_id_array         = model("store")->where("store_shop_id", $store_shop_id)->column("store_id");
                        $where['user_store_id'] = ['in', $store_id_array];
                    } else {
                        $where['user_store_id'] = $userOne->user_store_id;
                    }
                } else {
                    $where['user_store_id'] = $user_store_id;
                }
                $lists = $user->field("user_id,user_realname")
                    ->where($where)
                    ->select();

                return json(["code" => 200, "message" => "请求成功", "data" => $lists]);
            }
        }catch (Exception $e){
            return json(["code" => 400, "message" => "请求失败", "data" =>'']);
        }

    }

}
