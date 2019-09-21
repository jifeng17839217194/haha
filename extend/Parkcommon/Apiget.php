<?php
namespace Parkcommon;
use think\Log;

/**
 * 停车场设备统一接口数据处理
 * 我方主动向停车场设备查询数据
 */
class Apiget
{

    public function __construct()
    {

    }

    public function test()
    {

    }

    /**
     * 获取获取车道信息
     * $param["uuid"]//停车场唯一ID
     * $param["from_compay"]//停车场类型,"epapi"、"kede"
     */
    public function getPort($param = [])
    {
        $data = [];
        /*
        $data["park_list"] = [
        [
        "centry_name"=>"岗亭名称",
        "port_name"=>"车道名称",
        "park_id"=>"通道ID",
        "in_or_out"=>"in入口，out出口，-1未知"

        ]
        ];
         */
        $rs = ["code" => 1, "message" => "", "data" => $data];
        switch (strtolower($param["from_compay"])) {
            //设备来源公司
            case 'epapi': //易泊

                $sendData            = [];
                $sendData["park_id"] = $param["uuid"];

                $Epapi   = new \Epapi\Api();
                $rsArray = $Epapi->getVehicleInfo($sendData);
                if ($rsArray["code"] == 0) {
                    //易泊没有查询到
                    $rs["code"]    = 0;
                    $rs["message"] = $rsArray["message"];
                } else {
                    if (count($rsArray["data"]) > 0) {
                        foreach ($rsArray["data"]["port"] as $portOne) {
                            $data["park_list"][] = [
                                "centry_name" => $portOne["centry_name"],
                                "port_name"   => $portOne["centry_name"],
                                "port_id"     => $portOne["port_id"],
                                "in_or_out"   => "", //易泊没这个数据
                            ];
                        }
                    } else {
                        $rs["code"]    = 0;
                        $rs["message"] = "获得0条车道数据";
                    }
                }

                /*$rsArray             = model("epapi")->sendData("getPortId", $sendData);
                trace($rsArray);
                //{"error_des":"获取进出口id成功！","park_id":"25404241023598608","park_name":"杭州专注科技停车场","port":[{"centry_name":"岗亭入口","port_id":"1","port_name":"入口"},{"centry_name":"岗亭入口","port_id":"4","port_name":"出口"}],"ret_code":"1","signature":"53cfbe2b4a64c58d7b8aed7b886eefc9fae70e3f60c4d3f82440af56e010b9cc","timestamp":"1531107804"}
                if ($rsArray["ret_code"] == 1) {

                foreach ($rsArray["port"] as $portOne) {
                $data["park_list"][] = [
                "centry_name" => $portOne["centry_name"],
                "port_name"   => $portOne["centry_name"],
                "port_id"     => $portOne["port_id"],
                "in_or_out"   => "", //易泊没这个数据
                ];
                }
                } else {
                $rs["code"]    = 0;
                $rs["message"] = $rsArray["error_des"];
                }*/

                break;

            case "kede": //科德
                $kede    = new \Kede\Api();
                $rsArray = $kede->getVehicleInfo($param["uuid"]);
                if ($rsArray["code"] == 1) {
                    if (count($rsArray["data"]) > 0) {
                        foreach ($rsArray["data"] as $portOne) {
                            $data["park_list"][] = [
                                "centry_name" => $portOne["sentryboxNo"],
                                "port_name"   => $portOne["vehName"],
                                "port_id"     => $portOne["parkKey"],
                                "in_or_out"   => ($portOne["vehType"] == "0" ? "in" : "out"), //0入口,1出口
                            ];
                        }
                    } else {
                        $rs["code"]    = 0;
                        $rs["message"] = "获得0条车道数据";
                    }
                } else {
                    $rs["code"]    = 0;
                    $rs["message"] = $rsArray["msg"];
                }
                break;
            default:
                $rs["code"]    = 0;
                $rs["message"] = "未知的停车场类型" . $param["from_compay"];
                break;
        }
        $rs["data"] = $data;
        return $rs;
    }

