<?php
namespace Jindian;

use Jindian\Config as Jindianconfig;
use Jindian\Rsa;

class Jindianapi
{
    public function orgNo()
    {
        return Jindianconfig::APPID;
    }

    public function APPPPRIVATEKEY()
    {
        return Jindianconfig::APPPPRIVATEKEY;
    }

    public function APPPUBLICKEY()
    {
        return Jindianconfig::APPPUBLICKEY;
    }

    public function uriUploadimg()
    {
        return Jindianconfig::URI_UPLOADIMG;
    }
    public function uriBase($value = '')
    {
        return Jindianconfig::URI_BASE;
    }

    /**
     * 扫码支付(商户主扫)
     * @return [type] [description]
     */
    public function sciencePay($dataArray = [])
    {

        $post_data["orgNo"]        = $this->orgNo();
        //$post_data["privateKey"]   = $this->APPPPRIVATEKEY();
        $post_data["mercId"]       = $dataArray["mercId"];
        $post_data["storeId"]      = $dataArray["storeId"];
        //$post_data["operator"]     = $dataArray["operator"]; //取消这个函数
        $post_data["amount"]       = $dataArray["amount"];
        $post_data["total_amount"] = $dataArray["total_amount"];
        $post_data["authCode"]     = $dataArray["authCode"];
        $post_data["signType"]     = "md5";
        $post_data["signValue"]    = $this->getSign($post_data);
        trace($post_data, "debug");
        trace("付款码支付", "debug");
        $this_rs = $this->httpsPost($this->uriBase() . "jdSciencePay", $post_data, "付款码支付", true);
        return $this_rs;
    }

    //获得加密字符串 md5
    public function getSign($param)
    {
        $param_string = "";
        if (is_array($param)) {
            unset($param["sign"]);
            ksort($param);
            trace("↓↓加密前的参数↓↓", "debug");
            trace($param, "debug");
            $stringtosignArray = [];
            foreach ($param as $key => $value) {
                if (!empty($value)) {
                    $stringtosignArray[] = $key . "=" . ((is_array($value) || is_object($value)) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value);
                }
            }
            $param_string = implode("&", $stringtosignArray);
            //echo $param_string."\n";
            trace("加密的string:" . $param_string, "debug");
        } else {
            $param_string = $param;
        }
        $sign = md5($param_string . $this->APPPPRIVATEKEY());
        trace("sign:" . $sign, "debug");
        trace("↑↑加密结束↑↑", "debug");
        return $sign;
    }

    //获得加密字符串
    public function getRSASign($param)
    {
        $param_string = "";
        if (is_array($param)) {
            unset($param["sign"]);
            ksort($param);
            $stringtosignArray = [];
            foreach ($param as $key => $value) {
                $stringtosignArray[] = $key . "=" . ((is_array($value) || is_object($value)) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value);
            }
            $param_string = implode("&", $stringtosignArray);
            //echo $param_string."<hr/>";
        } else {
            $param_string = $param;
        }

        $rsa  = new Rsa($this->APPPUBLICKEY, $this->APPPPRIVATEKEY());
        $sign = $rsa->sign($param_string);
        return $sign;
    }

