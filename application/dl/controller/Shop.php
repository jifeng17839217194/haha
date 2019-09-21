<?php
namespace app\dl\controller;

use app\dl\controller\Adminbase;

//2017-1-22，
class Shop extends Adminbase
{
    public function _initialize()
    {
        parent::_initialize();
    }

    public function index()
    {
        Adminbase::checkActionAuth();
        $Shop    = model("Shop");
        $where   = [];
        $keyword = input("get.keyword", "");
        if ($keyword) {
            $where["shop_name|shop_master_mobile|shop_master_name|shop_master_mobile"] = ["like", "%" . $keyword . "%"];
        }

        $agent_id = input("agent_id/d");
        if ($agent_id) {
            $where["shop_agent_id"] = $agent_id;
        }

        $lists = $Shop->join("__AGENT__", "shop_agent_id = agent_id", "left")->where($where)->order("shop_id", "desc")->paginate(15);

        $agentlist = model("agent")->where(["agent_parent_agent_id" => 0])->select();
        $this->assign("agentlist", $agentlist);

        $this->assign('lists', $lists);
        session("historylistpage", request()->url());

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
        Adminbase::checkActionAuth("shop/index", "add");
        $shop = model("shop");
        $this->assign("userList", model("User")->field("user_id,user_mobile")->select());

        //商户类型
        //$ShopType = model("shopType");
        //$typeLists=$ShopType->select();
        //$this->assign("typeLists",$typeLists);
        $one = $shop->join("__SHOP_ATTR__", "shop_id=shop_attr_shop_id", "left")->join("__SHOP_DATA__", "shop_data_shop_id=shop_id", "left")->where(["shop_id" => $shop_id])->find();

        if ($one) {
            if (is_string($one->shop_data_master_id_images)) {
                if ($one->shop_data_master_id_images == '[""]') {
                    $one->shop_data_master_id_images = [];
                } else { $one->shop_data_master_id_images = json_decode($one->shop_data_master_id_images, 1);}
            }
            if (is_string($one->shop_data_store_images)) {
                if ($one->shop_data_store_images == '[""]') {
                    $one->shop_data_store_images = [];
                } else { $one->shop_data_store_images = json_decode($one->shop_data_store_images, 1);}
            }
            if (is_string($one->shop_data_other_images)) {
                if ($one->shop_data_other_images == '[""]') {
                    $one->shop_data_other_images = [];
                } else { $one->shop_data_other_images = json_decode($one->shop_data_other_images, 1);}
            }
        }

        $this->assign("one", $one);

        $agentlist = model("agent")->where(["agent_parent_agent_id" => 0])->select();
        $this->assign("agentlist", $agentlist);

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
        Adminbase::checkActionAuth("shop/index", "add");
        $shop = model("shop");
        $shop->save(["shop_alipay_app_auth_token" => "", "shop_alipay_auth_app_id" => ""], ["shop_id" => input("shop_id/d", 0)]);
        return ["code" => 1, "message" => "", "data" => ""];
    }

    /**
     * 清除支付宝停车场的授权信息
     * @param  integer $shop_id [description]
     * @return [type]           [description]
     */
    public function clearalipayauthtokenautopay($shop_id = 0)
    {
        Adminbase::checkActionAuth("shop/index", "add");
        $shop = model("shop");
        $shop->save(["shop_alipay_app_auth_token_auto_pay" => "", "shop_alipay_auth_app_id_auto_pay" => ""], ["shop_id" => input("shop_id/d", 0)]);
        return ["code" => 1, "message" => "", "data" => ""];
    }

    public function delete()
    {
        Adminbase::checkActionAuth("Shop/index", "delete");
        $shop_id = input("shop_id/a");
        if ($shop_id) {

            if (model("store")->where(["store_shop_id" => ["in", $shop_id]])->count() > 0) {
                return ["code" => 0, "message" => "该店铺拥有下属商铺，不可删除", "wait" => 1];
            }

            $Shop = model("Shop");
            $Shop->where("shop_id", "in", $shop_id)->delete();
            return ["code" => 1, "message" => "删除成功", "wait" => -1, "url" => url('index')];
        } else {
            return ["code" => 0, "message" => "没有数据删除", "wait" => 1];
        }
    }

    //保存或新增
    public function save()
    {
        Adminbase::checkActionAuth("shop/index", "add");
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
            $shopOne = $Shop->where("shop_id", input("shop_id/d"))->find();
            //$newData["shop_head_picture"]             = "";
            $newData["shop_name"] = $shop_name;
            //$newData["shop_business_license_picture"] = "";
            $newData["shop_business_license"] = input("shop_business_license");
            $newData["shop_master_name"]      = input("shop_master_name");
            $newData["shop_agent_id"]         = input("shop_agent_id");
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

            //完善店铺资料表
            $shop_data                             = model("shop_data");
            $shopDataData["shop_data_status"]      = input("shop_data_status", 0);
            $shopDataData["shop_data_status_info"] = input("shop_data_status_info");
            if (!($storeDataOne = $shop_data->where(["shop_data_shop_id" => $shopOne->shop_id])->find())) {
                $shop_data->isupdate(false)->save($shopDataData); //__历史数据完善
            } else {
                $shop_data->isupdate(true)->save($shopDataData, ["shop_data_shop_id" => $shopOne->shop_id]);
            }

            //完善店铺附属资料
            $shop_attr                                = db("shop_attr");
            $shop_attr_data["shop_attr_shop_id"]      = $shopOne->shop_id;
            $shop_attr_data["shop_attr_alipay_rates"] = input("shop_attr_alipay_rates", "");
            $shop_attr_data["shop_attr_wxpay_rates"]  = input("shop_attr_wxpay_rates", "");

            if ($shop_attr->where(["shop_attr_shop_id" => $shopOne->shop_id])->find()) {
                $shop_attr->where(["shop_attr_shop_id" => $shopOne->shop_id])->update($shop_attr_data);
            } else {
                $shop_attr->insert($shop_attr_data);
            }

        } else {
            $shopOne = $Shop->baseadd($shop_name);
            return ["code" => 1, "message" => "保存成功", "wait" => -1, "url" => url('shop/add?shop_id=' . $shopOne->shop_id)];
        }

        return ["code" => 1, "message" => "保存成功", "wait" => -1, "url" => "#"];
    }

}
