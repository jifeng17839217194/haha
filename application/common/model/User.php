<?php
namespace app\common\model;

use think\Model;

class User extends Model
{
    protected $type = [
        'user_addtime'        => 'timestamp',
        'user_last_logintime' => 'timestamp',
        'user_zfb'            => 'array',
    ];
    protected $autoWriteTimestamp = false;

    //  定义全局的查询范围
    protected function base($query)
    {

    }

    //设置user token随机数
    public function getNewToken($user_id = "")
    {
        return md5(time() . $user_id . mt_rand(0, 999999999));
    }

    /**
     * 用户新增（没有使用到，2017-12-11 11:39:57）
     * param(array)
     * 必须参数 $param["user_username|user_email|user_mobile"],$param["user_password"]
     */
    public function addOne($param)
    {
        //没有注册主账号
        if (!isset($param["user_mobile"]) && !isset($param["user_email"]) && !isset($param["user_username"])) {
            if (!isset($param["user_mobile"])) {
                return ["code" => 0, "message" => "手机号不可为空", "data" => ""];
            }

            if (!isset($param["user_email"])) {
                return ["code" => 0, "message" => "邮箱不可为空", "data" => ""];
            }

            if (!isset($param["user_username"])) {
                return ["code" => 0, "message" => "用户名不可为空", "data" => ""];
            }

        }

        //密码设置
        if (!isset($param["user_password"])) {
            return ["code" => 0, "message" => "密码不可为空", "data" => ""];
        }
        if (strlen($param["user_password"]) < 6) {
            return (["code" => 0, "message" => "密码至少6位", "data" => ""]);
        }
        $param["user_password"] = $this->passwordSetMd5($param["user_password"]);
        //__密码设置

        //这里只是数据格式检测,重复检测
        if (isset($param["user_mobile"])) {
            if (!preg_match("/1[0-9]{10}/i", $param["user_mobile"])) {
                return (["code" => 0, "message" => "手机号格式不正确", "data" => ""]);
            }
            if ($this->where(["user_mobile" => $param["user_mobile"]])->count() > 0) {
                return (["code" => 0, "message" => "手机号已经存在", "data" => ""]);
            }
        }
        if (isset($param["user_email"])) {
            if (!preg_match("/^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,10})+$/i", $param["user_email"])) {
                return (["code" => 0, "message" => "邮箱号格式不正确", "data" => ""]);
            }
            if ($this->where("user_email", $param["user_email"])->count() > 0) {
                return (["code" => 0, "message" => "邮箱已经被注册", "data" => ""]);
            }
        }
        if (isset($param["user_username"])) {
            if ($param["user_username"] == "" || strlen($param["user_username"]) < 2) {
                return (["code" => 0, "message" => "用户名格式不正确", "data" => ""]);
            }
            if ($this->where("user_username", $param["user_username"])->count() > 0) {
                return (["code" => 0, "message" => "用户名已经被注册", "data" => ""]);
            }
        }
        //__这里只是数据格式检测

        //设置默认的昵称
        if (!isset($param["user_nicename"])) {
            if (isset($param["user_mobile"])) {
                $param["user_nicename"] = substr($param["user_mobile"], 0, 3) . "****" . substr($param["user_mobile"], -4, 4);
            }

            if (isset($param["user_username"])) {
                $param["user_nicename"] = $param["user_username"];
            }

            if (isset($param["user_email"])) {
                $param["user_nicename"] = explode("@", $param["user_email"])[0];
            }

        }

        if (!isset($param["user_addtime"])) {
            $param["user_addtime"] = time();
        }

        if (!isset($param["user_recommend_user_id"])) {
            $param["user_recommend_user_id"] = 0;
        }

        $param["user_token"] = md5(getMillisecond() . mt_rand(0, 99999)); //给一个默认的数据;0次登入的情况下要用下；

        $this->data($param)->isupdate(false)->save();
        return (["code" => 1, "message" => "", "data" => ["user_id" => $this->user_id]]);
    }

    /**
     * md5多重加密
     */
    public function passwordSetMd5($password)
    {
        return md5(md5(md5($password)));
    }

    /*用户登陆*/
    public function login($accountname, $password, $updateToken = false)
    {
        if ($accountname == "" || $password == "") {
            return (["code" => 0, "message" => "账号或密码不可为空", "data" => ""]);
        } else {
            $userone = $this->where(["user_mobile|user_email|user_username" => $accountname, "user_password" => $this->passwordSetMd5($password)])->find();
            //dump($userone);die();
            if ($userone) {
                if ($userone["user_active"] == 0) {
                    return (["code" => 0, "message" => "账户不可用", "data" => ""]);
                } else {
                    if ($updateToken) //更新user_token
                    {
                        //要多端同时登入，（如，一个账号PC端，与APP同时保存登陆；）不再更新 user_token 2017-12-23 15:03:34
                        //$userone->user_token = md5(getMillisecond() . $userone->user_id . mt_rand(0, 999));
                        //$userone->save();
                    }
                    return (["code" => 1, "message" => "", "data" => $userone]);
                }
            } else {
                return (["code" => 0, "message" => "账号或密码错误", "data" => ""]);
            }
        }
    }