    public function uploadimg($path)
    {
        $cachepathname = "jingjin_uploadimg" . $path;
        if ($rs = cache($cachepathname)) {
            return $rs;
        }
        $curl = curl_init();
        if (class_exists('\CURLFile')) {
            curl_setopt($curl, CURLOPT_SAFE_UPLOAD, true);
            $data = array('orgNo' => $this->orgNo(), "signvalue" => $this->getSign(['orgNo' => $this->orgNo()]), 'file' => new \CURLFile(realpath($path))); //>=5.5
        } else {
            if (defined('CURLOPT_SAFE_UPLOAD')) {
                curl_setopt($curl, CURLOPT_SAFE_UPLOAD, false);
            }
            $data = array('orgNo' => $this->orgNo(), "signvalue" => $this->getSign(['orgNo' => $this->orgNo()]), 'file' => '@' . realpath($path)); //<=5.5
        }
        curl_setopt($curl, CURLOPT_URL, $this->uriUploadimg());
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_USERAGENT, "TEST");
        $data  = curl_exec($curl);
        $error = curl_error($curl);
        curl_close($curl);
        if (stripos($data, "msg_dat") !== false) {
            $rs = json_decode($data, 1);
            switch ($rs["msg_cd"]) {
                case "000000":
                    $rs = ["code" => 1, "message" => $rs["msg_dat"], "data" => ["upload_url" => $rs["uploadUrl"]]];
                    break;

                default:
                    $rs = ["code" => 0, "message" => "【图片上传】" . $rs["msg_dat"], "data" => ""];
                    break;
            }
        } else {
            $rs = ["code" => 0, "message" => $data, "data" => ""];
        }
        cache($cachepathname, $rs);
        return $rs;
    }

    //发起进件
    public function dojingjian($dataArray)
    {

        //根据进件类型，合成数据

        $jingjin_data = [];
        switch (intval($dataArray["jinjian_class"])) {
            case 1: //标准进件
                $jingjin_data = [
                    ["key" => "orgNo", "field" => "jinjian_org_no", "sign" => 1],
                    ["key" => "type", "field" => "jinjian_type", "sign" => 1],
                    ["key" => "fullname", "field" => "jinjian_fullname", "sign" => 1],
                    ["key" => "alias", "field" => "jinjian_alias", "sign" => 1],
                    ["key" => "stoeCntNm", "field" => "jinjian_stoe_cnt_nm", "sign" => 1],
                    ["key" => "stoeCntTel", "field" => "jinjian_stoe_cnt_tel", "sign" => 1],
                    ["key" => "stoeAreaCod", "field" => "jinjian_stoe_area_cod", "sign" => 1],
                    ["key" => "stoeAdds", "field" => "jinjian_stoe_adds", "sign" => 1],
                    ["key" => "rate", "field" => "jinjian_rate", "sign" => 1],
                    ["key" => "wcLbnkNo", "field" => "jinjian_wc_lbnk_no", "sign" => 1],
                    ["key" => "stlOac", "field" => "jinjian_stl_oac", "sign" => 1],
                    ["key" => "crpNm", "field" => "jinjian_crp_nm", "sign" => 1],
                    ["key" => "crpIdNo", "field" => "jinjian_crp_id_no", "sign" => 1],
                    ["key" => "crpExpDt", "field" => "jinjian_crp_exp_dt", "sign" => 1],
                    ["key" => "loginname", "field" => "jinjian_loginname", "sign" => 1],
                    ["key" => "IDCardFront", "field" => "jinjian_id_card_front", "sign" => 1],
                    ["key" => "IDCardContrary", "field" => "jinjian_id_card_contrary", "sign" => 1],
                    ["key" => "bankCard", "field" => "jinjian_bank_card", "sign" => 1],
                    ["key" => "checkstand", "field" => "jinjian_checkstand", "sign" => 1],
                    ["key" => "headphoto", "field" => "jinjian_headphoto", "sign" => 1],
                    ["key" => "indoor", "field" => "jinjian_indoor", "sign" => 1],

                ];

                if (intval($dataArray["jinjian_type"]) == 1 || intval($dataArray["jinjian_type"]) == 2) {
                    $jingjin_data = array_merge($jingjin_data, [["key" => "businesspictureurl", "field" => "jinjian_businesspictureurl", "sign" => 0],
                        ["key" => "busLicNo", "field" => "jinjian_busLic_no", "sign" => 0],
                        ["key" => "busExpDt", "field" => "jinjian_bus_exp_dt", "sign" => 0]]);
                }

                break;

            case 2: //快速进件

                $jingjin_data = [
                    ["key" => "orgNo", "field" => "jinjian_org_no", "sign" => 1],
                    ["key" => "alias", "field" => "jinjian_alias", "sign" => 1],
                    ["key" => "stoeCntNm", "field" => "jinjian_stoe_cnt_nm", "sign" => 1],
                    ["key" => "stoeCntTel", "field" => "jinjian_stoe_cnt_tel", "sign" => 1],
                    ["key" => "stoeAreaCod", "field" => "jinjian_stoe_area_cod", "sign" => 1],
                    ["key" => "stoeAdds", "field" => "jinjian_stoe_adds", "sign" => 1],
                    ["key" => "rate", "field" => "jinjian_rate", "sign" => 1],
                    ["key" => "wcLbnkNo", "field" => "jinjian_wc_lbnk_no", "sign" => 1],
                    ["key" => "stlOac", "field" => "jinjian_stl_oac", "sign" => 1],
                    ["key" => "crpNm", "field" => "jinjian_crp_nm", "sign" => 1],
                    ["key" => "crpIdNo", "field" => "jinjian_crp_id_no", "sign" => 1],
                    ["key" => "crpExpDt", "field" => "jinjian_crp_exp_dt", "sign" => 1],
                    ["key" => "loginname", "field" => "jinjian_loginname", "sign" => 1],
                    ["key" => "IDCardFront", "field" => "jinjian_id_card_front", "sign" => 1],
                    ["key" => "IDCardContrary", "field" => "jinjian_id_card_contrary", "sign" => 1],
                    ["key" => "bankCard", "field" => "jinjian_bank_card", "sign" => 1],
                ];

                break;
            default:
                # code...
                break;
        }

        if (!$dataArray["jinjian_seller_account"]) {
            //新增
            $jingjin_data = array_merge($jingjin_data, [["key" => "mccCd", "field" => "jinjian_mcc", "sign" => 1]]);
        } else //修改
        {

            $jingjin_data = array_merge($jingjin_data, [["key" => "sellerAccount", "field" => "jinjian_seller_account", "sign" => 1]]);
        }

        $jingjin_post_data          = [];
        $jingjin_post_data_for_sign = [];
        foreach ($jingjin_data as $key => $jingjin_data_one) {

            //图片上传
            $dataArrayItemOne = $dataArray[$jingjin_data_one["field"]];
            if (stripos($dataArrayItemOne, "/uploads/") !== false) //带图片的，都丢过去
            {
                $this_rs = $this->uploadimg("." . $dataArrayItemOne);
                if ($this_rs["code"] == 0) {
                    return $this_rs;
                } else {
                    $dataArrayItemOne = $this_rs["data"]["upload_url"];
                }
            }
            $jingjin_post_data[$jingjin_data_one["key"]] = $dataArrayItemOne;
            if ($jingjin_data_one["sign"]) {
                $jingjin_post_data_for_sign[$jingjin_data_one["key"]] = $dataArrayItemOne;
            }

        }

        $jingjin_post_data["signvalue"] = $this->getSign($jingjin_post_data_for_sign);

        //推送
        switch (intval($dataArray["jinjian_class"])) {
            case 1: //标准进件
                if (!$dataArray["jinjian_seller_account"]) {
                    $rs = $this->httpsPost($this->uriBase() . "mercAdd", $jingjin_post_data, "进件");
                } else {
                    $rs = $this->httpsPost($this->uriBase() . "edit", $jingjin_post_data, "进件");
                }
                break;

            case 2: //快速进件
                if (!$dataArray["jinjian_seller_account"]) {
                    $rs = $this->httpsPost($this->uriBase() . "fastAdd", $jingjin_post_data, "进件");
                } else {
                    $rs = $this->httpsPost($this->uriBase() . "fastUpdate", $jingjin_post_data, "进件");
                }

                break;
        }
        //trace($rs, "error");
        //return ["code"=>1,"message"=>"","data"=>["stoeId"=>"101331000001493","msg_dat"=>"成功","sellerAccount"=>"000172","msg_cd"=>"000000","signValue"=>"a9c6d7e81cbee2e0baffa53261224376"]];
        if ($rs["code"] == 1) {
            switch ($rs["data"]["msg_cd"]) {
                case "000000":
                    $rs = ["code" => 1, "message" => $rs["data"]["msg_dat"], "data" => $rs["data"]];
                    break;

                default:
                    $rs = ["code" => 0, "message" => "【进件】" . $rs["data"]["msg_dat"], "data" => $rs["data"]];
                    break;
            }
        } else {
            $rs = ["code" => 0, "message" => "【进件】" . $rs["message"], "data" => $rs];
        }

        return $rs;
    }

    /**
     * 查询商户的审核状态
     * @param  string $sellerAccount [description]
     * @return [type]                [description]
     */
    public function queryStatus($sellerAccount = "")
    {
        $post_data = [
            "orgNo"         => $this->orgNo(),
            "sellerAccount" => $sellerAccount,
        ];
        $post_data["signvalue"] = $this->getSign($post_data);

        $this_rs = $this->httpsPost($this->uriBase() . "queryStatus", $post_data, "查询商户的审核状态");

        //trace($this->uriBase() . "queryStatus","error");
        //trace($post_data,"error");

        if ($this_rs["code"] == 0) {
            return $this_rs;
        }
        switch (intval($this_rs["data"]["status"])) {
            case 1: //1 表示成功
                $rs = ["code" => 1, "message" => "", "data" => $this_rs["data"]];
                break;
            case 2: //2 审核中
                $rs = ["code" => 2, "message" => "审核中", "data" => $this_rs["data"]];
                break;
            case 3: //3 驳回
                $rs = ["code" => 3, "message" => "驳回", "data" => $this_rs["data"]];
                break;
        }
        return $rs;
    }

    public function httpsPost($url, $data, $action_name = "", $is_json = false)
    {
        trace("↓↓↓开始POST↓↓↓", "debug");
        trace($url, "debug");
        trace($data, "debug");
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_TIMEOUT, 120000);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 120000);
        curl_setopt($curl, CURLOPT_POST, 1);
        if ($is_json) {
            $data_string = is_array($data) ? json_encode($data, JSON_UNESCAPED_UNICODE) : $data;
            trace($data_string, "debug");
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);
            curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data_string),
            ));
        } else {
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $result   = curl_exec($curl);
        $headinfo = curl_getinfo($curl);
        if ($this_error = curl_errno($curl) || $headinfo["http_code"]!=200 ) {
            $rs = ["code" => 0, "message" => $this_error, "data" => $result]; //通信失败
        } else {
            $rs = ["code" => 1, "message" => $result, "data" => json_decode($result, 1)]; //通信成功
        }
        curl_close($curl);
        trace($result, "debug");
        trace("↑↑↑结束POST↑↑↑", "debug");
        return $rs;

    }

    /**
     * 查询单笔订单接口
     * @return [type] [description]
     */
    public function queryOrderInfo($dataArray = [])
    {
        $post_data = [
            "orgNo"      => $this->orgNo(),
            "privateKey" => $this->APPPPRIVATEKEY(),
            "mercId"     => $dataArray["mercId"], //商户号
            "orderNo"    => $dataArray["orderNo"], //交易成功（result为 S）返回的与用户支付订单
            "tradeNo"    => $dataArray["tradeNo"], //商户支付渠道订单号  可用于退款
            "signType"   => "md5",
        ];

        $post_data["signValue"] = $this->getSign($post_data);

        //trace($post_data,"debug");
        //trace("查询单笔订单接口","debug");
        $this_rs = $this->httpsPost($this->uriBase() . "queryOrderInfo", $post_data, "查询单笔订单接口", true);

        return ["code" => 0, "message" => "", "data" => $this_rs];
    }

    /**
     * 发起退款
     * @return [type] [description]
     */
    public function refundTxnAmt($dataArray = [])
    {
        $post_data = [
            "orgNo"      => $this->orgNo(),
            "privateKey" => $this->APPPPRIVATEKEY(),
            "mercId"     => $dataArray["mercId"], //商户号
            "orderNo"    => $dataArray["orderNo"], //交易成功（result为 S）返回的与用户支付订单
            "tradeNo"    => $dataArray["tradeNo"], //商户支付渠道订单号  可用于退款
            "txnAmt"     => $dataArray["txnAmt"], //退款金额
            "signType"   => "md5",
        ];

        $post_data["signValue"] = $this->getSign($post_data);

        //trace($post_data,"debug");
        //trace("发起退款","debug");
        $this_rs = $this->httpsPost($this->uriBase() . "refundTxnAmt", $post_data, "发起退款", true);

        return ["code" => 0, "message" => "", "data" => $this_rs];
    }

    public function status($status_value, $status_value_info = "")
    {
        switch ($status_value) {
            case 3: //驳回
                return $status_value_info;
                break;
            case 1:
                return "审核通过";
                break;
            case 2:
                return "审核中";
                break;
            case 0:
                return "未提交审核";
                break;
            default:
                # code...
                break;
        }
    }
}
