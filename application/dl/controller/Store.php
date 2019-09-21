<?php
namespace app\dl\controller;

use app\dl\controller\Adminbase;

class Store extends Adminbase
{
    public function _initialize()
    {
        parent::_initialize();
    }
    public function index()
    {
        Adminbase::checkActionAuth("shop/index", "view");
        $store   = model("store");
        $where   = ["store_shop_id" => input("store_shop_id", 0)];
        $keyword = input("get.keyword", "");
        if ($keyword) {
            $where["store_name"] = ["like", "%" . $keyword . "%"];
        }
        $lists = $store->join("__PARKING__", "parking_store_id=store_id", "left")->where($where)->paginate(15);
        if ($lists) {
            /*$cityData=model("City")->column("city_id,city_name");
        foreach ($lists as $listsOne) {
        $listsOne->cityname= implode(",",self::idGetVal($listsOne->agents_city_id,$cityData));
        }*/
        }
        $this->assign('lists', $lists);
        return view();
    }
    public function add()
    {
        Adminbase::checkActionAuth("shop/index", "add");
        $store = model("store");
        $one   = [];
        if (input("store_id")) {
            $one = $store->join("__PARKING__", "parking_store_id=store_id", "left")->find(input("store_id"));
        }
        $this->assign("one", $one);
        return view();
        //继续做角色新增的动作11
    }
    /*
    * 新增停车场
    * By
    * create:2018-8-15
    */
    public function car_park_add()
    {
         Adminbase::checkActionAuth("shop/index", "add");
        $store = model("store");
        $one   = [];
        if (input("store_id")) {
            $one = $store->join("__PARKING__", "parking_store_id=store_id", "left")->find(input("store_id"));
        }
        $this->assign("one", $one);
        return view();
    }
    public function delete()
    {
        Adminbase::checkActionAuth("shop/index", "delete");
        $store_id = input("store_id");
        if ($store_id) {
            $store = model("store");
            if (db("user")->where(["user_store_id" => $store_id])->count() > 0) {
                return ["code" => 0, "message" => "该店铺有下属员工，不可删除", "wait" => 1];
            }
            $store->where(["store_id" => $store_id])->delete(); //不支持批量删除，因为要查询下面有没有用户
            return ["code" => 1, "message" => "删除成功", "wait" => -1, "url" => url('index?store_shop_id=' . input("store_shop_id/d") . '&target=self')];
        } else {
            return ["code" => 0, "message" => "没有数据删除", "wait" => 1];
        }
    }
    //保存或新增
    public function save()
    {
        Adminbase::checkActionAuth("shop/index", "add");
        $store = model("store");
        //判断重复
        $wherehave["store_name"] = input("store_name");
        if (input("store_id/d") > 0) {
            $wherehave["store_id"] = ["neq", input("store_id/d")];
        }
        if ($store->where($wherehave)->find()) {
            return ["code" => 0, "message" => input("store_name") . " 已经存在!", "url" => "#"];
        }
        $store_open_reward           = input("store_open_reward/d", 0);
        $store_open_funds_authorized = input("store_open_funds_authorized/d", 0);
        if (config('saleversion') == 1) //基础版本，不开启打赏功能
        {
            $store_open_reward = 0;
        }
        if (config('saleversion') <= 2) {
            $store_open_funds_authorized = 0;
        } 
        $newData = [
            'store_name'                       => input("store_name"),
            'store_shop_id'                    => input("store_shop_id/d"),
            'store_address'                    => input("store_address", ""),
            'store_open_reward'                => $store_open_reward,
            'store_open_funds_authorized'      => $store_open_funds_authorized,
            'store_mobile'                     => input("store_mobile", ""),
            "store_pay_after_ad"               => input("store_pay_after_ad", "", null),
            "store_pay_after_ad_active"        => input("store_pay_after_ad_active", 0),
            "store_parking_compatibility_mode" => input("store_parking_compatibility_mode", 0),          
        ];
        if (input("store_id/d") > 0) {
            $newData["store_updatetime"] = time();
        } else {
            $newData["store_addtime"] = input("store_addtime", time());
        }
        $store->save($newData, input("store_id") ? ['store_id' => input("store_id")] : null);

        return ["code" => 1, "message" => "保存成功", "wait" => -1, "url" => url('index?store_shop_id=' . input("store_shop_id/d") . '&target=self')];
    }
    public function car_park_save()
    {
        Adminbase::checkActionAuth("shop/index", "add");
        $store = model("store");
        //判断重复
        $wherehave["store_name"] = input("store_name");
        if (input("store_id/d") > 0) {
            $wherehave["store_id"] = ["neq", input("store_id/d")];
        }
        if ($store->where($wherehave)->find()) {
            return ["code" => 0, "message" => input("store_name") . " 已经存在!", "url" => "#"];
        }       
        /*bof */
         if(input('store_is_park',0)){
            if(!input('store_parking_lng',0)){
                return ["code" => 0, "message" => "停车场必需填写经度", "data" => ""];
            }
            if(!input('store_parking_lat',0)){
                return ["code" => 0, "message" => "停车场必需填写纬度", "data" => ""];
            }
        }
        /*eof */
        $newData = [
            'store_name'                       => input("store_name"),
            'store_shop_id'                    => input("store_shop_id/d"),
            'store_address'                    => input("store_address", ""),
            'store_mobile'                     => input("store_mobile", ""),
            'store_parking_poiid'              => input("store_parking_poiid", ""),
            "store_parking_compatibility_mode" => input("store_parking_compatibility_mode", 0),
            "store_is_park"        => input("store_is_park", 0),
            'store_parking_lng'=>input('store_parking_lng',0),
            'store_parking_lat'=>input('store_parking_lat',0),
            'store_temporary_parking_fee'=>input('store_temporary_parking_fee',0),
            'store_monthly_fee'=>input('store_monthly_fee',0),
        ];
        if (input("store_id/d") > 0) {
            $newData["store_updatetime"] = time();
        } else {
            $newData["store_addtime"] = input("store_addtime", time());
        }
        $store->save($newData, input("store_id") ? ['store_id' => input("store_id")] : null);
        $store_id = input("store_id") ?: $store->store_id;

        $parking_uuid = input("parking_uuid", "");

        //绑定停车场的消息
        $parking = model("parking");
        if ($parking_uuid) {

            if (!input("store_mobile", "")) {
                return ["code" => 0, "message" => "停车必需填写电话", "data" => ""];
            }
              

            $parking_uuid = $parking_uuid ?: input("store_parking_poiid", ""); //默认可使用地图的数据(兼容模式下会执行)

            //先判断parking_uuid有没有被占用
            $parkingOne             = $parking->where(["parking_uuid" => $parking_uuid])->find();

            $this_parking_id        = "";
            $parking_ali_parking_id = $parkingOne["parking_ali_parking_id"];
            if (!$parkingOne) {
                //没有就生成一条空停车场记录
                $parking_ali_parking_id = "";
                $this_parking_id        = db("parking")->insertGetId(["parking_uuid" => $parking_uuid, "parking_name" => "自动创建"]);
            } else {
                $this_parking_id = $parkingOne->parking_id;
            }
            //再判断store_id 有没有绑定过记录
            if (!db("parking")->where(["parking_store_id" => $store_id])->find()) {
                db("parking")->where(["parking_id" => $this_parking_id])->update(["parking_store_id" => $store_id, "parking_addtime" => time()]);
            }

            //通道与账号的绑定
            if (input("store_parking_compatibility_mode", 0) == 0) {
                $cbuRs=model("ParkingChannel")->channelBindUser($this_parking_id); //这是实时与易泊服务器通信的（并没有直接与硬件通信）
                if($cbuRs["code"]==0)
                {
                    return $cbuRs;
                }
            }
            //__通道与账号的绑定

            if (input("store_parking_poiid", "")) {
             
                //到阿里那里set一下(停车场必需要初始化)
                
                $shopOne                              = db("shop")->where(["shop_id" => input("store_shop_id/d")])->find();
                //return ["code"=>0,"message"=>$shopOne["shop_alipay_account"],"data"=>""];
                $paramArray                           = [];
                $paramArray["merchant_name"]          = input("store_name")."";
                $paramArray["merchant_service_phone"] = input("store_mobile", "");
                $paramArray["account_no"]             = $shopOne["shop_alipay_account"];//签约支付宝账号(ISV，还是物业？)
                $paramArray["app_auth_token"]         = $shopOne["shop_alipay_app_auth_token_auto_pay"];
                $paramArray["interface_url"]          = urlencode(str_replace("http:", "https:", request()->domain()) . url("h5/carpark/parkingfee"));
                $setRs                                = model("parking")->push2aliyun("parking.config.set", $paramArray);
                if (!$setRs["code"]) {
                    $setRs["message"]="【支付宝停车场初始化】：".$setRs["message"];
                    return $setRs;
                }
                //__到阿里那里set一下


                //停车场注册下
                $paramArray                    = [];
                $paramArray["out_parking_id"]  = $store_id;
                $paramArray["parking_address"] = input("store_address", "");
                $paramArray["parking_poiid"]   = input("store_parking_poiid", "");
                $paramArray["parking_mobile"]  = input("store_mobile", "");
                $paramArray["parking_name"]    = input("store_name", "")."(停车场名称)";
                $shopOne                       = db("shop")->where(["shop_id" => input("store_shop_id/d")])->find();
                $paramArray["agent_id"]        = $shopOne["shop_alipay_seller_id"];
                $paramArray["app_auth_token"]  = $shopOne["shop_alipay_app_auth_token_auto_pay"];
                $paramArray["mchnt_id"] = $shopOne["shop_alipay_seller_id"];

                if (!$parking_ali_parking_id) {
                    $setRs = model("parking")->push2aliyun("parking.parkinglotinfo.create", $paramArray);
                    if (!$setRs["code"]) {
                        $setRs["message"]="【支付宝创建停车场信息】：".$setRs["message"];
                        return $setRs;
                    } else {
                        $ali_parking_id = $setRs["data"]["parking_id"]; //支付宝返回停车场id。成功不为空，失败返回空
                        db("parking")->where(["parking_store_id" => $store_id])->update(["parking_ali_parking_id" => $ali_parking_id]);
                    }
                } else //更新
                {
                    unset($paramArray["out_parking_id"]);
                    $paramArray["parking_id"] = $parking_ali_parking_id;
                    $setRs                    = model("parking")->push2aliyun("parking.parkinglotinfo.update", $paramArray);
                    if (!$setRs["code"]) {
                        $setRs["message"]="支付宝更新停车场信息：".$setRs["message"]."<br />(支付宝：1分钟内做了2+次保存，请忽略此消息)";
                        $setRs["code"]=1;//通常是“停车场重复修改”的提示，不影响什么，直接跳过就可以了
                        $setRs["url"]=url('index?store_shop_id=' . input("store_shop_id/d") . '&target=self');
                        return $setRs;
                    }
                }
            }

            //__停车场注册下
            //还有更新的接口
        } else {
            //不给清除绑定
            //$parking->isUpdate(true)->save(["parking_store_id" => 0], ["parking_uuid" => $parking_uuid]); //清除绑定
        }
        return ["code" => 1, "message" => "保存成功", "wait" => -1, "url" => url('index?store_shop_id=' . input("store_shop_id/d") . '&target=self')];
    }
}
