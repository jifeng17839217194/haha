<?php
namespace app\dl\controller;

use app\dl\controller\Adminbase;
use Jindian\Config as Jindianconfig;
use Jindian\Jindianapi;

class Jinjian extends Adminbase
{
    //快速进件不需要的字段
    public $jinjian_class_2_no_allow = ["jinjian_fullname", "jinjian_checkstand", "jinjian_headphoto", "jinjian_indoor", "jinjian_businesspictureurl", "jinjian_busLic_no", "jinjian_bus_exp_dt"];

    //商户类型个人不需要的字段
    public $jinjian_type_3_no_allow = ["jinjian_businesspictureurl", "jinjian_busLic_no", "jinjian_bus_exp_dt"];

    public function _initialize()
    {
        parent::_initialize();
    }

    public function index()
    {
        Adminbase::checkActionAuth();

        $Jinjian = model("Jinjian");

        /*$Jindianapi = new Jindianapi();
        $rs=$Jindianapi->queryOrderInfo([
        "mercId"=>"000176",//商户号
        "orderNo"=>"20181226104444991834", //交易成功（result为 S）返回的与用户支付订单
        "tradeNo"=>"32527436596899741696", //商户支付渠道订单号  可用于退款
        ]);

        dump($rs);die;*/

        // $Jindianapi = new Jindianapi();
        // $rs=$Jindianapi->refundTxnAmt([
        //     "mercId"=>"000176",//商户号
        //     "orderNo"=>"20181226104444991834", //交易成功（result为 S）返回的与用户支付订单
        //     "tradeNo"=>"32527436596899741696", //商户支付渠道订单号  可用于退款
        //     "txnAmt"=>"0.01"
        //     ]);

        // dump($rs);die;

        $where   = [];
        $keyword = input("get.keyword", "");
        if ($keyword) {
            $where["jinjian_fullname"] = ["like", "%" . $keyword . "%"];
        }
        $lists = $Jinjian->where($where)->order(["jinjian_id desc"])->paginate(15);

        if ($lists) {
            /*$cityData=model("City")->column("city_id,city_name");
        foreach ($lists as $listsOne) {
        $listsOne->cityname= implode(",",self::idGetVal($listsOne->agents_city_id,$cityData));
        }*/
        }

        $this->assign('lists', $lists);
        return view();
    }

    public function dopaytest()
    {
        $Jinjian    = model("Jinjian");
        $Jindianapi = new Jindianapi();
        $One        = $Jinjian->where(["jinjian_id" => 14])->find();
        $cash       = "0.01";
        $rs         = $Jindianapi->sciencePay([
            "mercId"       => $One->jinjian_seller_account,
            "storeId"      => $One->jinjian_stoe_id,
            "operator"     => $One->jinjian_loginname,
            "amount"       => strval($cash), //近店只能用数字
            "total_amount" => strval($cash), //近店只能用数字
            "authCode"     => input("fkm"),
        ]);

        

        return $rs;
    }

    //
    /*private function idGetVal($idArray,$ObjArray)
    {
    $rs=[];
    foreach ($idArray as $idArraykey) {
    if(isset($ObjArray[$idArraykey]))
    {
    $rs[]=$ObjArray[$idArraykey];
    }
    }
    return $rs;
    }*/

    public function copy()
    {
        $jinjian_id = input("jinjian_id");
        $old_data   = db("jinjian")->find($jinjian_id);
        unset($old_data["jinjian_id"]);
        $old_data["jinjian_addtime"]        = time();
        $old_data["jinjian_updatetime"]     = 0;
        $old_data["jinjian_qr_url"]         = ""; //变更就清空原来的数据
        $old_data["jinjian_device_number"]  = ""; //变更就清空原来的数据
        $old_data["jinjian_trade_key"]      = ""; //变更就清空原来的数据
        $old_data["jinjian_status"]         = 0;
        $old_data["jinjian_seller_account"] = "";

        db("jinjian")->insert($old_data);
        return ["code" => 1, "message" => "已经复制", "data" => "", "url" => url("dl/jinjian/index")];
    }

