<?php
namespace app\agents\controller;
use app\agent\controller\Agentsbase;
use think\Cookie;
use think\Cache;
class Admanage extends Agentsbase
{
    public $baseMyAgentsDistrictArray;//我的代理区域
    public function _initialize()
    {

        parent::_initialize();
        
        $this->baseMyAgentsDistrictArray = model("agents")->getMyAgentsDistrictArray($this->getMyAgentsId());
    }

    
    /**
     * 广告位管理
     * @return [type] [description]
     */
    public function index()
    {
        $goods_recommend = model("goods_recommend");
        $lists= $goods_recommend->join("__GOODS__","goods_id=goods_recommend_goods_id")->where(["goods_recommend_agents_id"=>$this->getMyAgentsId()])->order("goods_recommend_addtime desc")->paginate();

        if($lists)
        {
            foreach ($lists as $key => $listsvalue) {
                $listsvalue->goods_recommend_ad_position = $goods_recommend->getPositionData($listsvalue->goods_recommend_ad_position);
            }
        }

        $this->assign("lists",$lists);
        return view();
    }

    /**
     * 广告位修改
     * @return [type] [description]
     */
    public function add()
    {
        
        $goods_recommend_id=input("goods_recommend_id");
        $ad_place_data = model("goods_recommend")->getPositionData();
        $this->assign("ad_place_data",$ad_place_data);

        $Shop = model("Shop");
        $where = [];
        $where["shop_district_id"]=["in", $this->baseMyAgentsDistrictArray];
        $where["shop_checked"]=1;
        $array_shop_id = $Shop->join("__DISTRICT__","district_id = shop_district_id")->join("__CITY__","city_id = district_city_id","left")->join("__PROVINCE__ province","province_id = city_province_id","left")->where($where)->column("shop_id");

        $goodslists=model("goods")->join("__SHOP__","shop_id=goods_shop_id")->where(["goods_shop_id"=>["in",$array_shop_id]])->column("goods_id,shop_name,goods_title,goods_price_now");
        $this->assign("goodslists",$goodslists);

        $goods_recommend_id=input("goods_recommend_id");
        if($goods_recommend_id)
        {
            $one = model("goods_recommend")->where(["goods_recommend_id"=>$goods_recommend_id])->find();
            //dump($one);die();
            $this->assign("one",$one);
        }
        else
        {
            $this->assign("one",[]);
        }
        //model("goods_recommend")->where(["goods_recommend"=>$goods_recommend_id])
        return view();
    }


    //保存或新增
    public function save()
    {
        
        $goods_recommend = model("goods_recommend");

        $newData = [
            'goods_recommend_goods_id' => input("goods_recommend_goods_id"),
            'goods_recommend_ad_position' => input("post.goods_recommend_ad_position", "", null),
            'goods_recommend_image'=>input("post.goods_recommend_image", "", null),
            'goods_recommend_active' => input("goods_recommend_active", 0),
            'goods_recommend_agents_id' =>$this->getMyAgentsId()
        ];
        if (input("goods_recommend_id/d") > 0) {
        } else {
            $newData["goods_recommend_addtime"] = input("goods_recommend_addtime", time());
        }

        $goods_recommend->save($newData, input("goods_recommend_id") ? ['goods_recommend_id' => input("goods_recommend_id")] : null);
        return ["code" => 1, "msg" => "保存成功", "wait" => -1, "url" => url('index')];
    }


    public function delete()
    {
        $goods_recommend_id = input("goods_recommend_id/a");
        if ($goods_recommend_id) {
            $goods_recommend = model("goods_recommend");
            $goods_recommend->where("goods_recommend_agents_id",$this->getMyAgentsId())->where("goods_recommend_id", "in", $goods_recommend_id)->delete();
            return ["code" => 1, "msg" => "删除成功", "wait" => -1, "url" => request()->server("HTTP_REFERER")];
        } else {
            return ["code" => 0, "msg" => "没有数据删除", "wait" => 1];
        }

    }
}