    /**
     * 查询停车费
     * $param["uuid"]//停车场唯一ID
     * $param["order_id"]//停车订单号 同一车场下唯一(第三方停车场的ID)
     * $param["car_number"]//车牌号
     * $param["from_compay"]//停车场类型,"epapi"、"kede"
     */
    public function getOrderFee($param = [])
    {
        $cachename = $param["uuid"] . $param["car_number"];
        if (cache($cachename)) {
            return cache($cachename);
        }

        //统一返回数据格式
        $data["fee"]      = 0;
        $data["order_id"] = 0;
        //__统一返回数据格式

        $rs = ["code" => 1, "message" => "", "data" => $data];
        switch (strtolower($param["from_compay"])) {
            //设备来源公司
            case 'epapi': //易泊

                $sendData             = [];
                $sendData["park_id"]  = $param["uuid"];
                $sendData["plate_id"] = $param["car_number"];
                $sendData["order_id"] = $param["order_id"];

                $Epapi   = new \Epapi\Api();
                $rsArray = $Epapi->getOrderFee($sendData);
                Log::write('易泊返回的的数据:'.json_encode($rsArray,JSON_UNESCAPED_UNICODE)."\G",'log');
                if ($rsArray["code"] == 0) {
                    //易泊没有查询到
                    $rs["code"]    = 0;
                    $rs["message"] = $rsArray["message"];
                } else {
                    $data["fee"]      = $rsArray["data"]["pay_amount"];
                    $data["order_id"] = $rsArray["data"]["cario_id"];
                }

                /*$rsArray              = model("epapi")->sendData("GetPayInfo", $sendData);
                if (ceil($rsArray["ret_code"]) != 1) {
                //易泊没有查询到
                $rs["code"]    = 0;
                $rs["message"] = $rsArray["error_des"];
                } else {
                $data["fee"]      = $rsArray["pay_amount"];
                $data["order_id"] = $rsArray["cario_id"];
                }*/

                break;

            case "kede": //科德
                $kede                = new \Kede\Api();
                $sendData            = [];
                $sendData["parkKey"] = $param["uuid"];
                $sendData["orderNo"] = $param["order_id"];

                $rsArray = $kede->getOrderFee($sendData);
                if ($rsArray["code"] == 1) {
                    $data["fee"] = $rsArray["data"]["totalAmount"];
                } else {
                    $rs["code"]    = 0;
                    $rs["message"] = $rsArray["msg"];
                }

                break;
            default:
                $rs["code"]    = 0;
                $rs["message"] = "未知的停车场类型" . $param["from_compay"];
                break;
        }
        $rs["data"] = $data;
        cache($cachename, $rs, 1); //避免某些地方同一个执行向硬件发起多次查询
        return $rs;
    }

    /**
     * 查询停车场剩余车位
     * $param["uuid"]//停车场唯一ID
     * $param["from_compay"]//停车场类型,"epapi"、"kede"
     */
    public function getRemainingSpace($param = [])
    {

        //统一返回数据格式
        $data                 = [];
        $data["total_spaces"] = 0; //总车位数
        $data["remai_spaces"] = 0; //剩余车位数
        //__统一返回数据格式
        $rs = ["code" => 1, "message" => "", "data" => $data];
        switch (strtolower($param["from_compay"])) {
            //设备来源公司
            case 'epapi': //易泊
                $sendData            = [];
                $sendData["park_id"] = $param["uuid"];

                $Epapi   = new \Epapi\Api();
                $rsArray = $Epapi->getRemainingSpace($sendData);
                if ($rsArray["code"] == 0) {
                    //易泊没有查询到
                    $rs["code"]    = 0;
                    $rs["message"] = $rsArray["message"];
                } else {
                    $data["total_spaces"] = $rsArray["data"]["parkspaces_num"];
                    $data["remai_spaces"] = $rsArray["data"]["parkspaces_Surplus"];
                }

                /*$rsArray             = model("epapi")->sendData("GetPlaceCount", $sendData);

                if (ceil($rsArray["ret_code"]) != 1) {
                //易泊没有查询到
                $rs["code"]    = 0;
                $rs["message"] = $rsArray["error_des"];
                } else {
                $data["total_spaces"] = $rsArray["parkspaces_num"]; //总车位数
                $data["remai_spaces"] = $rsArray["parkspaces_Surplus"]; //剩余车位数
                }*/

                break;

            case "kede": //科德
                $kede                = new \Kede\Api();
                $sendData            = [];
                $sendData["parkKey"] = $param["uuid"];
                $rsArray             = $kede->getRemainingSpace($sendData);
                if ($rsArray["code"] == 1) {
                    $data["total_spaces"] = $rsArray["data"]["totalSpaces"];
                    $data["remai_spaces"] = $rsArray["data"]["remaiSpaces"];
                } else {
                    $rs["code"]    = 0;
                    $rs["message"] = $rsArray["msg"];
                }
                break;
            default:
                $rs["code"]    = 0;
                $rs["message"] = "未知的停车场类型" . $param["from_compay"];
                break;
        }
        $rs["data"] = $data;
        return $rs;
    }

