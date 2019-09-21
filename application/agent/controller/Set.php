<?php
namespace app\agent\controller;
use app\agent\controller\Agentbase;
use think\Db;
class Set extends Agentbase
{
    public function _initialize()
    {
        parent::_initialize();
    }

    public function index()
    {
        $agent = model("agent");
        $agentOne = $agent->find($this->getMyAgentId());
        
        $this->assign('agentOne', $agentOne);
        return view();
    }

    //保存或新增
    public function save()
    {
        $saveData=[];
        if(input("shop_head_picture"))
        {
            $saveData["shop_head_picture"]=input("shop_head_picture");
        }

        if(input("shop_tel"))
        {
            $saveData["shop_tel"]=input("shop_tel");
        }

        model("shop")->save($saveData,["shop_id"=>$this->getMyShopId()]);

        return ["code" => 1, "msg" => "保存成功", "wait" => 2, "url" =>url("index")];
    }

}
