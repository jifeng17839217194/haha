<?php
namespace app\agent\controller;

use app\agent\controller\Agentbase;

class User extends Agentbase
{
    public function _initialize()
    {
        parent::_initialize();
    }

    public function index()
    {
        
        $user    = model("user");
        $where   = ["user_store_id" => input("user_store_id", 0)];
        $keyword = input("get.keyword", "");

        $where["shop_agent_id"] = $this->getMyagentId();
        if ($keyword) {
            $where["user_username|user_realname|user_mobile"] = ["like", "%" . $keyword . "%"];
        }
        $lists = $user->join("__STORE__","store_id=user_store_id","left")->join("__SHOP__","shop_id=store_shop_id","left")->where($where)->paginate(15);

        if ($lists) {
            foreach ($lists as $listsOne) {
                $listsOne->user_role_name = $user->roleId2NiceName($listsOne->user_role);
            }
        }
        $this->assign('lists', $lists);
        return view();
    }

    public function add()
    {
        $user_store_id = input("user_store_id");
        $user = model("user");
        $one  = [];
        if (input("user_id")) {
            $one = $user->find(input("user_id"));
            $user_store_id = $one->user_store_id;
        }

        if(!model("store")->join("__SHOP__","shop_id=store_shop_id","left")->where(["store_id"=>$user_store_id])->find())
        {
            return ["code"=>0,"message"=>"没有操作权限","data"=>""];
        }

        $store_open_reward=db("store")->where(["store_id"=>$user_store_id])->value("store_open_reward");
        $this->assign("store_open_reward",$store_open_reward);
        $this->assign("one", $one);
        return view();
        //继续做角色新增的动作11
    }

    public function delete()
    {
        
        $user_id = input("user_id");
        if ($user_id) {
            $user = model("user");
            if (db("order")->where(["order_user_id" => $user_id])->count() > 0) {
                return ["code" => 0, "message" => "该账号下有交易记录，不可删除", "wait" => 1];
            }

            if(!$user->join("__STORE__","store_id=user_store_id","left")->join("__SHOP__","shop_id=store_shop_id","left")->where(["user_id"=>$user_id])->find())
            {
                return ["code"=>0,"message"=>"没有操作权限","data"=>""];
            }

            $user->where(["user_id" => $user_id])->delete();
            return ["code" => 1, "message" => "删除成功", "wait" => -1, "url" => url('index?user_store_id=' . input("user_store_id/d") . '&target=self')];
        } else {
            return ["code" => 0, "message" => "没有数据删除", "wait" => 1];
        }

    }

    //保存或新增
    public function save()
    {
        
        $user = model("user");

        $user_mobile = input("user_mobile");

        //判断重复
        if ($user_mobile) {
            $wherehave["user_mobile"] = $user_mobile;
            if (input("user_id/d") > 0) {
                $wherehave["user_id"] = ["neq", input("user_id/d")];
            }
            if ($user->where($wherehave)->find()) {
                return ["code" => 0, "message" => $user_mobile . " 已经存在!", "url" => "#"];
            }

            if (!preg_match("/^1[0-9]{10}$/", $user_mobile)) {
                return ["code" => 0, "message" => $user_mobile . " 手机号格式不正确!", "url" => "#"];
            }
        }

        if(!model("store")->join("__SHOP__","shop_id=store_shop_id","left")->where(["store_id"=>input("user_store_id")])->find())
        {
            return ["code"=>0,"message"=>"没有操作权限","data"=>""];
        }

        $newData = [
            'user_mobile'      => input("user_mobile"),
            'user_realname'    => input("user_realname"),
            'user_store_id'    => input("user_store_id"),
            'user_refund_auth' => input("user_refund_auth", 0),
            'user_active'      => input("user_active", 1),
            'user_role'        => input("user_role", 2), //默认是最小权限的“收银员”
            'user_play_reward' => input("user_play_reward", 0),
        ];

        //没有强制要填支付密码（2017-12-5 17:03:16，因为输入数据认证不好判断）
        if (input("user_refund_password", "", null)) {
            if (mb_strlen(input("user_refund_password", "", null)) < 6) {
                return ["code" => 0, "message" => "退款密码至少6位", "url" => "#"];
            }
            $newData['user_refund_password'] = $user->passwordSetMd5(input("user_refund_password", "", null));
        }

        if (input("user_password", "", null)) {
            if (mb_strlen(input("user_password", "", null)) < 6) {
                return ["code" => 0, "message" => "密码至少6位", "url" => "#"];
            }
            $newData['user_password'] = $user->passwordSetMd5(input("user_password", null));
            $newData['user_token']    = md5(request()->domain().getMillisecond() . mt_rand(0, 99999)); //给一个默认的数据;0次登入的情况下要用下；
        }

        if (input("user_id/d") > 0) {
            //$newData["news_updatetime"] = time();
        } else {
            $newData["user_addtime"] = input("user_addtime", time());
        }

        $user->save($newData, input("user_id") ? ['user_id' => input("user_id")] : null);
        return ["code" => 1, "message" => "保存成功", "wait" => -1, "url" => url('index?user_store_id=' . input("user_store_id"))];
    }


    public function qrcode()
    {
        $value        = input("value", "");
        $size         = input("size", 2);
        $downloadname = input("downloadname", false);
        return qrcode($value, $size, $downloadname);
    }
}