    /**
     * 功能模块说明
     * 获取到最后一条停车记录
     * $param["uuid"]//停车场id
     * $param["car_number"]//车牌号
     * $param["from_compay"]//停车场类型,"epapi"、"kede"
     */
    public function findCarIoInfoInLast($param = [])
    {
        //统一返回数据格式
        $data             = [];
        $data["in_time"]  = 0; //入场时间
        $data["out_time"] = 0; //出场时间
        //__统一返回数据格式
        $rs = ["code" => 1, "message" => "", "data" => $data];
        switch (strtolower($param["from_compay"])) {
            //设备来源公司
            case 'epapi': //易泊

                $sendData             = [];
                $sendData["park_id"]  = $param["uuid"];
                $sendData["plate_id"] = $param["car_number"];
                // $rsArray              = model("epapi")->sendData("findCarIoInfoIn", $sendData);
                // if (ceil($rsArray["ret_code"]) != 1) {
                //     //易泊没有查询到
                //     $rs["code"]    = 0;
                //     $rs["message"] = $rsArray["error_des"];
                // } else {
                //     $last_record = $rsArray["record"][count($rsArray["record"]) - 1];
                //     if ($last_record["access_time_in"]) {
                //         $data["in_time"] = strtotime($last_record["access_time_in"]);
                //     }

                //     if ($last_record["access_time_out"]) {
                //         $data["out_time"] = strtotime($last_record["access_time_out"]);
                //     }

                // }

                break;

            case "kede": //科德

                break;
            default:
                $rs["code"]    = 0;
                $rs["message"] = "未知的停车场类型" . $param["from_compay"];
                break;
        }
        $rs["data"] = $data;
        return $rs;
    }

