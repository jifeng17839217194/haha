<?php
namespace app\dl\controller;
use app\dl\controller\Adminbase;
class Sysconfig extends Adminbase
{
    public function _initialize()
    {
        parent::_initialize();
    }

    public function baseconfig()
    {
        Adminbase::checkActionAuth();
        $this->assign("one",model("Sysconfig")->getConfig());
        return view();
    }

    
    public function save()
    {
        Adminbase::checkActionAuth("sysconfig/baseconfig","add");
        $sysconfig = model("Sysconfig");
        $post=input("post.");
        //$post["shareinfo"]=input("post.shareinfo","",null);
        $sysconfig->updateConfig($post);
        return ["code" => 1, "message" => "保存成功", "wait" =>1];
    }

}
