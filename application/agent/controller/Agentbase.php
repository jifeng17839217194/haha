<?php
namespace app\agent\controller;
use think\Controller;
use think\Cookie;
use think\Cache;
class Agentbase extends Controller
{
    public function _initialize()
    {
        $this->checkIsLogin();
    }

    public function getMyagentId()
    {
        return session("agent_id");
    }


    //基本是否登录检测
    public function checkIsLogin()
    {
        $request = request();
        $agentbase = model("agentbase");
        $rs = $agentbase->isLogin();
        if ($rs == false) {
        //没有登录
            if (!($request->controller() == "Index" && $request->action() == "login")) {
                //不在登录页
                Cookie::set('HTTP_REFERER_Manage', $request->url(true), 3600);
                echo ("<script>top.window.location.href='" . url("index/login") . "'</script>");die();
            }

        } else {
            if (($request->controller() == "Index" && $request->action() == "login")) {
//在登录页上
                echo ("<script>top.window.location.href='" . url("index/index") . "'</script>");die();
            }

        }

    }
}
