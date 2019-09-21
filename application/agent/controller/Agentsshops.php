<?php

namespace app\agents\controller;
use app\common\controller\Agentsbase;

class Agentsshops extends Agentsbase
{
    public $baseMyAgentsDistrictArray;//我的代理区域
    public function _initialize()
    {

        parent::_initialize();
        
        $this->baseMyAgentsDistrictArray = model("agents")->getMyAgentsDistrictArray($this->getMyAgentsId());
    }

    public function index()
    {
        
        $Shop = model("Shop");
        $where = [];
        $where["shop_district_id"]=["in", $this->baseMyAgentsDistrictArray];

        $keyword = input("get.keyword", "");
        if ($keyword) {
            $where["shop_name"] = ["like", "%" . $keyword . "%"];
        }


        $district_id = input("get.district_id", "");
        $where2=[];
        if ($district_id) {
            $where2["shop_district_id "] = $district_id;
        }



        $checked = input("checked", "");
        if ($checked!="") {
            $where["shop_checked"] = $checked;
        }
        
        $lists = $Shop->join("__DISTRICT__","district_id = shop_district_id")->join("__CITY__","city_id = district_city_id","left")->join("__PROVINCE__ province","province_id = city_province_id","left")->where($where)->where($where2)->order("shop_id","desc")->paginate(15);


        $this->assign("districtLists", model("District")->join("__CITY__","city_id = district_city_id","left")->join("__PROVINCE__ province","province_id = city_province_id","left")->field("district_id,district_name,province_name,city_name")->where("district_id","in",$this->baseMyAgentsDistrictArray)->order("district_name asc")->select());

        if($lists)
        {
            foreach ($lists as $listsOne) {
                $listsOne->nicechecked= $Shop->getNiceChecked($listsOne->shop_checked);
            }
        }

        $this->assign('lists', $lists);
        return view();
    }


    public function goodslist()
    {
        $shop_id=input("shop_id");
        $lists=model("goods")->where(["goods_shop_id"=>$shop_id])->paginate();
        $this->assign("lists",$lists);
        $ad_place_data = model("goods_recommend")->getPositionData();
        //dump($ad_place_data);die();
        $this->assign("ad_place_data",$ad_place_data);
        return view();
    }

    public function goodsset2ad()
    {
        $goods_id = input("goods_id");
        $one=model("goods")->where(["goods_id"=>$goods_id])->join("__GOODS_RECOMMEND__","goods_recommend_goods_id=goods_id","left")->select();
        $this->assign("one",$one);
        return view();
    }

    
    public function setgoodshop()
    {
        $shop_id = input("shop_id");
        model("shop")->save(["shop_set_top_good_shop"=>time()],["shop_id"=>$shop_id]);
        return ["code"=>1,"message"=>"","data"=>"","url"=>request()->server("HTTP_REFERER")];
    }

    public function add()
    {
        $Shop = model("Shop");
        $one = [];
        if (input("shop_id")) {

            $where = [];
            $where["shop_district_id"]=["in", $this->baseMyAgentsDistrictArray];
            $where["shop_id"]=input("shop_id");
            $one = $Shop->where($where)->join("__DISTRICT__","district_id = shop_district_id")->join("__CITY__","city_id = district_city_id","left")->join("__PROVINCE__ province","province_id = city_province_id","left")->find();
            $one->shop_class_nicename = $Shop->getNiceShopClass($one->shop_class);
        }
        $this->assign("one", $one);
        return view();
    }
/*
    public function delete()
    {
        Agentsbase::checkActionAuth("news/index", "delete");
        $shop_id = input("shop_id/a");
        if ($shop_id) {
            $Shop = model("Shop");
            $Shop->where("shop_id", "in", $shop_id)->delete();
            return ["code" => 1, "msg" => "删除成功", "wait" => -1, "url" => url('index')];
        } else {
            return ["code" => 0, "msg" => "没有数据删除", "wait" => 1];
        }

    }*/

    //修改排序
    /*
    public function sortnum()
    {
        $Shop = model("Shop");
        if (!is_numeric(input("promptvalue"))) {
            $this->error("必需是数字");
        }
        $Shop->save(
            ['shop_sortnum' => input("promptvalue"),
            ], ['shop_id' => input("shop_id")]);
        return ["code" => 1, "msg" => "保存成功", "wait" => -1, "url" => request()->server("HTTP_REFERER")];
    }
    */

    //保存或新增
    public function save()
    {
        
        $Shop = model("Shop");

        /*
        //判断重复
        $wherehave["shop_name"] = input("shop_name");
        if (input("shop_id/d") > 0) {
            $wherehave["shop_id"] = ["neq", input("shop_id/d")];
        }
        if ($Shop->where($wherehave)->find()) {
            return ["code" => 0, "msg" => input("shop_name") . " 已经存在!", "url" => "#"];
        }
        */
        $newData = [
            'shop_checked_message' => input("shop_checked")!=1?input("shop_checked_message"):"审核通过",
            'shop_checked' => input("shop_checked")
        ];

        $Shop->save($newData,['shop_id' => input("shop_id")]);
        return ["code" => 1, "msg" => "保存成功", "wait" => -1, "url" => url('index')];
    }

}