    /**
     * 下发预支付完成的指令
     * $param["uuid"]//停车场唯一ID/编号
     * $param["out_port_id"]//出场出口ID，场内预付的为空
     * $param["car_number"]//车牌号
     * $param["order_no"]//智慧收银的订单号
     * $param["order_id"]//线下停车场的订单号
     * $param["pay_time"]//支付时间，时间戳
     * $param["pay_amount"]//实际支付金额
     * $param["pay_id"]//实际支付金额的订单号
     * $param["pay_finish_type"]//0 预支付完成，1 出场支付完成(1会直接开门)
     * $param["pay_type"]//支付帐户类型，1-微信、2-支付宝
     * $param["from_compay"]//停车场类型,"epapi"、"kede"
     */
    public function sendPayOk($param = [])
    {
        //统一返回数据格式
        $data = [];
        //__统一返回数据格式
        $rs = ["code" => 1, "message" => "", "data" => $data];
        switch (strtolower($param["from_compay"])) {
            //设备来源公司
            case 'epapi': //易泊
                $sendData                      = [];
                $sendData["park_id"]           = $param["uuid"];
                $sendData["port_id"]           = $param["out_port_id"];
                $sendData["plate_id"]          = $param["car_number"];
                $sendData["order_id"]          = $param["order_no"];
                $sendData["cario_id"]          = $param["order_id"];
                $sendData["pay_time"]          = date("Y-m-d H:i:s", $param["pay_time"]);
                $sendData["pay_amount"]        = $param["pay_amount"];
                $sendData["pay_id"]            = $param["pay_id"];
                $sendData["accept_account_id"] = "";
                $sendData["pay_finish_type"]   = $param["pay_finish_type"];
                $sendData["pay_type"]          = $param["pay_type"];

                $Epapi   = new \Epapi\Api();
                $rsArray = $Epapi->sendPayOk($sendData);
                if ($rsArray["code"] == 0) {
                    $rs["code"]    = 0;
                    $rs["message"] = $rsArray["message"];
                } else {

                }

                break;

            case "kede": //科德
                // $kede                = new \Kede\Api();
                // $sendData            = [];
                // $sendData["parkKey"] = $param["uuid"];
                // $sendData["xxxxxx"]  = $param["xxxxxx"];

                // $rsArray = $kede->xxxxxxx($sendData);
                // if ($rsArray["code"] == 1) {
                //     $data["xxxxx1"] = $rsArray["data"]["xxxxx1"];
                //     $data["xxxxx2"] = $rsArray["data"]["xxxxx2"];
                // } else {
                //     $rs["code"]    = 0;
                //     $rs["message"] = $rsArray["msg"];
                // }
                break;
            default:
                $rs["code"]    = 0;
                $rs["message"] = "未知的停车场类型" . $param["from_compay"];
                break;
        }
        $rs["data"] = $data;
        return $rs;
    }

    /**
     * LED显示
     * $param["uuid"]//停车场唯一ID/编号
     * $param["port_id"]//指定道口
     * $param["from_compay"]//停车场类型,"epapi"、"kede"
     * $param["text"]//显示的文本
     */
    public function ledDisplay($param = [])
    {
        //统一返回数据格式
        $data = [];
        //__统一返回数据格式
        $rs = ["code" => 1, "message" => "", "data" => $data];
        switch (strtolower($param["from_compay"])) {
            //设备来源公司
            case 'epapi': //易泊
                $sendData            = [];
                $sendData["park_id"] = $param["uuid"];
                $sendData["port_id"] = $param["port_id"];
                $sendData["text"]    = $param["text"];

                $Epapi         = new \Epapi\Api();
                $rsArray       = $Epapi->ledDisplay($sendData);
                $rs["message"] = $rsArray["message"];
                if ($rsArray["code"] == 0) {
                    $rs["code"] = 0;
                } else {

                }

                break;

            case "kede": //科德
                // $kede                = new \Kede\Api();
                // $sendData            = [];
                // $sendData["parkKey"] = $param["uuid"];
                // $sendData["xxxxxx"]  = $param["xxxxxx"];

                // $rsArray = $kede->xxxxxxx($sendData);
                // if ($rsArray["code"] == 1) {
                //     $data["xxxxx1"] = $rsArray["data"]["xxxxx1"];
                //     $data["xxxxx2"] = $rsArray["data"]["xxxxx2"];
                // } else {
                //     $rs["code"]    = 0;
                //     $rs["message"] = $rsArray["msg"];
                // }
                break;
            default:
                $rs["code"]    = 0;
                $rs["message"] = "未知的停车场类型" . $param["from_compay"];
                break;
        }
        $rs["data"] = $data;
        return $rs;
    }

