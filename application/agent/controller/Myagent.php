<?php
namespace app\agent\controller;

use app\agent\controller\Agentbase;

class Myagent extends Agentbase
{
    public function _initialize()
    {
        parent::_initialize();
    }

    public function index()
    {
        
        $agent   = model("agent");
        $where   = [];
        $keyword = input("get.keyword", "");
        if ($keyword) {
            $where["agent_company_name"] = ["like", "%" . $keyword . "%"];
        }
        $where["agent_parent_agent_id"] = session("agent_id");
        $lists = $agent->where($where)->paginate(15);

        if ($lists) {
            foreach ($lists as $listsOne) {
                $listsOne->agent_son_count= $agent->where(["agent_parent_agent_id"=>$listsOne->agent_id])->count();
            }
        }

        $this->assign('lists', $lists);
        return view();
    }

    
    public function sonagent()
    {
        

        $agent   = model("agent");
        
        $where["agent_parent_agent_id"] = input("agent_id");
        $lists = $agent->where($where)->paginate(15);
        if ($lists) {
            foreach ($lists as $listsOne) {
                $listsOne->agent_son_count= $agent->where(["agent_parent_agent_id"=>$listsOne->agent_id])->count();
            }
        }

        $this->assign('lists', $lists);
        return view();

    }


    public function add()
    {
        
        if (config("saleversion") <= 2) {
            $this->error("无代理商操作功能");
        }
        $agent = model("agent");
        $one   = [];
        if (input("agent_id")) {
            $one = $agent->find(input("agent_id"));
        }
        $this->assign("one", $one);
        return view();
        //继续做角色新增的动作11
    }

    public function delete()
    {
        

        $agent_id = input("agent_id/a");
        if ($agent_id) {

            if (model("shop")->where(["shop_agent_id" => ["in", $agent_id]])->count() > 0) {
                return ["code" => 0, "message" => "该代理拥有代理商户，不可删除", "wait" => 1];
            }

            $agent = model("agent");
            $agent->where(["agent_parent_agent_id"=>session("agent_id")])->where("agent_id", "in", $agent_id)->delete();
            return ["code" => 1, "message" => "删除成功", "wait" => -1, "url" => url('index')];
        } else {
            return ["code" => 0, "message" => "没有数据删除", "wait" => 1];
        }

    }

    //保存或新增
    public function save()
    {
        
        $agent = model("agent");

        //判断重复
        $wherehave["agent_username"] = input("agent_username");
        if (input("agent_id/d") > 0) {
            $wherehave["agent_id"] = ["neq", input("agent_id/d")];
        }
        if ($agent->where($wherehave)->find()) {
            return ["code" => 0, "message" => input("agent_username") . " 已经存在!", "url" => "#"];
        }

        $newData = [
            'agent_company_name'   => input("agent_company_name"),
            'agent_name'           => input("post.agent_name", ""),
            'agent_username'       => input("post.agent_username", ""),
            'agent_active'         => input("agent_active/d", 0),
            'agent_open_son_agent' => input("agent_open_son_agent/d", 0),
            'agent_mobile'         => input("agent_mobile"),
            'agent_parent_agent_id'=> session("agent_id"),
            'agent_proportion'     => input("agent_proportion"),
        ];

        if (input("agent_password", "", null)) {
            if (mb_strlen(input("agent_password", "", null)) < 6) {
                return ["code" => 0, "message" => "密码至少6位", "url" => "#"];
            }
            $newData['agent_password'] = model("user")->passwordSetMd5(input("agent_password", null));
        }

        if (input("agent_id/d") > 0) {

        } else {
            $newData["agent_addtime"] = input("agent_addtime", time());
        }

        $agent->save($newData, input("agent_id") ? ['agent_id' => input("agent_id"),'agent_parent_agent_id'=> session("agent_id")] : null);
        return ["code" => 1, "message" => "保存成功", "wait" => -1, "url" => url('index')];
    }

}