    public function add()
    {
        Adminbase::checkActionAuth(request()->controller() . "/index", "add");
        $Jinjian    = model("Jinjian");
        $Jindianapi = new Jindianapi();
        $one        = [];
        if (input("jinjian_id")) {
            $one                      = $Jinjian->find(input("jinjian_id"));
            $one->jinjian_status_info = $Jindianapi->status($one->jinjian_status, $one->jinjian_status_info);
            $this->assign("isCanUpdate", $this->isCanUpdate($one->jinjian_status));
        } else {
            $this->assign("isCanUpdate", true);
        }

        //获取 地区信息码
        $jinjian_area = db("jinjian_area")->order("prov_nm asc ,city_nm asc ,area_nm asc ")->select();
        $this->assign("jinjian_area", $jinjian_area);

        $sign = $Jindianapi->getSign("orgNo=" . Jindianconfig::APPID);

        //上传限制
        //$this->assign("uploadScript",Jindianconfig::URI_UPLOADIMG);//自定义图片上传地址
        //$this->assign("uploadScriptformData",json_encode(["orgNo"=>Jindianconfig::APPID,"signvalue"=>$sign],JSON_UNESCAPED_UNICODE));//自定义图片上传其它参数
        $this->assign("uploadFileType", "image/*");
        $this->assign("uploadFileSizeLimit", "500KB");

        $this->assign("jinjian_class_no_allow", json_encode($this->jinjian_class_2_no_allow));
        $this->assign("jinjian_type_no_allow", json_encode($this->jinjian_type_3_no_allow));

        $this->assign("one", $one);
        return view();
        //继续做角色新增的动作11
    }

    public function getbankdata()
    {
        Adminbase::checkActionAuth(request()->controller() . "/index", "add");
        $by = input("by");
        switch ($by) {
            case 'prov_nm':
                $jinjian_bank = db("jinjian_brank_line_num")->group("prov_nm")->field(["prov_cd as value", "prov_nm as name"])->order("prov_cd asc")->select();
                return $jinjian_bank;
                break;
            case 'city_nm':
                $jinjian_bank = db("jinjian_brank_line_num")->where(["prov_cd" => input("prov_cd")])->group("city_nm")->field(["city_cd as value", "city_nm as name"])->order("city_cd asc")->select();
                return $jinjian_bank;
                break;
            case 'lbnk_add_nm':
                $jinjian_bank = db("jinjian_brank_line_num")->where(["city_cd" => input("city_cd"), "prov_cd" => input("prov_cd")])->group("lbnk_add_nm")->field(["lbnk_add_nm as value", "lbnk_add_nm as name"])->order("lbnk_add_cd asc")->select();
                return $jinjian_bank;
                break;
            case 'lbnk_cd_nm':
                $jinjian_bank = db("jinjian_brank_line_num")->where(["lbnk_add_nm" => input("lbnk_add_cd"), "city_cd" => input("city_cd"), "prov_cd" => input("prov_cd")])->group("lbnk_cd_nm")->field(["lbnk_cd_nm as value", "lbnk_cd_nm as name"])->order("lbnk_cd_nm asc")->select();
                return $jinjian_bank;
                break;
            case 'jinjian_wc_lbnk':
                $jinjian_bank = db("jinjian_brank_line_num")->where(["lbnk_add_nm" => input("lbnk_add_cd"), "city_cd" => input("city_cd"), "prov_cd" => input("prov_cd"), "lbnk_cd_nm" => input("lbnk_cd_nm")])->group("lbnk_nm")->field(["lbnk_no as value", "lbnk_nm as name"])->order("lbnk_nm asc")->select();
                return $jinjian_bank;
                break;

            default:
                # code...
                break;
        }
    }

    public function getmccdata()
    {
        Adminbase::checkActionAuth(request()->controller() . "/index", "add");
        $by = input("by");
        switch ($by) {
            case 'jinjian_mmc_sup_mmc_nm':
                $rs = db("jinjian_mcc")->group("sup_mmc_nm")->field(["sup_mmc_nm as value", "sup_mmc_nm as name"])->order("sup_mmc_nm asc")->select();
                return $rs;
                break;
            case 'jinjian_mmc_mmc_typ_nm':
                $rs = db("jinjian_mcc")->group("mmc_typ_nm")->where(["sup_mmc_nm" => input("jinjian_mmc_sup_mmc_nm")])->field(["mmc_typ_nm as value", "mmc_typ_nm as name"])->order("mmc_typ_nm asc")->select();
                return $rs;
                break;
            case 'jinjian_mmc_mmc_cd':
                $rs = db("jinjian_mcc")->group("mmc_nm")->where(["sup_mmc_nm" => input("jinjian_mmc_sup_mmc_nm"), "mmc_typ_nm" => input("jinjian_mmc_mmc_typ_nm")])->field(["mmc_cd as value", "CONCAT(mmc_nm,mmc_cd) as name"])->order("mmc_nm asc")->select();
                return $rs;
                break;

            default:
                # code...
                break;
        }
    }

