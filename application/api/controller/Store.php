<?php
namespace app\api\controller;

use app\api\controller\Apibase;

//商户开设接口
class Store extends Apibase
{
    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 获取商户的列表
     * @param  [type] $user_id [description]
     * @return [type]          [description]
     */
    public function getstorelist()
    {
        $user_id             = input("user_id");
        $this->verifyPostDataHelper($user_id);
        //店长、老板看全部，收银员看自己
        //model("report")->getOrderList($user_id);
        $user    = model("user");
        $userOne = $user->where(["user_id" => $user_id])->field(true)->find();
        if ($userOne->user_role == 2) {
            return ["code" => 0, "message" => "没有权限查看经营场地列表", "data" => ""];
        } else {
            $user_store_id = $userOne->user_store_id;
            $store         = model("store");
            if ($userOne->user_role == 0) {
                $where["store_shop_id"] = $store->where(["store_id" => $user_store_id])->value("store_shop_id");
            } else {
                $where["store_id"] = $user_store_id;
            }
            $shoplist            = model("store")->where($where)->field("", true)->order("store_addtime desc")->select();
            $rs["list"]          = $shoplist;
            $rs["is_set_reward"] = config("saleversion") >= 3 ? 1 : 0;
            return ["code" => 1, "message" => "", "data" => $rs];
        }
    }

    /**
     * 获取经营场地的基本信息
     * @param  [type] $user_id  [description]
     * @param  [type] $store_id [description]
     * @return [type]           [description]
     */
    public function getstoreone()
    {
        $user_id              = input("user_id");
        $store_id        = input("store_id",0);
        $this->verifyPostDataHelper($user_id);
        $user    = model("user");
        $userOne = $user->where(["user_id" => $user_id])->field(true)->find();
        if ($userOne->user_role == 0) //老板
        {
            $store_id = floatval($store_id);
            if ($store_id > 0) {
//指定了经营场地
                $where["store_id"] = $store_id;
            } else {
                $where["store_id"] = $userOne->user_store_id;
            }

            //老板名下的所有的店铺ID(防止跨店数据)
            $shop_id                = model("store")->where(["store_id" => $userOne->user_store_id])->value("store_shop_id");
            $where["store_shop_id"] = $shop_id;
            //__老板名下的所有的店铺ID

        } else {
            $where["store_id"] = $userOne->user_store_id; //店长，收银员,只能看自己的店

            if ($store_id > 0) {
                //指定了店铺
                $where["store_id "] = $store_id;
            } else {
                $where["store_id "] = $userOne->user_store_id;
            }
        }
        $rs = model("store")->where($where)->find();
        if ($rs) {

            $rs["is_set_reward"] = config("saleversion") >= 3 ? 1 : 0;
            return ["code" => 1, "message" => "", "data" => $rs];
        } else {
            return ["code" => 0, "message" => "经营场地不存在或无权限查看", "data" => ""];
        }
    }
    /*
     *验证新增权限
     *By
     */
    public function storeaddone()
    {
        $user_id              = input("user_id");
        $user_store_id        = input("user_store_id",0);
        $this->verifyPostDataHelper($user_id);

        $user    = model("user");
        $userOne = $user->where(["user_id" => $user_id])->field(true)->find();
        if ($user_store_id <= 0) {
            $user_store_id = $userOne->user_store_id;
        }
        if ($userOne->user_role != 0) {
            return ["code" => 0, "message" => "没有创建权限", "data" => array("user_store_id" => $user_store_id)];
        } else {
            return ["code" => 1, "message" => "", "data" => ""];
        }
    }
    /*
     *验证新增权限
     *By
     */
    public function useraddone()
    {
        $user_id = input("user_id");
        $this->verifyPostDataHelper($user_id);

        $user    = model("user");
        $userOne = $user->where(["user_id" => $user_id])->field(true)->find();

        if ($userOne->user_role == 2) {
            return ["code" => 0, "message" => "没有创建权限", "data" => ""];
        } else {
            return ["code" => 1, "message" => "", "data" => ""];
        }
    }
    /**
     * 新增 经营场地
     * @param  [type]  $user_id           [description]
     * @param  string  $store_name        [description]
     * @param  string  $store_address     [description]
     * @param  string  $store_mobile      [description]
     * @param  integer $store_open_reward [description]
     * @return [type]                     [description]
     */
    public function addstoreone()
    {
        $user_id                     = input("user_id");
        $store_name                  = input("store_name");
        $store_address               = input("store_address");
        $store_mobile                = input("store_mobile");
        $store_open_reward           = input("store_open_reward", 0);
        $store_open_funds_authorized = input("store_open_funds_authorized", 0);
        $store_pay_after_ad_active   = input("store_pay_after_ad_active", 0);
        $store_pay_after_ad          = input("store_pay_after_ad");

        $this->verifyPostDataHelper($user_id);

        $user    = model("user");
        $userOne = $user->where(["user_id" => $user_id])->field(true)->find();

        if ($userOne->user_role != 0) {
            return ["code" => 0, "message" => "没有创建权限", "data" => ""];
        }

        $store_name = trim($store_name);
        if (!$store_name) {
            return ["code" => 0, "message" => "经营场地名称不可为空", "data" => ""];
        }

        $store   = model("store");
        $shop_id = $store->where(["store_id" => $userOne->user_store_id])->value("store_shop_id");

        //判断重复
        $wherehave["store_name"]    = $store_name;
        $wherehave["store_shop_id"] = $shop_id;
        if ($store->where($wherehave)->find()) {
            return ["code" => 0, "message" => $store_name . " 已经存在!", "data" => ""];
        }

        $is_set_reward = config("saleversion") >= 3 ? 1 : 0;

        $savedata = [];
        if ($is_set_reward == 1) {
//有开启打赏的权限
            $savedata["store_open_reward"] = intval($store_open_reward);
        } else {
            $savedata["store_open_reward"] = 0; //没有开启打赏的功能
        }

        $savedata["store_name"]                  = $store_name;
        $savedata['store_pay_after_ad_active']   = $store_pay_after_ad_active; //是否开启广告
        $savedata["store_shop_id"]               = $shop_id;
        $savedata['store_open_funds_authorized'] = $store_open_funds_authorized; //是否开启预授权功能
        $savedata["store_address"]               = $store_address;
        $savedata["store_mobile"]                = $store_mobile;
        $savedata['store_pay_after_ad']          = htmlspecialchars_decode($store_pay_after_ad); //广告内容
        $savedata["store_updatetime"]            = time();

        $store->isUpdate(false)->save($savedata);
        $rs["store_id"] = $store->store_id;
        return ["code" => 1, "message" => "新增成功", "data" => $rs];
    }

