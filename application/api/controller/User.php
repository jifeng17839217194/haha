<?php
namespace app\api\controller;

use app\api\controller\Apibase;
use think\captcha\Captcha;

class User extends Apibase
{
    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 登入口子
     * @param  string $user_name     [description]
     * @param  string $user_password [description]
     * @return [type]                [description]
     */
    public function login()
    {

        $captcha_type       = input("captcha_type", "image"); //验证码类型
        $captcha_value      = input("captcha_value");
        $captcha_identifier = input("captcha_identifier", "");
        $user_pushtoken     = input("user_pushtoken", ""); //ios\android才有这个数据
        switch ($captcha_type) {
            case 'image':

                if (!$captcha_value) {
                    return ["code" => 0, "message" => "验证码不可为空", "data" => []];
                }
                $captcha = new Captcha();
                if (!$captcha->check($captcha_value, $captcha_identifier)) {
                    return ["code" => 0, "message" => "验证码错误", "data" => []];
                }
                break;

            default:
                # code...
                break;
        }

        //有效性检测
        $user_name     = input("user_name", "", "trim");
        $user_password = input("user_password", "", "trim");
        if (!($user_name) || (!$user_password)) {
            return ["code" => 0, "message" => "用户名或密码不可为空", "data" => []];
        }
        $store = model("store");
        $rs    = $store->login($user_name, $user_password, true);

        if ($rs["code"] == 1) {
            $rsdata["user_id"]                     = $rs["data"]["user_id"];
            $rsdata["user_token"]                  = $rs["data"]["user_token"];
            $rsdata["store_name"]                  = $rs["data"]["store_name"];
            $rsdata["user_role"]                   = $rs["data"]["user_role"];
            $rsdata["user_role_cn"]                = model("user")->roleId2NiceName($rs["data"]["user_role"]);
            $rsdata["user_realname"]               = $rs["data"]["user_realname"];
            $rsdata["user_store_id"]               = $rs["data"]["user_store_id"];
            $rsdata["user_refund_auth"]            = $rs["data"]["user_refund_auth"];
            $rsdata["store_open_funds_authorized"] = $rs["data"]["store_open_funds_authorized"];
            $rsdata["store_open_reward"]=$rs["data"]["store_open_reward"];
            
            $rsdata["h5_url"] = request()->domain() . url("cmd/oauth/qrcode?uid=" . $rs["data"]["user_id"]);

            //更新数据
            $updateData = [];
            if ($user_pushtoken) {
                $updateData["user_pushtoken"] = $user_pushtoken; //更新极光推送ID
            }
            if (count($updateData) != 0) {
                model("user")->isUpdate(true)->save($updateData, ["user_id" => $rs["data"]["user_id"]]);
            }

            //__更新数据

            return ["code" => 1, "message" => "", "data" => $rsdata];
        } else {
            return $rs;
        }

    }

    /**
     * 更新用户登入密码
     * @param  string $user_id     [description]
     * @param  string $oldpassword [旧密码]
     * @param  string $newpassword [新密码]
     * @return [type]              [description]
     */
    public function updateloginpassword()
    {
        $user_id     = input("user_id", "");
        $oldpassword = input("oldpassword", "", null);
        $newpassword = input("newpassword", "", null);

        $this->verifyPostDataHelper($user_id);
        $user    = model("user");
        $userOne = $user->where(["user_id" => $user_id])->find();
        if ($userOne->user_password != $user->passwordSetMd5($oldpassword)) {
            return ["code" => 0, "message" => "原密码错误", "data" => ""];
        }
        if (strlen($newpassword) < 6) {
            return ["code" => 0, "message" => "密码不能少于6位", "data" => ""];
        }

        $userOne->user_password = $user->passwordSetMd5($newpassword);
        $user_token             = md5(request()->domain() . getMillisecond() . $newpassword . mt_rand(999, 99999));
        $userOne->user_token    = $user_token;
        $userOne->save();
        $rsdata["user_token"] = $user_token;
        return ["code" => 1, "message" => "", "data" => $rsdata];
    }

    /**
     * 更新用户退款密码
     * @param  string $user_id     [description]
     * @param  string $oldpassword [旧密码]
     * @param  string $newpassword [新密码]
     * @return [type]              [description]
     */
    public function updatepaypassword()
    {
        $user_id     = input("user_id");
        $oldpassword = input("oldpassword", "", null);
        $newpassword = input("newpassword", "", null);

        $this->verifyPostDataHelper($user_id);
        $user    = model("user");
        $userOne = $user->where(["user_id" => $user_id])->find();
        if ($userOne->user_refund_auth == 0) {
            return ["code" => 0, "message" => "该账号没有退款权限", "data" => ""];
        }
        if ($userOne->user_refund_password != $user->passwordSetMd5($oldpassword)) {
            return ["code" => 0, "message" => "原退款密码错误", "data" => ""];
        }
        if (strlen($newpassword) < 6) {
            return ["code" => 0, "message" => "密码不能少于6位", "data" => ""];
        }

        $userOne->user_refund_password = $user->passwordSetMd5($newpassword);
        $userOne->save();
        return ["code" => 1, "message" => "", "data" => []];
    }

    /**
     * 收银员绑定收款聚合码
     * @return [type] [description]
     */
    public function bindqrcode()
    {
        $user_id   = input("user_id");
        $qrcodeval = input("qrcodeval", "", null);
        $this->verifyPostDataHelper($user_id);

        $short_url= model("short_url");
        $rs=$short_url->bindqrcode($user_id,$qrcodeval);
        return ["code"=>$rs["code"],"message"=>$rs["message"],"data"=>""];
    }
}