    /**
     * 播放声音
     * $param["uuid"]//停车场唯一ID/编号
     * $param["port_id"]//指定道口
     * $param["from_compay"]//停车场类型,"epapi"、"kede"
     * $param["voice_text"]//播放的文本
     */
    public function playVoice($param = [])
    {
        //统一返回数据格式
        $data = [];
        //__统一返回数据格式
        $rs = ["code" => 1, "message" => "", "data" => $data];
        switch (strtolower($param["from_compay"])) {
            //设备来源公司
            case 'epapi': //易泊
                $sendData               = [];
                $sendData["park_id"]    = $param["uuid"];
                $sendData["port_id"]    = $param["port_id"];
                $sendData["voice_text"] = $param["voice_text"];

                $Epapi         = new \Epapi\Api();
                $rsArray       = $Epapi->playVoice($sendData);
                $rs["message"] = $rsArray["message"];
                if ($rsArray["code"] == 0) {
                    $rs["code"] = 0;
                } else {
                    
                }

                break;

            case "kede": //科德
                // $kede                = new \Kede\Api();
                // $sendData            = [];
                // $sendData["parkKey"] = $param["uuid"];
                // $sendData["xxxxxx"]  = $param["xxxxxx"];

                // $rsArray = $kede->xxxxxxx($sendData);
                // if ($rsArray["code"] == 1) {
                //     $data["xxxxx1"] = $rsArray["data"]["xxxxx1"];
                //     $data["xxxxx2"] = $rsArray["data"]["xxxxx2"];
                // } else {
                //     $rs["code"]    = 0;
                //     $rs["message"] = $rsArray["msg"];
                // }
                break;
            default:
                $rs["code"]    = 0;
                $rs["message"] = "未知的停车场类型" . $param["from_compay"];
                break;
        }
        $rs["data"] = $data;
        return $rs;
    }

    /**
     * 开闸
     * $param["uuid"]//停车场唯一ID/编号
     * $param["port_id"]//指定道口
     * $param["from_compay"]//停车场类型,"epapi"、"kede"
     */
    public function openDoor($param = [])
    {
        //统一返回数据格式
        $data = [];
        //__统一返回数据格式
        $rs = ["code" => 1, "message" => "", "data" => $data];
        switch (strtolower($param["from_compay"])) {
            //设备来源公司
            case 'epapi': //易泊
                $sendData            = [];
                $sendData["park_id"] = $param["uuid"];
                $sendData["port_id"] = $param["port_id"];

                //model("epapi")->sendData("OpenDoor", $sendData, "yes"); //无需回调
                //易泊的开闸api的还没有给我，2019-1-17 09:21:16，程剑虎
                // $Epapi   = new \Epapi\Api();
                // $rsArray = $Epapi->sendPayOk($sendData);
                // if ($rsArray["code"] == 0) {
                //     $rs["code"]    = 0;
                //     $rs["message"] = $rsArray["message"];
                // } else {

                // }

                break;

            case "kede": //科德
                // $kede                = new \Kede\Api();
                // $sendData            = [];
                // $sendData["parkKey"] = $param["uuid"];
                // $sendData["xxxxxx"]  = $param["xxxxxx"];

                // $rsArray = $kede->xxxxxxx($sendData);
                // if ($rsArray["code"] == 1) {
                //     $data["xxxxx1"] = $rsArray["data"]["xxxxx1"];
                //     $data["xxxxx2"] = $rsArray["data"]["xxxxx2"];
                // } else {
                //     $rs["code"]    = 0;
                //     $rs["message"] = $rsArray["msg"];
                // }
                break;
            default:
                $rs["code"]    = 0;
                $rs["message"] = "未知的停车场类型" . $param["from_compay"];
                break;
        }
        $rs["data"] = $data;
        return $rs;
    }

