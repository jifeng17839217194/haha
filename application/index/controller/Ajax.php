<?php
namespace app\index\controller;
use think\Controller;
use think\Url;
class Ajax extends Controller
{
    public function index()
    {
        return view();
    }

    public function _empty()
    {
    	return view(request()->action());
    }

    public function mobile()
    {
        session("thisname",time());
        return view();
    }

    public function dopostmobile()
    {
        session(session("thisname"),(intval(session(session("thisname"))?:0))+1);
    }

    public function getpostmobile()
    {
        return session(session("thisname"));
    }

    public function mobileinnert()
    {
        return view();
    }

    //PC端的javascript代码
    public function nwdata()
    {
        $config = model("Sysconfig")->getConfig();
        $this->assign("config",$config);

    	return view("nwdata");
    	die();
    }
}