    /**
     * 保存 经营场地 信息
     * @param  [type]  $user_id           [description]
     * @param  string  $store_name        [description]
     * @param  string  $store_address     [description]
     * @param  string  $store_mobile      [description]
     * @param  integer $store_open_reward [description]
     * @return [type]                     [description]
     */
    public function savestoreone()
    {
        $user_id                     = input("user_id");
        $store_id                    = input("store_id", 0);
        $store_name                  = input("store_name");
        $store_address               = input("store_address");
        $store_mobile                = input("store_mobile");
        $store_open_reward           = input("store_open_reward", 0);
        $store_open_funds_authorized = input("store_open_funds_authorized", 0);
        $store_pay_after_ad_active   = input("store_pay_after_ad_active", 0);
        $store_pay_after_ad          = input("store_pay_after_ad");

        $this->verifyPostDataHelper($user_id);

        $store_name = trim($store_name);
        if (!$store_name) {
            return ["code" => 0, "message" => "经营场地名称不可为空", "data" => ""];
        }

        $user    = model("user");
        $userOne = $user->where(["user_id" => $user_id])->field(true)->find();

        if ($userOne->user_role == 2) {
            return ["code" => 0, "message" => "没有修改权限", "data" => ""];
        }

        $store   = model("store");
        $shop_id = $store->where(["store_id" => $userOne->user_store_id])->value("store_shop_id");

        if (!$store_id) {
            $store_id = $userOne->user_store_id;
        }

        if ($userOne->user_role == 1 && $store_id != $userOne->user_store_id) {
            return ["code" => 0, "message" => "没有修改权限", "data" => ""];
        }

        //判断重复
        $wherehave["store_name"]    = $store_name;
        $wherehave["store_shop_id"] = $shop_id;
        $wherehave["store_id"]      = ["neq", $store_id];
        if ($store->where($wherehave)->find()) {
            return ["code" => 0, "message" => $store_name . " 已经存在!", "data" => ""];
        }

        $is_set_reward = config("saleversion") >= 3 ? 1 : 0;

        $savedata = [];
        if ($is_set_reward == 1) {
//有开启打赏的权限
            $savedata["store_open_reward"] = intval($store_open_reward);
        } else {
            $savedata["store_open_reward"] = 0; //没有开启打赏的功能
        }

        $savedata["store_name"]                  = $store_name;
        $savedata['store_pay_after_ad_active']   = $store_pay_after_ad_active; //是否开启广告
        $savedata["store_shop_id"]               = $shop_id;
        $savedata['store_open_funds_authorized'] = $store_open_funds_authorized; //是否开启预授权功能
        $savedata["store_address"]               = $store_address;
        $savedata["store_mobile"]                = $store_mobile;
        $savedata['store_pay_after_ad']          = htmlspecialchars_decode($store_pay_after_ad); //广告内容
        $savedata["store_updatetime"]            = time();
        $store->isUpdate(true)->save($savedata, ['store_id' => $store_id]);
        return ["code" => 1, "message" => "修改成功", "data" => ''];
    }

