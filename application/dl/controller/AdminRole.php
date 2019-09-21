<?php
namespace app\dl\controller;
use app\dl\controller\Adminbase;
class AdminRole extends Adminbase
{
	public function _initialize()
    {
        parent::_initialize();
    }

    public function index()
    {
        Adminbase::checkActionAuth();
    	$AdminRole = model("AdminRole");
    	$lists = $AdminRole->order("admin_role_id","desc")->select();
    	$this->assign('lists',$lists);
        return view();
    }

    public function add()
    {
        Adminbase::checkActionAuth("admin_role/index","add");
        $AdminRole = model("AdminRole");
        $one=[];
        if (input("admin_role_id")) {
            $one = $AdminRole->find(input("admin_role_id"));
        }
        $menu = model("Menu");
        $this->assign("getTreeForRoleSelect",$menu->getTreeForRoleSelect());
        $this->assign("one",$one);
        return view();
        //继续做角色新增的动作11
    }


    

    public function delete()
    {
        Adminbase::checkActionAuth("admin_role/index","delete");
        if (input("admin_role_id")) {
            $AdminRole = model("AdminRole");
            $AdminRole->where("admin_role_id",input("admin_role_id"))->delete();
        }
        return ["code" => 1, "message" => "删除成功", "wait" => 1, "url" => url('index')];
    }

    //保存或新增
    public function save()
    {
        Adminbase::checkActionAuth("admin_role/index","add");
        $AdminRole = model("AdminRole");

        //收集admin_role_powerlist值
        $admin_role_powerlist=[];
        foreach (input("post.") as $postname=>$postvalue) {
            if(strpos($postname,"point_")!==false)
            {
                $admin_role_powerlist[]=$postname;
            }
        }

         //判断重复
        $wherehave["admin_role_name"]=input("admin_role_name");
        if(input("id/d")>0)
        {
            $wherehave["admin_role_id"]=["neq",input("id/d")];
        }
        if($AdminRole->where($wherehave)->find())
        {
            return ["code" => 0, "message" => input("admin_role_name")." 已经存在!","url"=>"#"];
        }
        
        //__收集admin_role_powerlist值

        $AdminRole->save(
            [
                'admin_role_name' => input("admin_role_name"),
                'admin_role_powerlist' => json_encode($admin_role_powerlist),
                'admin_role_addtime' => time(),
                'admin_role_active' => input("admin_role_active", 0),
            ], input("id") ? ['admin_role_id' => input("id")] : null);
        return ["code" => 1, "message" => "保存成功", "wait" => -1, "url" => url('index')];
    }


}
