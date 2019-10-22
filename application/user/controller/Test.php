<?php
/**
 * Created by PhpStorm.
 * User: ZGL
 * Date: 2019/10/21
 * Time: 15:08
 */

namespace app\user\controller;

use think\Db;
use think\Exception;

class Stores extends Apibase
{

    /**
     * 门店管理/门店列表
     * @param string $user_id
     * @param int $page
     * @param int $limit
     * @return \think\response\Json
     */
    public function stores_list($user_id = "")
    {
        try {
            $userOne = Db::name('user')->where(["user_id" => $user_id])->field(true)->find();
            if($userOne['user_role']!=0){
                return json(["code" => 400, "msg" => "权限不足", "data" =>'']);
            }
            $paginate = $this->request->get('paginate', 10);
            //接口验证
            $this->verifyPostDataHelper($user_id);
            $shop_id = Db::name('user')->alias('u')
                ->join('qs_store s', 'u.user_store_id=s.store_id')
                ->field('s.store_shop_id')
                ->where('u.user_id', $user_id)
                ->find();
            $result['pagination']= Db::name('store')->where('store_shop_id', $shop_id['store_shop_id'])->field('store_id,store_name,store_address')->paginate($paginate);


            if ($result) {
                return json(["code" => 200, "message" => "请求成功", "data" => $result]);
            } else {
                return json(["code" => 400, "message" => "请求失败", "data" => null]);
            }
        } catch (Exception $e) {
            return json(["code" => 400, "message" => "请求失败", "data" => '']);

        }

    }

}