<?php
namespace app\agents\controller;
use app\common\controller\Agentsbase;

class Pinlun extends Agentsbase
{
    public $baseMyAgentsDistrictArray;//我的代理区域
    public function _initialize()
    {
        parent::_initialize();
        $this->baseMyAgentsDistrictArray = model("agents")->getMyAgentsDistrictArray($this->getMyAgentsId());
    }

    public function index()
    {
        
        
        $order_list = model("order_list");
        $where = [];
        $where["shop_district_id"]=["in", $this->baseMyAgentsDistrictArray];

        $keyword = input("get.keyword", "","trim");
        if ($keyword) {
            $where["order_list_title|order_pingjia_content|shop_name"] = ["like", "%" . $keyword . "%"];
        }
        
        $where["order_pingjia_lasttime"]=["gt",0];
        $lists = $order_list->join("__SHOP__","shop_id = order_shop_id","left")->join("__DISTRICT__","district_id = shop_district_id","left")->where($where)->order("order_list_id desc")->paginate(15);

       

        if($lists)
        {
            // $cityData=model("City")->column("city_id,city_name");
            // foreach ($lists as $listsOne) {
            //     $listsOne->cityname= implode(",",self::idGetVal($listsOne->agents_city_id,$cityData));
            // }
        }

        $this->assign('lists', $lists);
        return view();
    }


    public function close()
    {
        
        $order_list_id = input("order_list_id/a");
        if ($order_list_id) {
            $order_list = model("order_list");
            $order_list->save(["order_pingjia_active"=>-1],["order_list_id"=>["in", $order_list_id]]);
            return ["code" => 1, "msg" => "关闭成功", "wait" => -1, "url" => url('index')];
        } else {
            return ["code" => 0, "msg" => "没有数据关闭", "wait" => 1];
        }

    }
}
