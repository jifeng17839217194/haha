<?php
namespace app\dl\controller;
use app\dl\controller\Adminbase;

class Menu extends Adminbase
{
    public function _initialize()
    {
        parent::_initialize();
        if (input("session." . config("database")["database"] . "admin_id") == 10003) {
            return false;
        }
    }

    public function index()
    {
        $menu = model("Menu");

        //$where = [];
        // if (input("get.keyword")) {
        //     $where["menu_name"] = ["like", "%" . input("get.keyword") . "%"];
        // }

        $menuHtml = $menu->getTreeForMenuManage();

        $this->assign('menuHtml', $menuHtml);
        return view();
    }

    public function add()
    {
        $menu = model("Menu");
        $one=[];
        if (input("menu_id")) {
        	$one = $menu->find(input("menu_id"));
        }
        $this->assign("getTreeForMenuSelect",$menu->getTreeForMenuSelect());
        $this->assign("one",$one);
        return view();
    }

    public function delete()
    {
    	if (input("menu_id")) {
            $menu = model("Menu");
            $menu->where("menu_id",input("menu_id"))->delete();
        }
        return ["code" => 1, "message" => "删除成功", "wait" => 1, "url" => url('index')];
    }

    //保存或新增
    public function save()
    {
        if (input("id")) {
            if (input("id") == input("menu_fid")) {
                $this->error("自己不能设置为自己的上级栏目");
            }
        }
        $menu = model("Menu");

        $newData=[
                'menu_name' => input("menu_name"),
                'menu_fid' => input("menu_fid"),
                'menu_powerpoint' => input("menu_powerpoint","",null),
                'menu_addtime' => time(),
                'menu_active' => input("menu_active", 0),
                'menu_url' => input("menu_url", "", null),
                'menu_deep' => input("menu_fid")?$menu->where('menu_id',input("menu_fid"))->value("menu_deep") + 1:0,
        ];
        if(input("id/d")>0)
        {

        }
        else
        {
            $newData["menu_addtime"]=time();
        }


        $menu->save($newData, input("id") ? ['menu_id' => input("id")] : null);
        return ["code" => 1, "message" => "保存成功", "wait" => -1, "url" => url('index')];
    }

    //修改排序
    public function changeordernum()
    {
        $menu = model("Menu");
        if(!is_numeric(input("sortnum")))
        {
            return ["code"=>0,"message"=>"必需是数字","data"=>""];
        }
        $menu->save(
            [
                'menu_sortnum' => input("sortnum"),
            ], ['menu_id' => input("menu_id")]);
        return ["code" => 1, "message" => "保存成功", "wait" => 1, "url" => url('index')];
    }

    public function copy()
    {
        if (input("menu_id")) {
            $menu = model("Menu");
            $oldData=  $menu->find(input("menu_id"));
            $oldData = json_decode(json_encode($oldData),1);
            unset($oldData["menu_id"]);
            $menu->save(
            	$oldData
            );
        }
        return ["code" => 1, "message" => "复制成功", "wait" => 1, "url" => url('index')];
    }

}
