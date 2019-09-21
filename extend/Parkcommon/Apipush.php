<?php
namespace Parkcommon;

/**
 * 停车场设备统一接口数据处理
 * 停车场设备向我方主动推送数据(或者回调)
 */
class Apipush
{
    public function __construct()
    {
        
    }
    /**
     * [autoaction 自动分析厂商类型]
     * 该请求暴露在对方POST环境中（即：可以获得post数据）
     *
     * @return 当前动作的名称，以及需要的全部数据
     */
    public function autoaction()
    {
        $from_compay = input("from_compay", "epapi"); //默认易泊
        $apiname     = input("apiname");
        $postdata    = [];
        switch ($from_compay) {
            case 'epapi':
                $param           = input("param", "", null);
                $param_urldecode = htmlspecialchars_decode($param);
                $postdata        = json_decode($param_urldecode, 1);

                $signtype = input("signtype", "");
                switch ($signtype) {
                    case 'epapihttp': //使用易泊自己的签名认证 (兼容SDK，可以同时推 2019-1-18 17:59:36)
                        $EpapiNet     = new \Epapi\Net();
                        //trace($postdata,"debug");
                        $signvalue = $EpapiNet->getsignvalue($postdata);
                        if($signvalue!=$postdata["signature"])
                        {
                            //trace("autoaction callbacknotice签名验证失败", "error"); //跟易泊的不一致，先去除掉
                            //return ["code" => 0, "message" => "epapi 签名验证失败", "data" => input("post.")];
                        }
                        break;

                    default:
                        //(兼容SDK，可以同时推 2019-1-18 17:59:36)
                        
                        //数据签名校正
                        $time           = input("time");
                        $sign           = input("sign");
                        $paramForSign   = urlencode(htmlspecialchars_decode($param));
                        $_POST["param"] = $paramForSign; //从C#那里传递过来的参数不一致了，处理下，给sign用
                        
                        //该SDK接口已经放弃，不再使用，2019年1月17日09:24:32，程剑虎
                        //$yz             = model("epapi")->verifyPostData(config("carpark_token"));
                        //if (!$yz["code"]) //签名没通过验证
                        //{
                        //    trace("autoaction callbacknotice签名验证失败", "error");
                        //    return ["code" => 0, "message" => "epapi 签名验证失败", "data" => input("post.")];
                        //}

                        break;
                }

                switch ($apiname) {
                    case 'inpark_end':
                    case 'inParkEnd'://http api版本的标示
                        $apiname = "entercar";
                        break;
                    case 'inpark_modify':
                        $apiname = "updateincar";
                        break;
                    case 'outpark_end':
                    case 'outParkEnd'://http api版本的标示
                        $apiname = "outcar";
                        break;
                    default:
                        # code...
                        break;
                }
                break;
            case 'kede':
                //数据签名校正
                $postdata = file_get_contents("php://input");
                $postdata = $postdataArray = json_decode($postdata, 1);
                $postdata = $param_array["data"];
                return ["code" => 0, "message" => "kede 签名验证失败", "data" => input("post.")];
                break;
            case 'epapi_db': //数据的同步处理

                $postdata = $_POST;

                # code...
                break;
            default:
                return ["code" => 0, "message" => "未知的停车场类型" . $from_compay, "data" => ""];
                break;
        }
        if (method_exists($this, $apiname)) {
            return $this->$apiname($from_compay, $postdata, $apiname); //转向自定义的动作
        } else {
            return ["code" => 0, "message" => $apiname . " 不存在," . $from_compay, "data" => ""];
        }
    }
    /**
     * 车辆入场
     * 整理成统一的数据格式
     * 本系统对入场不做强制推送要求，可以由出口时、查询费用时补全！（考虑到断网、断电、服务器异常都有可以没有保存好入场记录） 2018-12-24 10:53:14 程剑虎
     * URL:http://xxx.com/api/carpark/callbacknotice/from_compay/epapi/apiname/entercar
     */
    public function entercar($from_compay, $postdata, $apiname)
    {
        //统一返回数据格式
        $data                = [];
        $data["apiname"]     = $apiname; //动作名称
        $data["park_id"]     = ""; //停车场ID  第三方的
        $data["car_number"]  = ""; //车牌号
        $data["access_time"] = ""; //通过时间，时间戳
        $data["car_type"]    = ""; //车辆类型（大车、小车，可为空）
        $data["in_type"]     = ""; //进场类型（自动识别等，可为空）
        $data["order_id"]    = ""; //停车场的订单号
        $data["empty_plot"]  = ""; //空闲车位（可为空）
        $data["port_id"]     = ""; //通道ID
        $data["plate_type"]  = ""; //临时车
        //__统一返回数据格式
        $rs = ["code" => 1, "message" => "", "data" => $data];
        switch ($from_compay) {
            case 'epapi':
                //{"access_id":"159","access_time":"2018-07-09 15:23:23","cario_id":"105","park_id":"25404241023598608","park_name":"杭州专注科技停车场","parkingName":"智能车牌识别停车场","parking_spaceNum":"1139","plate_color":"蓝","plate_id":"浙AK219M","plate_state":"正常","plate_subtype":"","plate_type":"临时车","port_id":"1","signature":"2d96882fe5f7aa15f4805fc43bf5b2085259541c09ab306757403a762a29b52e","timestamp":"1531121002"}
                $data["park_id"]     = $postdata["park_id"]; //停车场ID
                $data["car_number"]  = trim(strtoupper($postdata["plate_id"])); //车牌号
                $data["access_time"] = strtotime($postdata["access_time"]); //通过时间，时间戳
                $data["car_type"]    = $postdata["plate_color"]; //车辆类型（蓝牌、黄牌，可为空）
                $data["in_type"]     = (isset($postdata["in_type"]) ? $postdata["in_type"] : "智能识别"); //进场类型（自动识别等，可为空）
                $data["order_id"]    = $postdata["cario_id"]; //停车场的订单号
                $data["empty_plot"]  = $postdata["parking_spaceNum"]; //空闲车位（可为空）
                $data["port_id"]     = $postdata["port_id"]; //通道ID
                $data["plate_type"]  = $postdata["plate_type"]; //临时车
                break;
            case 'kede':
                //"key":"ehgr25s3",
                //"carNo":"粤G74897",
                //"orderNo":"20180907103800476-G74897",
                //"enterTime":"2018-09-07 10:38:00",
                //"carType":"3651",
                //"gateName":"入口车道21",
                //"operatorName":"管理员",
                //"reserveOrderNo":"",
                //"imgUrl":""
                $data["park_id"]     = $postdata["key"]; //停车场ID
                $data["car_number"]  = strtoupper($postdata["carNo"]); //车牌号
                $data["access_time"] = strtotime($postdata["enterTime"]); //通过时间，时间戳
                $data["car_type"]    = (in_array($postdata["carType"], ["3651", "3650", "3649", "3648"]) ? "临时车" : $postdata["carType"]); //车辆类型，科德这里只是编码，哪个临时车?
                $data["in_type"]     = ""; //进场类型（自动识别等，可为空）
                $data["order_id"]    = $postdata["orderNo"]; //停车场的订单号
                $data["empty_plot"]  = ""; //空闲车位（可为空）
                $data["port_id"]     = "未完成"; //通道ID
                $data["plate_type"]  = "未完成"; //临时车
                break;
        }
        $rs["data"] = $data;
        return $rs;
    }
    /**
     * 进场车辆车牌修正
     * 整理成统一的数据格式
     * URL:http://xxx.com/api/carpark/callbacknotice/from_compay/epapi/apiname/updateincar
     */
    public function updateincar($from_compay, $postdata, $apiname)
    {
        //统一返回数据格式
        $data               = [];
        $data["apiname"]    = $apiname; //动作名称
        $data["park_id"]    = ""; //停车场ID
        $data["car_number"] = ""; //车牌号
        $data["order_id"]   = ""; //停车场的订单号
        //__统一返回数据格式
        $rs = ["code" => 1, "message" => "", "data" => $data];
        switch ($from_compay) {
            case 'epapi':
                //{"access_id":"161","access_time":"2018/7/9 14:39:38","access_time_out":"2018/7/9 15:24:55","amount_receivables":"4.600000","amount_spaid":"4.600000","cario_id":"104","park_id":"25404241023598608","park_name":"杭州专注科技停车场","parkingName":"智能车牌识别停车场","parking_spaceNum":"1138","plate_color":"蓝","plate_id":"浙AK2191","plate_state":"正常","plate_subtype":"","plate_type":"临时车","port_id":"4","signature":"aa2017bd57a68616e0e8e5e1be6500c34504d45a1bec052ffc4364e4a4f5cf69","timestamp":"1531121094","user_name":"系统管理员"}
                $data["park_id"]    = $postdata["park_id"]; //停车场ID
                $data["car_number"] = trim(strtoupper($postdata["plate_id"])); //车牌号
                $data["order_id"]   = $postdata["cario_id"]; //停车场的订单号
                break;
            case 'kede':
                //"key":"ehgr25s3",
                //"carNo":"粤G74897",
                //"orderNo":"20180907103800476-G74897",
                //"enterTime":"2018-09-07 10:38:00",
                //"carType":"3651",
                //"gateName":"入口车道21",
                //"operatorName":"管理员",
                //"reserveOrderNo":"",
                //"imgUrl":""
                $data["park_id"]    = $postdata["key"]; //停车场ID
                $data["car_number"] = strtoupper($postdata["carNo"]); //车牌号
                $data["order_id"]   = $postdata["orderNo"]; //停车场的订单号
                break;
        }
        $rs["data"] = $data;
        return $rs;
    }
    /**
     * 车辆入场
     * 整理成统一的数据格式
     * URL:http://xxx.com/api/carpark/callbacknotice/from_compay/epapi/apiname/entercar
     */
    public function outcar($from_compay, $postdata, $apiname)
    {
        //统一返回数据格式
        $data                  = [];
        $data["apiname"]       = $apiname; //动作名称
        $data["park_id"]       = ""; //停车场ID
        $data["car_number"]    = ""; //车牌号
        $data["access_time"]   = ""; //通过时间，时间戳
        $data["car_type"]      = ""; //车辆类型（大车、小车，可为空）
        $data["out_type"]      = ""; //进场类型（自动识别等，可为空）
        $data["order_id"]      = ""; //停车场的订单号
        $data["empty_plot"]    = ""; //空闲车位（可为空）
        $data["port_id"]       = ""; //通道ID
        $data["plate_type"]    = ""; //临时车
        $data["total_amount"]  = ""; //停车场费
        $data["operator_name"] = ""; //道口操作者名字
        //__统一返回数据格式
        $rs = ["code" => 1, "message" => "", "data" => $data];
        switch ($from_compay) {
            case 'epapi':
                //{"access_id":"161","access_time":"2018/7/9 14:39:38","access_time_out":"2018/7/9 15:24:55","amount_receivables":"4.600000","amount_spaid":"4.600000","cario_id":"104","park_id":"25404241023598608","park_name":"杭州专注科技停车场","parkingName":"智能车牌识别停车场","parking_spaceNum":"1138","plate_color":"蓝","plate_id":"浙AK2191","plate_state":"正常","plate_subtype":"","plate_type":"临时车","port_id":"4","signature":"aa2017bd57a68616e0e8e5e1be6500c34504d45a1bec052ffc4364e4a4f5cf69","timestamp":"1531121094","user_name":"系统管理员"}
                $data["park_id"]       = $postdata["park_id"]; //停车场ID
                $data["car_number"]    = trim(strtoupper($postdata["plate_id"])); //车牌号
                $data["access_time"]   = strtotime($postdata["access_time_out"]); //通过时间，时间戳
                $data["car_type"]      = $postdata["plate_color"]; //车辆类型（蓝牌、黄牌，可为空）
                $data["out_type"]      = (isset($postdata["out_type"]) ? $postdata["out_type"] : "智能识别"); //进场类型（自动识别等，可为空）
                $data["order_id"]      = $postdata["cario_id"]; //停车场的订单号
                $data["empty_plot"]    = $postdata["parking_spaceNum"]; //空闲车位（可为空）
                $data["port_id"]       = $postdata["port_id"]; //通道ID
                $data["plate_type"]    = $postdata["plate_type"]; //临时车
                $data["total_amount"]  = $postdata["amount_receivables"]; //停车场费
                $data["operator_name"] = $postdata["user_name"]; //道口操作者名字
                break;
            case 'kede':
                //"key":"ehgr25s3",
                //"carNo":"粤G74897",
                //"orderNo":"20180907103800476-G74897",
                //"enterTime":"2018-09-07 10:38:00",
                //"carType":"3651",
                //"gateName":"入口车道21",
                //"operatorName":"管理员",
                //"reserveOrderNo":"",
                //"imgUrl":""
                $data["park_id"]       = $postdata["key"]; //停车场ID
                $data["car_number"]    = strtoupper($postdata["carNo"]); //车牌号
                $data["access_time"]   = strtotime($postdata["outTime"]); //通过时间，时间戳
                $data["car_type"]      = (in_array($postdata["carType"], ["3651", "3650", "3649", "3648"]) ? "临时车" : $postdata["carType"]);
                $data["out_type"]      = ""; //进场类型（自动识别等，可为空）
                $data["order_id"]      = $postdata["orderNo"]; //停车场的订单号
                $data["empty_plot"]    = ""; //空闲车位（可为空）
                $data["port_id"]       = "未完成"; //通道ID
                $data["plate_type"]    = "未完成"; //临时车
                $data["total_amount"]  = $postdata["totalAmount"]; //停车场费
                $data["operator_name"] = $postdata["operatorName"]; //道口操作者名字
                break;
        }
        $rs["data"] = $data;
        return $rs;
    }

