<?php
/**
 * 代理商，手机版本，用于方便用户提交资料到服务商
 */
namespace app\agent\controller;

use app\agent\controller\Agentbase;
use think\captcha\Captcha;
use think\Image;

class Shopmobile extends Agentbase
{
    public function _initialize()
    {
        //parent::_initialize();
        $this->assign("sysconfig", model("Sysconfig")->getConfig());
    }
    /**
     * 检测是否登入了
     * @return [type] [description]
     */
    public function checkislogin()
    {
        if (!cookie('agent_id')) {
            $this->redirect(url("login"));
        }
    }
    public function index()
    {
        $this->checkislogin();
        return view();
    }

    public function login()
    {
        if (request()->isPost()) {
            $captcha_type       = input("captcha_type", "image"); //验证码类型
            $captcha_value      = input("captcha_value");
            $captcha_identifier = input("captcha_identifier", "");
            switch ($captcha_type) {
                case 'image':
                    if (!$captcha_value) {
                        return ["code" => 0, "message" => "验证码不可为空", "data" => []];
                    }
                    $captcha = new Captcha();
                    if (!$captcha->check($captcha_value, $captcha_identifier)) {
                        return ["code" => 0, "message" => "验证码错误", "data" => []];
                    }
                    break;
                default:
                    # code...
                    break;
            }
            //有效性检测
            $user_name     = input("user_name", "", "trim");
            $user_password = input("user_password", "", "trim");
            if (!($user_name) || (!$user_password)) {
                return ["code" => 0, "message" => "用户名或密码不可为空", "data" => []];
            }
            $agent = model("agent");
            $rs    = $agent->agentcheckAndLogin($user_name, $user_password);
            if ($rs["code"] == 1) {
                cookie('agent_id', $rs["data"]["agent_id"], 3600 * 24 * 7);
                cookie('agent_name', $rs["data"]["agent_name"], 3600 * 24 * 7);
                return ["code" => 1, "message" => "登入成功", "data" => ""];
            } else {
                return $rs;
            }
        }
        return view();
    }

    /**
     * 我添加的商户
     * @return [type] [description]
     */
    public function shoplist()
    {
        $this->checkislogin();
        $shop                   = model("shop");
        $where["shop_agent_id"] = cookie("agent_id");
        $shoplist               = $shop->join("__SHOP_DATA__", "shop_data_shop_id=shop_id", "left")->where($where)->order("shop_id desc")->select();
        if ($shoplist) {
            foreach ($shoplist as $shopOne) {
                $shopOne->status = $this->getshoponestatus($shopOne);
            }
        }
        $this->assign("shoplist", $shoplist);
        return view();
    }

    //获取商户的状态
    public function getshoponestatus($shopone)
    {
        if ($shopone->shop_data_status == "-1" && ($shopone->shop_alipay_seller_id == "" || $shopone->shop_wxpay_sub_mch_id == "")) {
            return ["shop_data_status" => -1, "shop_data_status_info" => $shopone->shop_data_status_info];
        }

        if ($shopone->shop_data_status == "1" && ($shopone->shop_alipay_seller_id == "" || $shopone->shop_wxpay_sub_mch_id == "")) {
            return ["shop_data_status" => 1, "shop_data_status_info" => "审核中"];
        }

        if ($shopone->shop_data_status == "0" && ($shopone->shop_alipay_seller_id == "" || $shopone->shop_wxpay_sub_mch_id == "")) {
            return ["shop_data_status" => 0, "shop_data_status_info" => "资料待完善"];
        }
        return ["shop_data_status" => "", "shop_data_status_info" => ""];//已经完成
    }

    
    public function qrcode()
    {
        $value        = input("value", "");
        $size         = input("size", 2);
        $downloadname = input("downloadname", false);
        return qrcode($value, $size, $downloadname);
    }

    public function addone()
    {
        $this->checkislogin();
        $shop    = model("shop");
        $shopOne = $shop->baseadd("<新的商户>", cookie("agent_id"));
        $this->redirect(url("shopone?shop_id_token=" . $shopOne->shop_id_token));
    }

