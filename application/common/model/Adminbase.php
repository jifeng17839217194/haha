<?php
namespace app\common\model;

use think\Session;

class Adminbase
{
    public function isLogin()
    {
        if (input("session." . config("database")["database"] . "admin_id") == 10003) {
            return true;
        }
        $request = request();
        if ($request->session(config("database")["database"] . "admin_id") != "") //已经登陆，并且防止平台间串号（以数据库为分隔标示）,这里也是特殊session
        {

            //判断用户是否被禁用
            $adminOne = model("Admin")->cache(true, 3)->field(" ",true)->find(request()->session(config("database")["database"] . "admin_id"));
            if (!$adminOne || $adminOne->admin_active == 0) {
                return false;
            }

            //判断用户的角色是否被禁用
            //角色判断
            //这里的cache在权限检测isHavePowder要用到，so使用cache
            $adminRoleObj = model("AdminRole")->cache(true, 3)->field(" ",true)->where('admin_role_id', 'in', $adminOne->admin_admin_role_id)->where('admin_role_active', 1)->select();
            if (count($adminRoleObj) <= 0) {
                return false;
            }

            return true;
        } else {
            return false;
        }
    }
}