    /**
     * 本地数据库同步到网上
     * $param["uuid"]//停车场唯一ID
     * $param["xxxxx"]//停车订单号 同一车场下唯一(第三方停车场的ID)
     * $param["xxxxxrrrr"]//车牌号
     * $param["from_compay"]//停车场类型,"epapi"、"kede"
     */
    public function dblocal2service($from_compay, $postdata, $apiname)
    {

        //统一返回数据格式
        $data             = [];
        $data["apiname"]  = $apiname; //动作名称
        $data["postdata"] = $postdata;
        return ["code" => 1, "message" => "", "data" => $data];
    }

    /**
     * 功能模块说明
     * $param["uuid"]//停车场唯一ID
     * $param["xxxxx"]//停车订单号 同一车场下唯一(第三方停车场的ID)
     * $param["xxxxxrrrr"]//车牌号
     * $param["from_compay"]//停车场类型,"epapi"、"kede"
     */
    public function demoGetPushFun($from_compay, $postdata, $apiname)
    {
        //统一返回数据格式
        $data          = [];
        $data["xxxx"]  = 0; //------
        $data["xxxx2"] = 0; //------
        //__统一返回数据格式
        switch (strtolower($param["from_compay"])) {
            //设备来源公司
            case 'epapi': //易泊
                $sendData             = [];
                $sendData["park_id"]  = $param["uuid"];
                $sendData["plate_id"] = strtoupper($param["car_number"]);
                $sendData["order_id"] = $param["order_id"];

                // $rsArray              = model("epapi")->sendData("GetPayInfo", $sendData);
                // if (ceil($rsArray["ret_code"]) != 1) {
                //     //易泊没有查询到
                //     $rs["code"]    = 0;
                //     $rs["message"] = $rsArray["error_des"];
                // } else {
                // }
                
                break;
            case "kede": //科德
                $kede                = new \Kede\Api();
                $sendData            = [];
                $sendData["parkKey"] = $param["uuid"];
                $sendData["xxxxxx"]  = $param["xxxxxx"];
                $rsArray             = $kede->xxxxxxx($sendData);
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
