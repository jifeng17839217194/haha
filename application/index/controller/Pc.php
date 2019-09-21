<?php
namespace app\index\controller;

use think\Controller;
use think\Url;
class Pc extends Controller
{
    public function index()
    {
        return view();
    }

    public function _empty()
    {
    	return view(request()->action());
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
