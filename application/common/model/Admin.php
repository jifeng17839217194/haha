<?php
namespace app\common\model;

use think\Model;
use think\Session;

class Admin extends Model
{
    protected $type = [
        'admin_lastlogintime' => 'timestamp',
        'admin_addtime' => 'timestamp',
    ];
    protected $autoWriteTimestamp = false;

    //  定义全局的查询范围
    protected function base($query)
    {
        
    }

    /**
     * 修改器
     */
    // public function setAdminPasswordAttr($value)
    // {
    //     return $this->passwordSetMd5($value);
    // }

    /**
     * md5多重加密
     */
    public function passwordSetMd5($password)
    {
        return md5(md5(md5($password)));
    }

    /**
     * 会员登录
     * 前置参数不可为空
     */
    public function checkAndLogin($username, $password)
    {
        $rs = $this->isExitsByUsernameEmailPassword($username, $password);
        if ($rs['code'] == 1) {
            $userOne = $rs["data"];
            if ($userOne["admin_active"] == 0) {
                return ['code' => 0, 'data' => '', 'message' => '该账号已经禁止登录'];
            }

            $rolegroup=model("AdminRole")->getAdminPowerArray($userOne["admin_id"]);
            if($rolegroup<=0)
            {
                return ['code' => 0, 'data' => '', 'message' => '该账号没有有效角色'];   
            }
            return ['code' => 1, 'data' => $userOne, 'message' => ''];

        } else {
            return ['code' => 0, 'data' => '', 'message' => '账号密码不正确'];
        }
    }

    /**
     * 基本登录后的操作
     */
    public function afterBaseLogin($userOne)
    {
        $this->save([
            'admin_lastloginip' => request()->ip(),
            'admin_lastlogintime' => time(),
        ], ['admin_id' => $userOne["admin_id"]]);

        //留有session
        Session::set(config("database")["database"] . "admin_id", $userOne["admin_id"]);
        Session::set("admin_id", $userOne["admin_id"]);
        Session::set("admin_nicename", $userOne["admin_nicename"]);
        Session::set("allow_upload_use_ueditor",1);
    }

    /**
     * 用户退出动作
     */
    public function doLogout()
    {
        Session::delete(config("database")["database"] . "admin_id");
        Session::delete(config("database")["database"] . "admin_nicename");
        Session::delete("allow_upload_use_ueditor");
    }

    //以下是Mode层，只做数据处理

    //根据用户名及密码是否存在
    public function isExitsByUsernameEmailPassword($username, $password)
    {
        $userOne = $this->where([
            'admin_username|admin_email' => $username,
            'admin_password' => $this->passwordSetMd5($password),
        ])->find();
        if ($userOne) {
            return ['code' => 1, 'data' => $userOne, 'message' => ''];
        } else {
            return ['code' => 0, 'data' => '', 'message' => ''];
        }
    }
}
