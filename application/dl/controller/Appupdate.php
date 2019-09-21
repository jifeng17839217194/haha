<?php
namespace app\dl\controller;
use app\dl\controller\Adminbase;

class Appupdate extends Adminbase
{
    public function _initialize()
    {
        parent::_initialize();
    }

    public function index()
    {
        Adminbase::checkActionAuth();
        $appupdate = model("appupdate");
        

        $this->assign('one', $appupdate->getOne());
        return view();
    }

    
    //保存或新增
    public function save()
    {
        Adminbase::checkActionAuth("appupdate/index", "add");
        $appupdate = model("appupdate");

        if(input("post."))
        {
            foreach (input("post.") as $key => $value) {
                $appupdate->setValue($key,$value);
            }
            return ["code" => 1, "message" => "保存成功", "wait" => 1, "url" => url('index')];
        }

        
    }

}