    public function delete()
    {
        Adminbase::checkActionAuth(request()->controller() . "/index", "delete");
        $jinjian_id = input("jinjian_id/a");
        if ($jinjian_id) {
            $Jinjian = model("Jinjian");
            $Jinjian->where("jinjian_id", "in", $jinjian_id)->delete();
            return ["code" => 1, "message" => "删除成功", "wait" => -1, "url" => url('index')];
        } else {
            return ["code" => 0, "message" => "没有数据删除", "wait" => 1];
        }

    }

    //记录是否跟更新
    public function isCanUpdate($jinjian_status)
    {
        if ($jinjian_status == 3 || $jinjian_status == 0) {
            return true;
        } else {
            return false;
        }
    }

    //修改排序
    public function sortnum()
    {
        $Jinjian = model("Jinjian");
        if (!is_numeric(input("promptvalue"))) {
            $this->error("必需是数字");
        }
        $Jinjian->save(
            ['news_sortnum' => input("promptvalue"),
            ], ['jinjian_id' => input("jinjian_id")]);
        return ["code" => 1, "message" => "保存成功", "wait" => -1, "url" => request()->server("HTTP_REFERER")];
    }

    //保存或新增
    public function save()
    {
        set_time_limit(0);
        ini_set('max_execution_time', '0');

        Adminbase::checkActionAuth(request()->controller() . "/index", "add");
        $Jinjian = model("Jinjian");

        //判断重复
        $wherehave["jinjian_alias"] = input("jinjian_alias");
        if (input("jinjian_id/d") > 0) {
            $wherehave["jinjian_id"] = ["neq", input("jinjian_id/d")];
        }

        if ($Jinjian->where($wherehave)->find()) {
            return ["code" => 0, "message" => input("jinjian_alias") . " 已经存在!", "url" => "#"];
        }

        $save_data = [];
        //数据验证
        $save_data["jinjian_org_no"]             = Jindianconfig::APPID;
        $save_data["jinjian_type"]               = input("jinjian_type"); // 1
        $save_data["jinjian_class"]              = input("jinjian_class"); // 1
        $save_data["jinjian_fullname"]           = input("jinjian_fullname"); // 商户全称
        $save_data["jinjian_alias"]              = input("jinjian_alias"); // 商户简称
        $save_data["jinjian_stoe_cnt_nm"]        = input("jinjian_stoe_cnt_nm"); // 姓名
        $save_data["jinjian_stoe_cnt_tel"]       = input("jinjian_stoe_cnt_tel"); // 电话
        $save_data["jinjian_stoe_area_cod"]      = input("jinjian_stoe_area_cod"); // 340823
        $save_data["jinjian_stoe_adds"]          = input("jinjian_stoe_adds"); // 详细地址
        $save_data["jinjian_rate"]               = input("jinjian_rate"); // 0.38
        $save_data["jinjian_wc_lbnk_prov_cd"]    = input("prov_cd"); // 3000
        $save_data["jinjian_wc_lbnk_city_cd"]    = input("city_cd"); // 3020
        $save_data["jinjian_wc_lbnk_add_cd"]     = input("lbnk_add_cd"); // 宜兴市
        $save_data["jinjian_wc_lbnk_cd_nm"]      = input("lbnk_cd_nm"); // 工商银行
        $save_data["jinjian_wc_lbnk_no"]         = input("jinjian_wc_lbnk"); // 102302302964
        $save_data["jinjian_stl_oac"]            = input("jinjian_stl_oac"); // 银行卡号
        $save_data["jinjian_crp_nm"]             = input("jinjian_crp_nm"); // 姓名
        $save_data["jinjian_crp_id_no"]          = input("jinjian_crp_id_no"); // 身份证号
        $save_data["jinjian_crp_exp_dt"]         = input("jinjian_crp_exp_dt"); // 2018-12-12
        $save_data["jinjian_loginname"]          = $save_data["jinjian_stoe_cnt_tel"]; // 15968890526
        $save_data["jinjian_mmc_sup_mmc_nm"]     = input("jinjian_mmc_sup_mmc_nm"); // 一般类
        $save_data["jinjian_mmc_mmc_typ_nm"]     = input("jinjian_mmc_mmc_typ_nm"); // 百货商店
        $save_data["jinjian_mcc"]                = input("jinjian_mcc"); // 5311
        $save_data["jinjian_id_card_front"]      = input("jinjian_id_card_front", "", "trim"); // /uploads/20181217/65818496d2c7cc7f4e25559a4e65dad8.png
        $save_data["jinjian_id_card_contrary"]   = input("jinjian_id_card_contrary", "", "trim"); // /uploads/20181217/36a87ff44ba8e881ae220fced64a1cda.png
        $save_data["jinjian_bank_card"]          = input("jinjian_bank_card", "", "trim"); // /uploads/20181217/d4df7dc959c2d211c3ae34d38c3c4f1b.png
        $save_data["jinjian_checkstand"]         = input("jinjian_checkstand", "", "trim"); // /uploads/20181217/be43396e706bd9ff2a78307d20e49158.png
        $save_data["jinjian_headphoto"]          = input("jinjian_headphoto", "", "trim"); // /uploads/20181217/d516f648c759cc16ad8659c9c9da35a4.png
        $save_data["jinjian_indoor"]             = input("jinjian_indoor", "", "trim"); // /uploads/20181217/cc3bef8806571609ad27cb5b407bd5b3.png
        $save_data["jinjian_businesspictureurl"] = input("jinjian_businesspictureurl", "", "trim"); // /uploads/20181217/b09e8ebaf9297a259ff070ac583ced8e.png
        $save_data["jinjian_busLic_no"]          = input("jinjian_busLic_no"); // 营业执照号
        $save_data["jinjian_bus_exp_dt"]         = input("jinjian_bus_exp_dt"); // 2019-01-26

        $not_check_field = []; //判断哪些需要进行数据验证
        if ($save_data["jinjian_class"] == 2) //快速进件
        {
            $not_check_field = array_merge($not_check_field, $this->jinjian_class_2_no_allow);
        }
        if ($save_data["jinjian_type"] == 3) //商户类型,个人
        {
            $not_check_field = array_merge($not_check_field, $this->jinjian_type_3_no_allow);
        }

        if (!in_array("jinjian_fullname", $not_check_field)) {

            if (empty($save_data["jinjian_fullname"])) {
                return ["code" => 0, "message" => "商户全称 不为空"];
            } else {
                if (mb_strlen($save_data["jinjian_fullname"]) > 100) {
                    return ["code" => 0, "message" => "商户全称 太长了"];
                }
            }

        }
        if (!in_array("jinjian_loginname", $not_check_field)) {
            //商户全称
            if (empty($save_data["jinjian_loginname"])) {
                return ["code" => 0, "message" => "登陆手机号 不为空"];
            } else {
                if (!preg_match('/1[0-9]{10}/', $save_data["jinjian_loginname"])) {
                    return ["code" => 0, "message" => "手机号格式错误"];
                }
            }

        }
        if (!in_array("jinjian_mcc", $not_check_field)) {
            //15968890526
            if (empty($save_data["jinjian_mcc"])) {
                return ["code" => 0, "message" => "经营类目码 不为空"];
            }

        }
        if (!in_array("jinjian_alias", $not_check_field)) {
            //商户简称
            if (empty($save_data["jinjian_alias"])) {
                return ["code" => 0, "message" => "商户简称 不为空"];
            } else {
                if (mb_strlen($save_data["jinjian_alias"]) > 50) {
                    return ["code" => 0, "message" => "商户简称 太长了"];
                }
            }

        }
        if (!in_array("jinjian_stoe_cnt_nm", $not_check_field)) {
            //姓名
            if (empty($save_data["jinjian_stoe_cnt_nm"])) {
                return ["code" => 0, "message" => "商户联系人 不为空"];
            } else {
                if (mb_strlen($save_data["jinjian_stoe_cnt_nm"]) > 20) {
                    return ["code" => 0, "message" => "商户联系人 太长了"];
                }
            }

        }
        if (!in_array("jinjian_stoe_cnt_tel", $not_check_field)) {
            //电话
            if (empty($save_data["jinjian_stoe_cnt_tel"])) {
                return ["code" => 0, "message" => "商户电话 不为空"];
            } else {
                if (!preg_match('/1[0-9]{10}/', $save_data["jinjian_stoe_cnt_tel"])) {
                    return ["code" => 0, "message" => "商户联系电话必需是手机号"];
                }
            }

        }
        if (!in_array("jinjian_stoe_area_cod", $not_check_field)) {
            //
            if (empty($save_data["jinjian_stoe_area_cod"])) {
                return ["code" => 0, "message" => "地区信息码 不为空"];
            }

        }
        if (!in_array("jinjian_stoe_adds", $not_check_field)) {
            //340823
            if (empty($save_data["jinjian_stoe_adds"])) {
                return ["code" => 0, "message" => "详细地址 不为空"];
            } else {
                if (mb_strlen($save_data["jinjian_stoe_adds"]) > 100 || mb_strlen($save_data["jinjian_stoe_adds"]) < 5) {
                    return ["code" => 0, "message" => "详细地址不能少于5个字"];
                }
            }

            //详细地址
        }
        if (!in_array("jinjian_rate", $not_check_field)) {

            if (empty($save_data["jinjian_rate"])) {
                return ["code" => 0, "message" => "费率 不为空"];
            } else {
                if (!($save_data["jinjian_rate"] >= 0.25 && $save_data["jinjian_rate"] <= 0.6)) {
                    return ["code" => 0, "message" => "费率最小0.25,最大不超过 0.6"];
                }
            }

            //0.38
        }
        if (!in_array("jinjian_wc_lbnk_no", $not_check_field)) {

            if (empty($save_data["jinjian_wc_lbnk_no"])) {
                return ["code" => 0, "message" => "开户行分行 不为空"];
            }
            //102302302964
        }
        if (!in_array("jinjian_stl_oac", $not_check_field)) {

            if (empty($save_data["jinjian_stl_oac"])) {
                return ["code" => 0, "message" => "银行卡号 不为空"];
            } else {
                if (mb_strlen($save_data["jinjian_stl_oac"]) > 23 || mb_strlen($save_data["jinjian_stl_oac"]) < 12) {
                    return ["code" => 0, "message" => "银行卡号长度不正确"];
                }
            }

        }
        if (!in_array("jinjian_crp_nm", $not_check_field)) {
            //银行卡号
            if (empty($save_data["jinjian_crp_nm"])) {
                return ["code" => 0, "message" => "银行卡姓名 不为空"];
            } else {
                if (mb_strlen($save_data["jinjian_crp_nm"]) > 20 || mb_strlen($save_data["jinjian_crp_nm"]) < 2) {
                    return ["code" => 0, "message" => "银行卡姓名长度不正确"];
                }
            }
        }
        if (!in_array("jinjian_crp_id_no", $not_check_field)) {
            //姓名
            if (empty($save_data["jinjian_crp_id_no"])) {
                return ["code" => 0, "message" => "身份证号 不为空"];
            } else {
                if (mb_strlen($save_data["jinjian_crp_id_no"]) != 15 && mb_strlen($save_data["jinjian_crp_id_no"]) != 18) {
                    return ["code" => 0, "message" => "身份证号长度不正确"];
                }
            }

        }
        if (!in_array("jinjian_crp_exp_dt", $not_check_field)) {
            //身份证号
            if (empty($save_data["jinjian_crp_exp_dt"])) {
                return ["code" => 0, "message" => "身份证到期日 不为空"];
            } else {
                if (strtotime($save_data["jinjian_crp_exp_dt"]) < time("today")) {
                    return ["code" => 0, "message" => "身份证已经过期"];
                }
            }

        }
        if (!in_array("jinjian_id_card_front", $not_check_field)) {
            //2018-12-12
            if (empty($save_data["jinjian_id_card_front"])) {
                return ["code" => 0, "message" => "身份证正面 不为空"];
            }
        }
        if (!in_array("jinjian_id_card_contrary", $not_check_field)) {
            ///
            if (empty($save_data["jinjian_id_card_contrary"])) {
                return ["code" => 0, "message" => "身份证反面 不为空"];
            }
        }
        if (!in_array("jinjian_bank_card", $not_check_field)) {
            ///
            if (empty($save_data["jinjian_bank_card"])) {
                return ["code" => 0, "message" => "银行卡照 不为空"];
            }

        }
        if (!in_array("jinjian_checkstand", $not_check_field)) {
            //5311
            if (empty($save_data["jinjian_checkstand"])) {
                return ["code" => 0, "message" => "收银台照片 不为空"];
            }

        }
        if (!in_array("jinjian_headphoto", $not_check_field)) {
            ///uploads/20181217
            if (empty($save_data["jinjian_headphoto"])) {
                return ["code" => 0, "message" => "门头照 不为空"];
            }

        }
        if (!in_array("jinjian_indoor", $not_check_field)) {
            ///uploads/20181217/
            if (empty($save_data["jinjian_indoor"])) {
                return ["code" => 0, "message" => "内景照 不为空"];
            }

        }
        if (!in_array("jinjian_businesspictureurl", $not_check_field)) {
            ///
            if (empty($save_data["jinjian_businesspictureurl"])) {
                return ["code" => 0, "message" => "营业执照 不为空"];
            }

        }
        if (!in_array("jinjian_busLic_no", $not_check_field)) {
            ///uploads/20181217/
            if (empty($save_data["jinjian_busLic_no"])) {
                return ["code" => 0, "message" => "营业执照号 不为空"];
            } else {
                if (mb_strlen($save_data["jinjian_busLic_no"]) > 27 || mb_strlen($save_data["jinjian_busLic_no"]) < 8) {
                    return ["code" => 0, "message" => "营业执照号长度不正确"];
                }
            }

        }
        if (!in_array("jinjian_bus_exp_dt", $not_check_field)) {
            //营业执照号
            if (empty($save_data["jinjian_bus_exp_dt"])) {
                return ["code" => 0, "message" => "营业执照有效期 不为空"];
            } else {
                if (strtotime($save_data["jinjian_bus_exp_dt"]) < time("today")) {
                    return ["code" => 0, "message" => "营业执照已经过期"];
                }
            }
        }

        if (input("jinjian_id/d") > 0) {
            //unset($save_data["jinjian_class"]); //目前不支持修改进件类型
            $save_data["jinjian_updatetime"] = time();

            $save_data["jinjian_qr_url"]        = ""; //变更就清空原来的数据
            $save_data["jinjian_device_number"] = ""; //变更就清空原来的数据
            $save_data["jinjian_trade_key"]     = ""; //变更就清空原来的数据

            $jinjian_status = db("jinjian")->where(["jinjian_id" => input("jinjian_id/d")])->value("jinjian_status");

            if ($this->isCanUpdate($jinjian_status) == false) {
                return ["code" => 0, "message" => "当前状态，不可操作" . $jinjian_status, "data" => ""];
            }

        } else {

            $save_data["jinjian_addtime"] = input("jinjian_addtime", time());
        }

        $Jinjian->save($save_data, input("jinjian_id") ? ['jinjian_id' => input("jinjian_id")] : null);
        if (!$jinjian_id = input("jinjian_id")) {
            $jinjian_id = $Jinjian->jinjian_id;
        }

        //以上是数据校正，以及本地保存

        $Jindianapi = new Jindianapi();
        $rs         = $Jindianapi->dojingjian(db("jinjian")->find($jinjian_id));

        if ($rs["code"] == 1) {
            //["msg_dat"=>"成功","sellerAccount"=>"000172","msg_cd"=>"000000","signValue"=>"a9c6d7e81cbee2e0baffa53261224376"]
            $jj_rs = $rs["data"];
            $this_update_data=[];
            $this_update_data["jinjian_status"]=2;
            if(isset($jj_rs["sellerAccount"]))$this_update_data["jinjian_seller_account"]=$jj_rs["sellerAccount"];// 进件修改是没有这个字段的
            db("jinjian")->where(["jinjian_id" => $jinjian_id])->update($this_update_data);

            return ["code" => 1, "message" => "进件已经提交，等待审核", "wait" => 3, "url" => url('index')];
        } else {
            return $rs;
        }
    }

}