    //用户充值/用户流程变更
    //用户ID,金额,说明,关联ID,关联数据,是否变更余额(在线支付就不用变更)
    public function recharge($userid, $cash, $type, $title, $aboutid, $absoutcode = "", $isUpdateUbalance = true)
    {
        //修改余额
        //echo $userid;die();
        $User = $this->find($userid);
        if ($isUpdateUbalance == true) {
            $user_balance       = round($User->user_balance + $cash, 2);
            $User->user_balance = $user_balance;
            $User->save();
        } else {
            $user_balance = $User->user_balance;
        }

        //新增记录
        $data["cashrecord_type"]        = $type;
        $data["cashrecord_cash"]        = $cash;
        $data["cashrecord_from_obj_id"] = 0;
        $data["cashrecord_balance"]     = $user_balance; //余额
        $data["cashrecord_aboutid"]     = $aboutid;
        $data["cashrecord_aboutcode"]   = $absoutcode;
        $data["cashrecord_title"]       = $title;
        model("CashRecord")->addOne($userid, $data);
    }

    //根据用户名及密码是否存在
    public function isExitsByUsernameEmailPassword($username, $password)
    {
        $userOne = $this->where([
            'user_username|user_email|user_mobile' => $username,
            'user_password'                        => $this->passwordSetMd5($password),
        ])->find();
        if ($userOne) {
            return ['code' => 1, 'data' => $userOne, 'message' => ''];
        } else {
            return ['code' => 0, 'data' => '', 'message' => '账号或密码错误'];
        }
    }

    //注册成功后的业务逻辑
    public function afterZhuche($user_id = '')
    {
        $rs = model("tbk")->addPid($user_id);
        if ($rs["code"] == 1) {
            $this->save(["user_tbk_ad_id" => $rs["data"]["adzoneId"]], ["user_id" => $user_id]);
        } else {
            model("sms")->sendSms("15968890526", "创建淘客AD_ID失败,user_id=>" . $user_id);
        }
        //model("Mycoupon")->lingQuan($user_id,2308,false,"注册");
    }

    /**
     * 基本账号有效性检测
     * 用于API接口验证
     * @param  [type] $user_id [description]
     * @return [type]          [description]
     */
    public function checkuseractive($user_id)
    {
        $userOne = model("user")->join("__STORE__", "store_id=user_store_id", "left")->join("__SHOP__", "shop_id=store_shop_id", "left")->where(["user_id" => $user_id])->field(["qs_user.*", "shop_id", "store_id", "shop_active", "store_name"])->find();
        //验证操作员有效性
        if (!$userOne) {
            return ["code" => 0, "message" => "该操作账号[$user_id]不存在", "data" => ""];
        }
        if (!$userOne->user_active) {
            return ["code" => 0, "message" => "该操作账号已经被禁用", "data" => ""];
        }
        //验证店铺的有效性
        if (!$userOne->shop_active) {
            return ["code" => 0, "message" => "该商户已经被禁用", "data" => ""];
        }

        return ["code" => 1, "message" => "", "data" => ["userOne" => $userOne]];
    }

    /**
     * 角色名（放回中文）
     * ，2017-12-11
     * @param  [type] $role_id [枚举型，跟数据库表字段备注统一]
     * @return [type]          [description]
     */
    public function roleId2NiceName($role_id_Or_Object)
    {
        $role_name="";
        if(is_numeric($role_id_Or_Object))
        {
            $role_id=$role_id_Or_Object;
        }
        else
        {
            $role_id=$role_id_Or_Object->user_role; 
            // if($role_id_Or_Object->user_parking_chanenl_value)
            // {
            //     $role_name="通道收费";
            // }
        }
        $data[0] = "老板";
        $data[1] = "店长";
        $data[2] = "收银员";
        $role_name = $role_name==""?(isset($data[$role_id]) ? $data[$role_id] : "未知角色(" . $role_id . ")"):$role_name;



        return $role_name;
    }
    /*
    * 获取收银员信息
    */
    public function get_user_info($parking_id)
    {
     if(empty($parking_id)) return 0;
     $user_id=$this->where("user_store_id",$parking_id)->order('user_id asc')->value("user_id");
     return $user_id;
    }
}
