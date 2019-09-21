<?php
namespace app\agent\controller;

use app\agent\controller\Agentbase;

//2017-1-22，
class Shop extends Agentbase
{
    public function _initialize()
    {
        parent::_initialize();
    }

    public function index()
    {

        $Shop    = model("Shop");
        $where   = [];
        $keyword = input("get.keyword", "");

        $where["shop_agent_id"] = $this->getMyagentId();
        if ($keyword) {
            $where["shop_name|shop_master_mobile"] = ["like", "%" . $keyword . "%"];
        }
        $lists = $Shop->where($where)->order("shop_id", "desc")->paginate(15);

        //$provinceLists = model("Region")->where(["region_parent_id"=>1])->select();
        //$this->assign("provinceLists",$provinceLists);
        
        session("historylistpage",request()->url());
        $this->assign('lists', $lists);
        return view();
    }

    //
    private function idGetVal($idArray, $ObjArray)
    {
        $rs = [];
        foreach ($idArray as $idArraykey) {
            if (isset($ObjArray[$idArraykey])) {
                $rs[] = $ObjArray[$idArraykey];
            }
        }
        return $rs;
    }

    public function add($shop_id = 0)
    {

        $shop = model("shop");
        $this->assign("userList", model("User")->field("user_id,user_mobile")->select());

        //商户类型
        //$ShopType = model("shopType");
        //$typeLists=$ShopType->select();
        //$this->assign("typeLists",$typeLists);
        
        $one = $shop->join("__SHOP_ATTR__", "shop_id=shop_attr_shop_id", "left")->where(["shop_id" => $shop_id])->find();
        $this->assign("one", $one);
        return view();
        //继续做角色新增的动作11
    }

    /**
     * 清除支付宝的授权信息
     * @param  integer $shop_id [description]
     * @return [type]           [description]
     */
    public function clearalipayauthtoken($shop_id = 0)
    {

        $shop = model("shop");
        $shop->save(["shop_alipay_app_auth_token" => "", "shop_alipay_auth_app_id" => ""], ["shop_agent_id" => $this->getMyagentId(), "shop_id" => input("shop_id/d", 0)]);
        return ["code" => 1, "message" => "", "data" => ""];
    }


    /**
     * 清除支付宝停车场的授权信息
     * @param  integer $shop_id [description]
     * @return [type]           [description]
     */
    public function clearalipayauthtokenautopay($shop_id = 0)
    {

        $shop = model("shop");
        $shop->save(["shop_alipay_app_auth_token_auto_pay" => "", "shop_alipay_auth_app_id_auto_pay" => ""], ["shop_agent_id" => $this->getMyagentId(), "shop_id" => input("shop_id/d", 0)]);
        return ["code" => 1, "message" => "", "data" => ""];
    }
    public function delete()
    {

        $shop_id = input("shop_id/a");
        if ($shop_id) {

            if (model("store")->where(["store_shop_id" => ["in", $shop_id]])->count() > 0) {
                return ["code" => 0, "message" => "该店铺拥有下属商铺，不可删除", "wait" => 1];
            }

            $Shop = model("Shop");
            $Shop->where(["shop_agent_id" => $this->getMyagentId()])->where("shop_id", "in", $shop_id)->delete();
            return ["code" => 1, "message" => "删除成功", "wait" => -1, "url" => url('index')];
        } else {
            return ["code" => 0, "message" => "没有数据删除", "wait" => 1];
        }
    }

    //保存或新增
    public function save()
    {

        $Shop = model("shop");

        $shop_name = input("shop_name", "", "trim");
        if (!$shop_name) {
            return ["code" => 0, "message" => "签约商户名称不可为空", "data" => ""];
        }

        //判断重复
        $wherehave["shop_name"] = input("shop_name");

        if (input("shop_id/d") > 0) {
            $wherehave["shop_id"] = ["neq", input("shop_id/d")];
        }
        $one = $Shop->where($wherehave)->find();

        if ($one) {

            return ["code" => 0, "message" => input("shop_name") . " 已经存在!", "url" => "#"];
        }

        if (input("shop_id/d") > 0) {
            $shopOne                                  = $Shop->where("shop_id", input("shop_id/d"))->find();
            //$newData["shop_head_picture"]             = "";
            $newData["shop_name"]                     = $shop_name;
            //$newData["shop_business_license_picture"] = "";
            $newData["shop_business_license"]         = input("shop_business_license");
            $newData["shop_master_name"]              = input("shop_master_name");
            //$newData["shop_master_sfz"]               = input("shop_master_sfz");
            $newData["shop_master_mobile"]    = input("shop_master_mobile");
            $newData["shop_address"]          = input("shop_address");
            $newData["shop_tel"]              = input("shop_tel");
            $newData["shop_content"]          = input("post.shop_content", "", null);
            $newData["shop_wxpay_sub_mch_id"] = input("shop_wxpay_sub_mch_id", "");
            $newData["shop_wxpay_sub_appid"]  = input("shop_wxpay_sub_appid", "");
            $newData["shop_alipay_account"]   = input("shop_alipay_account", "");

            $newData["shop_active"] = input("shop_active", 1);
            //$newData["shop_checked_message"] = "审核通过(平台添加的代理商直接通过)";
            
            $Shop->save($newData, ["shop_id" => $shopOne->shop_id]);

            //完善店铺附属资料
            $shop_attr                                = db("shop_attr");
            $shop_attr_data=[];
            $shop_attr_data["shop_attr_shop_id"]      = $shopOne->shop_id;
            $shop_attr_data["shop_attr_alipay_rates"] = input("shop_attr_alipay_rates", "");
            $shop_attr_data["shop_attr_wxpay_rates"]  = input("shop_attr_wxpay_rates", "");


            if ($shop_attr->where(["shop_attr_shop_id" => $shopOne->shop_id])->find()) {
                $shop_attr->where(["shop_attr_shop_id" => $shopOne->shop_id])->update($shop_attr_data);
            } else {
                $shop_attr->insert($shop_attr_data);
            }

        } else {
            $shopOne=$Shop->baseadd($shop_name,$this->getMyagentId());
            return ["code" => 1, "message" => "保存成功", "wait" => -1, "url" => url('shop/add?shop_id=' . $shopOne->shop_id)];
        }

        return ["code" => 1, "message" => "保存成功", "wait" => -1, "url" => url('index')];
    }

}