    /**
     * 删除经营场地
     * @param  [type]  $user_id  [description]
     * @param  integer $store_id [description]
     * @return [type]            [description]
     */
    public function deletestoreone()
    {
        $user_id  = input("user_id");
        $store_id = input("store_id", 0);
        $this->verifyPostDataHelper($user_id);

        $user    = model("user");
        $userOne = $user->where(["user_id" => $user_id])->field(true)->find();

        if ($userOne->user_role != 0) {
            return ["code" => 0, "message" => "没有删除权限", "data" => ""];
        }

        $store   = model("store");
        $shop_id = $store->where(["store_id" => $userOne->user_store_id])->value("store_shop_id");

        if (!$store_id) {
//如果没有指定store_id，基本是不可能的；
            $store_id = $userOne->user_store_id;
        }

        //老板名下的所有的店铺ID(防止跨店数据)
        $shop_id                 = model("store")->where(["store_id" => $userOne->user_store_id])->value("store_shop_id");
        $where["store_shop_id "] = $shop_id;
        //__老板名下的所有的店铺ID

        $where["store_id"] = $store_id;

        if (db("user")->where(["user_store_id" => $store_id])->count() > 0) {
            return ["code" => 0, "message" => "该店铺有下属员工，不可删除", "data" => ""];
        }
        $rs = $store->where($where)->delete(); //删除
        if ($rs) {
            return ["code" => 1, "message" => "删除成功", "data" => ""];
        } else {
            return ["code" => 0, "message" => "没有记录删除", "data" => ""];
        }
    }
    /*
     *收营账号列表
     *@param  [type]  $user_id           [description]
     *@param  string  $store_name        [description]
     *
     */
    public function getUserList()
    {
        $user_id       = input("user_id");
        $user_store_id = input("user_store_id", 0);
        $page          = input("page", 0);
        $per_page      = input("per_page", 0);

        $this->verifyPostDataHelper($user_id);
        if ($per_page > 100) {
            $per_page = 100;
        }
        $user    = model("user");
        $userOne = $user->where(["user_id" => $user_id])->field(true)->find();
        if ($userOne->user_role == 2) {
            return ["code" => 0, "message" => "没有查看权限", "data" => ""];
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
            $lists = $user->field("user_id,user_realname,user_mobile,user_role,user_addtime,user_play_reward,user_last_login_time")->where($where)->paginate($per_page);

            if ($lists) {
                foreach ($lists as $listsOne) {
                    $listsOne->user_role_name = $user->roleId2NiceName($listsOne->user_role);
                }
            }
            $lists = json_decode(json_encode($lists), 1);
            return ["code" => 1, "message" => "", "data" => $lists];
        }
    }
    /**
     * 新增 收营员
     * @param  [type]  $user_id           [description]
     * @param  integer $user_store_id [description]
     * @param  string  $user_realname        [description]
     * @param  string  $user_mobile     [description]
     * @param  string  $user_password      [description]
     * @param  integer $user_refund_auth [description]
     * @param  string $user_refund_password [description]
     * @param  integer $user_role [description]
     * @param  integer $user_active [description]
     */
    public function adduserone()
    {
        $user_id              = input("user_id");
        $user_store_id        = input("user_store_id", 0);
        $user_realname        = input("user_realname");
        $user_mobile          = input("user_mobile");
        $user_password        = input("user_password");
        $user_refund_auth     = input("user_refund_auth", 0);
        $user_refund_password = input("user_refund_password", "", null);
        $user_role            = input("user_role", 2);
        $user_active          = input("user_active", 1);
        $user_play_reward     = input("user_play_reward", 0);

        $this->verifyPostDataHelper($user_id);

        $user_realname = trim($user_realname);
        if (!$user_realname) {
            return ["code" => 0, "message" => "收银员名称", "data" => ""];
        }

        $user    = model("user");
        $userOne = $user->where(["user_id" => $user_id])->field(true)->find();

        if ($userOne->user_role == 2) {
            return ["code" => 0, "message" => "没有创建权限", "data" => ""];
        }
        if ($userOne->user_role != 0 && $user_role != 2) {
            return ["code" => 0, "message" => "没有权限创建老板与店长", "data" => ""];
        }
        //判断重复
        if ($user_mobile) {
            $wherehave["user_mobile"] = $user_mobile;

            if ($user->where($wherehave)->find()) {
                return ["code" => 0, "message" => $user_mobile . " 已经存在!", "data" => ""];
            }

            if (!preg_match("/^1[0-9]{10}$/", $user_mobile)) {
                return ["code" => 0, "message" => $user_mobile . " 手机号格式不正确!", "data" => ""];
            }
        }
        if ($user_store_id <= 0) {
            $user_store_id = $userOne->user_store_id;
        }
        $newData = [
            'user_mobile'      => $user_mobile,
            'user_realname'    => $user_realname,
            'user_store_id'    => $user_store_id,
            'user_refund_auth' => $user_refund_auth,
            'user_active'      => $user_active,
            'user_role'        => $user_role, //默认是最小权限的“收银员”
            'user_play_reward' => $user_play_reward,
        ];

        //没有强制要填支付密码（2017-12-5 17:03:16，因为输入数据认证不好判断）
        if ($user_refund_password) {
            if (mb_strlen($user_refund_password) < 6) {
                return ["code" => 0, "message" => "退款密码至少6位", "data" => ""];
            }
            $newData['user_refund_password'] = $user->passwordSetMd5($user_refund_password);
        }
        if (!$user_password) {
            return ["code" => 0, "message" => "请填写密码！", "data" => ""];
        }
        if ($user_password) {
            if (mb_strlen($user_password) < 6) {
                return ["code" => 0, "message" => "密码至少6位", "data" => ""];
            }
            $newData['user_password'] = $user->passwordSetMd5($user_password);
            $newData['user_token']    = md5(request()->domain() . getMillisecond() . mt_rand(0, 99999)); //给一个默认的数据;0次登入的情况下要用下；
        }
        $newData["user_addtime"] = time();
        $user->isUpdate(false)->save($newData);
        $rs["user_id"] = $user->user_id;
        return ["code" => 1, "message" => "新增成功", "data" => array("user_store_id" => $user_store_id)];
    }
    /*
     *By
     *收银员详情
     * @param  [type]  $user_id           [description]
     * @param  integer $user_store_id [description]
     */
    public function getUserDetail()
    {
        $user_id         = input("user_id", 0);
        $current_user_id = input("current_user_id", 0);
        $user_store_id   = input("user_store_id", 0);

        $this->verifyPostDataHelper($user_id);

        $user_store_id = $user_store_id;
        $user          = model("user");
        $one           = [];
        if ($current_user_id) {
            $one           = $user->find($current_user_id);
            $user_store_id = $one->user_store_id;
        }
        $store_open_reward = db("store")->where(["store_id" => $user_store_id])->value("store_open_reward");
        return ["code" => 1, "message" => "", "data" => array("store_open_reward" => $store_open_reward, "one" => $one)];
    }
    /**
     *  收营员 修改
     * @param  [type]  $user_id           [description]
     * @param  string  $user_realname        [description]
     * @param  string  $user_mobile     [description]
     * @param  string  $user_password      [description]
     * @param  integer $user_refund_auth [description]
     * @param  string $user_refund_password [description]
     * @param  integer $user_role [description]
     * @param  integer $user_active [description]
     */
    public function useronesave()
    {
        $user_id              = input("user_id");
        $current_user_id      = input("current_user_id", 0);
        $user_mobile          = input("user_mobile");
        $user_password        = input("user_password");
        $user_refund_auth     = input("user_refund_auth", 0);
        $user_refund_password = input("user_refund_password", "", null);
        $user_role            = input("user_role", 2);
        $user_active          = input("user_active", 1);

        $this->verifyPostDataHelper($user_id);

        $user    = model("user");
        $userOne = $user->where(["user_id" => $user_id])->field(true)->find();

        if ($userOne->user_role == 2) {
            return ["code" => 0, "message" => "修改权限", "data" => ""];
        }
        if ($userOne->user_role != 0 && $user_role == 0) {
            return ["code" => 0, "message" => "没有权限修改账号为老板!", "data" => ""];
        }
        if (!preg_match("/^1[0-9]{10}$/", $user_mobile)) {
            return ["code" => 0, "message" => $user_mobile . " 手机号格式不正确!", "data" => ""];
        }
        $newData = [
            'user_mobile'      => $user_mobile,
            'user_refund_auth' => $user_refund_auth,
            'user_active'      => $user_active,
            'user_role'        => $user_role, //默认是最小权限的“收银员”
        ];

        //没有强制要填支付密码（2017-12-5 17:03:16，因为输入数据认证不好判断）
        if ($user_refund_password) {
            if (mb_strlen($user_refund_password) < 6) {
                return ["code" => 0, "message" => "退款密码至少6位", "data" => ""];
            }
            $newData['user_refund_password'] = $user->passwordSetMd5($user_refund_password);
        }

        if ($user_password) {
            if (mb_strlen($user_password) < 6) {
                return ["code" => 0, "message" => "密码至少6位", "data" => ""];
            }
            $newData['user_password'] = $user->passwordSetMd5($user_password);
            $newData['user_token']    = md5(request()->domain() . getMillisecond() . mt_rand(0, 99999)); //给一个默认的数据;0次登入的情况下要用下；
        }
        $newData["user_addtime"] = time();

        $user->save($newData, ['user_id' => $current_user_id]);
        $user_store_id = $user->where("user_id", $current_user_id)->value("user_store_id");
        return ["code" => 1, "message" => "修改成功", "data" => array("user_store_id" => $user_store_id)];
    }
    /**
     * 删除收银员
     * @param  [type]  $user_id  [description]
     * @param  integer $current_user_id [description]
     * @return [type]            [description]
     */
    public function deleteuserone()
    {
        $user_id         = input("user_id");
        $current_user_id = input("current_user_id", 0);

        $this->verifyPostDataHelper($user_id);

        $user    = model("user");
        $userOne = $user->where(["user_id" => $user_id])->field(true)->find();

        if ($userOne->user_role == 2) {
            return ["code" => 0, "message" => "没有删除权限", "data" => ""];
        }

        if ($current_user_id) {
            $user = model("user");
            if (db("order")->where(["order_user_id" => $current_user_id])->count() > 0) {
                return ["code" => 0, "message" => "该账号下有交易记录，不可删除", "wait" => 1];
            }
            $user->where(["user_id" => $current_user_id])->delete();
            return ["code" => 1, "message" => "删除成功", "data" => ''];
        } else {
            return ["code" => 0, "message" => "没有数据删除", "wait" => 1];
        }
    }
}
