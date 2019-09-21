<?php
namespace app\common\model;

use think\Session;

class Agentbase
{
    public function isLogin()
    {
        $request = request();
        if ($request->session(config("database")["database"] . "agent_id") != "") //已经登陆，并且防止平台间串号（以数据库为分隔标示）,这里也是特殊session
        {
            //判断用户是否被禁用
            $userOne = model("agent")->cache(true, 3)->field(" ",true)->where("agent_id",request()->session(config("database")["database"] . "agent_id"))->find();
            if (!$userOne || $userOne->agent_active == 0) {
                return false;
            }

            return true;
        } else {
            return false;
        }
    }
}