    /**
     * 添加内部车
     * $param["uuid"]//停车场唯一ID/编号
     * $param["from_compay"]//停车场类型,"epapi"、"kede"
     */
    public function addInnerCar($param = [])
    {
        //统一返回数据格式
        $data = [];
        //__统一返回数据格式
        $rs = ["code" => 1, "message" => "", "data" => $data];
        switch (strtolower($param["from_compay"])) {
            //设备来源公司
            case 'epapi': //易泊
                $sendData                    = [];
                $sendData["park_id"]         = $param["uuid"];
                $sendData["plate_id"]        = $param["car_number"];
                $sendData["isinout"]         = $param["isinout"];
                $sendData["plate_color"]     = $param["plate_color"];
                $sendData["plate_type"]      = $param["plate_type"];
                $sendData["plate_state"]     = $param["plate_state"];
                $sendData["plate_subtype"]   = $param["plate_subtype"];
                $sendData["free_time"]       = $param["free_time"];
                $sendData["begin_date"]      = $param["begin_date"];
                $sendData["end_date"]        = $param["end_date"];
                $sendData["carown_name"]     = $param["carown_name"];
                $sendData["carown_sex"]      = $param["carown_sex"];
                $sendData["carown_phone"]    = $param["carown_phone"];
                $sendData["carown_cardtype"] = $param["carown_cardtype"];
                $sendData["carown_cardnum"]  = $param["carown_cardnum"];
                $sendData["carown_birsday"]  = $param["carown_birsday"];
                $sendData["carown_address"]  = $param["carown_address"];
                $sendData["charg_scheme"]    = $param["charg_scheme"];
                $sendData["del_record"]      = $param["del_record"];

                $Epapi         = new \Epapi\Api();
                $rsArray       = $Epapi->addInnerCar($sendData);
                $rs["message"] = $rsArray["message"];
                if ($rsArray["code"] == 0) {
                    $rs["code"] = 0;
                } else {
                }

                break;

            case "kede": //科德
                // $kede                = new \Kede\Api();
                // $sendData            = [];
                // $sendData["parkKey"] = $param["uuid"];
                // $sendData["xxxxxx"]  = $param["xxxxxx"];

                // $rsArray = $kede->xxxxxxx($sendData);
                // if ($rsArray["code"] == 1) {
                //     $data["xxxxx1"] = $rsArray["data"]["xxxxx1"];
                //     $data["xxxxx2"] = $rsArray["data"]["xxxxx2"];
                // } else {
                //     $rs["code"]    = 0;
                //     $rs["message"] = $rsArray["msg"];
                // }
                break;
            default:
                $rs["code"]    = 0;
                $rs["message"] = "未知的停车场类型" . $param["from_compay"];
                break;
        }
        $rs["data"] = $data;
        return $rs;
    }

    /**
     * 功能模块说明
     * $param["uuid"]//停车场唯一ID
     * $param["xxxxx"]//停车订单号 同一车场下唯一(第三方停车场的ID)
     * $param["xxxxxrrrr"]//车牌号
     * $param["from_compay"]//停车场类型,"epapi"、"kede"
     */
    public function demoGetFun($param = [])
    {
        //统一返回数据格式
        $data          = [];
        $data["xxxx"]  = 0; //------
        $data["xxxx2"] = 0; //------
        //__统一返回数据格式
        $rs = ["code" => 1, "message" => "", "data" => $data];
        switch (strtolower($param["from_compay"])) {
            //设备来源公司
            case 'epapi': //易泊
                $sendData             = [];
                $sendData["park_id"]  = $param["uuid"];
                $sendData["plate_id"] = $param["car_number"];
                $sendData["order_id"] = $param["order_id"];

                $Epapi         = new \Epapi\Api();
                $rsArray       = $Epapi->xxxxxxx($sendData);
                $rs["message"] = $rsArray["message"];
                if ($rsArray["code"] == 0) {
                    $rs["code"] = 0;
                } else {
                }

                break;

            case "kede": //科德
                $kede                = new \Kede\Api();
                $sendData            = [];
                $sendData["parkKey"] = $param["uuid"];
                $sendData["xxxxxx"]  = $param["xxxxxx"];

                $rsArray = $kede->xxxxxxx($sendData);
                if ($rsArray["code"] == 1) {
                    $data["xxxxx1"] = $rsArray["data"]["xxxxx1"];
                    $data["xxxxx2"] = $rsArray["data"]["xxxxx2"];
                } else {
                    $rs["code"]    = 0;
                    $rs["message"] = $rsArray["msg"];
                }
                break;
            default:
                $rs["code"]    = 0;
                $rs["message"] = "未知的停车场类型" . $param["from_compay"];
                break;
        }
        $rs["data"] = $data;
        return $rs;
    }

}