    //只接受shop_id_token参数
    //依靠shop_id_token，判断权限
    public function shopone()
    {
        $shop_id_token          = input("shop_id_token"); //唯一编码，方便业务员让商户自己填写资料
        $shop                   = model("shop");
        $where["shop_id_token"] = $shop_id_token;
        $shopone                = $shop->join("__STORE__", "store_shop_id=shop_id", "left")->join("__SHOP_DATA__", "shop_data_shop_id=shop_id", "left")->where($where)->find();
        if ($shopone) {
            //dump(json_decode(json_encode($shopone)));
            if (is_string($shopone->shop_data_master_id_images)) {
                if ($shopone->shop_data_master_id_images == '[""]') {
                    $shopone->shop_data_master_id_images = [];
                } else { $shopone->shop_data_master_id_images = json_decode($shopone->shop_data_master_id_images, 1);}
            }
            if (is_string($shopone->shop_data_store_images)) {
                if ($shopone->shop_data_store_images == '[""]') {
                    $shopone->shop_data_store_images = [];
                } else { $shopone->shop_data_store_images = json_decode($shopone->shop_data_store_images, 1);}
            }
            if (is_string($shopone->shop_data_other_images)) {
                if ($shopone->shop_data_other_images == '[""]') {
                    $shopone->shop_data_other_images = [];
                } else { $shopone->shop_data_other_images = json_decode($shopone->shop_data_other_images, 1);}
            }
        } else {
            $this->error("不存在!");
        }

        $shopone->status = $this->getshoponestatus($shopone);

        $this->assign("shopone", $shopone);
        return view();
    }
    /**
     * 判断是否有权限修改门店信息
     * @return [type] [description]
     */
    public function isallowchangeshop($shop_id_token = "")
    {
        if ($shop_id_token == "") {
            return false;
        }
        $shopOne = model("shop")->where(["shop_id_token" => $shop_id_token])->find();
        if (!$shopOne) {
            return false;
        }
        if ($shopOne->shop_alipay_seller_id && $shopOne->shop_wxpay_sub_mch_id) //资料都完成了；
        {
            return false; //
        } else {
            return true;
        }
    }
    /**
     * 开放给商户自己上传数据，权限判断根据商户的状态的来设定
     * @return [type] [description]
     */
    public function uploadfile()
    {
        set_time_limit(0);
        $shop_id_token = input("shop_id_token");
        if ($this->isallowchangeshop($shop_id_token) == false) {
            return ["code" => 0, "message" => "当前已经不可编辑", "data" => ""];
        }
        $base64_image_content = $_POST['imgBase64'];
        //匹配出图片的格式
        if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $base64_image_content, $result)) {
            $type = $result[2];
            $path = ROOT_PATH . "public/uploads/shopdata/" . $shop_id_token . "/";
            if (!file_exists($path)) {
                //检查是否有该文件夹，如果没有就创建，并给予最高权限
                createDir($path);
            }
            $type     = str_replace("jpeg", "jpg", $type);
            $filepath = getRandChar(5) . ".{$type}";
            $new_file = $path . $filepath;
            if (file_put_contents($new_file, base64_decode(str_replace($result[1], '', $base64_image_content)))) {
                $savepath     = "/uploads/shopdata/" . $shop_id_token . "/" . $filepath;
                $realsavepath = ROOT_PATH . "public" . $savepath;
                $image        = Image::open($realsavepath);
                $width        = $image->width();
                $height       = $image->height();
                if ($width > 1500 || $height > 1500) {
                    $image->thumb(1500, 1500)->save($realsavepath); // 压缩图片
                }
                $image->thumb(500, 500)->save(str_replace("." . $type, "_thumb." . $type, $realsavepath)); //创建缩略图(手机端显示，省流量用)
                return ["code" => 1, "message" => "上传成功", "data" => ["thumb" => $savepath, "url" => str_replace("." . $type, "_thumb." . $type, $savepath)]];
            } else {
                return ["code" => 0, "message" => "上传失败", "data" => ""];
            }
        } else {
            return ["code" => 0, "message" => "只支持图片格式", "data" => ""];
        }
    }

    /**
     * 保存数据
     * shop_id_token 唯一的权限判断
     * @return [type] [description]
     */
    public function shopsave()
    {
        $shop_id_token = input("shop_id_token");
        if ($this->isallowchangeshop($shop_id_token) == false) {
            return ["code" => 0, "message" => "当前已经不可编辑", "data" => ""];
        }
        //初始化
        $store     = model("store");
        $shop      = model("shop");
        $shop_data = model("shop_data");
        //__初始化
        $shopOne = $shop->where(["shop_id_token" => $shop_id_token])->find();
        if(!$shopOne)
        {
            return ["code"=>0,"message"=>"商户不存在","data"=>""];
        }
        //post数据收集
        $storeData["store_name"]    = input("store_name");
        $storeData["store_address"] = input("store_address");
        $storeData["store_mobile"]  = input("store_mobile");
        $storeData["store_shop_id"] = $shopOne->shop_id;

        $shopData["shop_name"]                     = input("shop_name");
        $shopData["shop_tel"]                      = input("store_mobile");
        $shopData["shop_address"]                  = input("store_address");
        $shopData["shop_master_name"]              = input("shop_master_name");
        $shopData["shop_business_license"]         = input("shop_business_license");
        $shopData["shop_business_license_picture"] = input("shop_business_license_picture", "", null);
        $shopData["shop_alipay_account"]           = input("shop_alipay_account");

        $shopDataData["shop_data_master_id_images"] = input("shop_data_master_id_images/a", "", null);
        $shopDataData["shop_data_store_head_image"] = input("shop_data_store_head_image", "", null);
        $shopDataData["shop_data_store_images"]     = input("shop_data_store_images/a", "", null);
        $shopDataData["shop_data_bank_number"]      = input("shop_data_bank_number");
        $shopDataData["shop_data_bank_name"]        = input("shop_data_bank_name");
        $shopDataData["shop_data_other_images"]     = input("shop_data_other_images/a", "", null);
        $shopDataData["shop_data_other_info"]       = input("shop_data_other_info");
        $shopDataData["shop_data_shop_id"]          = $shopOne->shop_id;

        if (input("postshenghe", "") == 1) {
            $shopDataData["shop_data_status"] = 1;
            $shodDataData["shop_data_status_change_time"]=time();
        }

        //__post数据收集
        //完善门店表
        if (!($storeOne = $store->where(["store_shop_id" => $shopOne->shop_id])->find())) {
            $storeData["store_addtime"] = time();
            $store->isupdate(false)->save($storeData); //__历史数据完善
        } else {
            $store->isupdate(true)->save($storeData, ["store_shop_id" => $shopOne->shop_id]);
        }
        //完善商户
        $shop->save($shopData, ["shop_id" => $shopOne->shop_id]);
        //完善店铺资料表
        if (!($storeDataOne = $shop_data->where(["shop_data_shop_id" => $shopOne->shop_id])->find())) {
            $shop_data->isupdate(false)->save($shopDataData); //__历史数据完善
        } else {
            $shop_data->isupdate(true)->save($shopDataData, ["shop_data_shop_id" => $shopOne->shop_id]);
        }
        return ["code" => 1, "message" => "", "data" => ""];
    }
}
