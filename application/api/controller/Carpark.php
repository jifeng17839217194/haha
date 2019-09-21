<?php
namespace app\api\controller;

use think\Controller;

class Carpark extends Apibase
{
    public function _initialize()
    {
        parent::_initialize();
    }

    public function test()
    {
        //$Epapi = new \Parkcommon\Apiget();
        /*dump($Epapi->getRemainingSpace([
        "uuid"=>"25404241023598608",
        "from_compay"=>"epapi"
        ]));*/

        // $sendData                = [];
        // $sendData["uuid"]        = "48446939748C75F75BF68D7FC4DE1F74";
        // $sendData["from_compay"] = "epapi";
        // $Parkcommon              = new \Parkcommon\Apiget();
        // $rsArray                 = $Parkcommon->getPort($sendData);
        // print_r($rsArray);
        // dump(model("ParkingRecord")->addMonthCar(105));
    }

    /*public function noNumberIn()
    {
    return model("parking_record")->noNumberIn($_POST);
    }*/

    /**
     * 停车场主动上行推送接口
     * @param  string $value [description]
     * @return [type]        [description]
     * array (
    'apiname' => 'handOpenDoor',
    'param' => '{&quot;access_id&quot;:&quot;&quot;,&quot;cario_id&quot;:&quot;104&quot;,&quot;opendoor_time&quot;:&quot;2018-07-09 14:47:53&quot;,&quot;park_id&quot;:&quot;25404241023598608&quot;,&quot;park_name&quot;:&quot;杭州专注科技停车场&quot;,&quot;parkingName&quot;:&quot;智能车牌识别停车场&quot;,&quot;plate_id&quot;:&quot;&quot;,&quot;plate_state&quot;:&quot;&quot;,&quot;plate_subtype&quot;:&quot;&quot;,&quot;plate_type&quot;:&quot;&quot;,&quot;port_id&quot;:&quot;&quot;,&quot;port_type&quot;:&quot;出口&quot;,&quot;signature&quot;:&quot;857cacb06177d4177c0d1665b7d2e3fc20b21d838052dbc6914d07117b42cbe7&quot;,&quot;timestamp&quot;:&quot;1531118872&quot;}',
    'time' => '1531118872',
    'sign' => '6a90ec94a124a970f9d23395bb47cd42',
    )
     */

    public function callbacknotice()
    {
        $Parkcommon = new \Parkcommon\Apipush();
        $rs         = $Parkcommon->autoaction();//自动分析不同的来源
        if ($rs["code"] == 1) {
            $rs_data = $rs["data"];
            switch ($rs_data["apiname"]) {
                case 'entercar': //车辆入场
                    model("ParkingRecord")->carin($rs_data); // 车辆入场都中断掉
                    break;
                case 'updateincar': //更新入场车辆车牌号
                    model("ParkingRecord")->updateincar($rs_data);
                    break;
                case 'outcar': //车辆出场
                    model("ParkingRecord")->carout($rs_data);
                    break;
                case 'dblocal2service': //数据同步
                    model("EpapiDb")->dblocal2service($rs_data);
                    break;
                default:
                    trace($rs, "error");
                    trace("apiname未知(" . $rs_data["apiname"] . ")", "error");
                    return $rs;
                    break;
            }
        } else {
            trace($rs, "error");
            return $rs;
        }
    }

    public function callbacknoticeOld()
    {

        //做数据签名验证
        $apiname = input("apiname");
        $param   = input("param", "", null);
        $time    = input("time");
        $sign    = input("sign");

        $paramForSign   = urlencode(htmlspecialchars_decode($param));
        $_POST["param"] = $paramForSign; //从C#那里传递过来的参数不一致了，处理下，给sign用

        $yz = $this->verifyPostData(config("carpark_token"));
        if (!$yz["code"]) //签名没通过验证
        {
            trace("Carpark.php callbacknotice签名验证失败", "error");
            return;
        }

        $param_urldecode = htmlspecialchars_decode($param);
        $param_array     = json_decode($param_urldecode, 1);
        //{"access_id":"","cario_id":"104","opendoor_time":"2018-07-09 14:54:58","park_id":"25404241023598608","park_name":"杭州专注科技停车场","parkingName":"智能车牌识别停车场","plate_id":"","plate_state":"","plate_subtype":"","plate_type":"","port_id":"","port_type":"出口","signature":"409fc0f1b222ddb973658521a43ee806c29ad633fd70cfa03ae67663d537f7d7","timestamp":"1531119297"}

        //提取参数
        $parking_uuid = $param_array["park_id"];
        if ($park_one = db("parking")->where(["parking_uuid" => $parking_uuid])->find()) {
            switch ($apiname) {
                case 'inpark_end': //车辆进场回调函数类型
                    //{"access_id":"159","access_time":"2018-07-09 15:23:23","cario_id":"105","park_id":"25404241023598608","park_name":"杭州专注科技停车场","parkingName":"智能车牌识别停车场","parking_spaceNum":"1139","plate_color":"蓝","plate_id":"浙AK219M","plate_state":"正常","plate_subtype":"","plate_type":"临时车","port_id":"1","signature":"2d96882fe5f7aa15f4805fc43bf5b2085259541c09ab306757403a762a29b52e","timestamp":"1531121002"}
                    model("ParkingRecord")->carin($param_array);
                    break;
                case 'inpark_modify': //入场修改车牌号回调函数类型
                    //{"access_id":"159","cario_id":"105","opendoor_time":"2018-07-09 15:24:11","park_id":"25404241023598608","park_name":"杭州专注科技停车场","parkingName":"智能车牌识别停车场","plate_id":"浙AK219M","plate_state":"","plate_subtype":"","plate_type":"","port_id":"1","port_type":"进口","signature":"11aa4d37f29fb2fe8ccdfb83c439178327f25dec5a34fc9b11976ac8b16d325b","timestamp":"1531121050"}
                    //$param_array["in_type"]="入场修改车牌";
                    //model("ParkingRecord")->carin($param_array);

                    break;
                case 'outpark_end': //出场回调函数（检测到车牌就有回调的）

                    //{"access_id":"161","access_time":"2018/7/9 14:39:38","access_time_out":"2018/7/9 15:24:55","amount_receivables":"4.600000","amount_spaid":"4.600000","cario_id":"104","park_id":"25404241023598608","park_name":"杭州专注科技停车场","parkingName":"智能车牌识别停车场","parking_spaceNum":"1138","plate_color":"蓝","plate_id":"浙AK2191","plate_state":"正常","plate_subtype":"","plate_type":"临时车","port_id":"4","signature":"aa2017bd57a68616e0e8e5e1be6500c34504d45a1bec052ffc4364e4a4f5cf69","timestamp":"1531121094","user_name":"系统管理员"}
                    model("ParkingRecord")->carout($param_array);

                    break;
                case 'outpark_modify': //出场修改车牌号
                    //{"access_id":"174","cario_id":"112","park_id":"25404241023598608","park_name":"杭州专注科技停车场","parkingName":"智能车牌识别停车场","plate_id":"浙AK219M","plate_id_new":"京AK219M","port_id":"4","signature":"2fe86814dd2ecd37074608c6517f563c3d1bc1aa286dfef0442be0beb58083fb","timestamp":"1531124952"}

                    $param_array["out_type"] = "出场修改车牌";
                    //model("ParkingRecord")->carout($param_array);

                    break;
                case 'outpark_exception': //出场异常处理(未匹配到入场记录)

                    break;
                case 'outpark_exceptionproc': //出场异常手动处理(未匹配到入场记录)

                    break;
                case 'handOpenDoor': //手动抬杆(进场和出场)
                    //车牌的数据来自上次自动识别成功的值
                    //{"access_id":"161","cario_id":"104","opendoor_time":"2018-07-09 15:26:01","park_id":"25404241023598608","park_name":"杭州专注科技停车场","parkingName":"智能车牌识别停车场","plate_id":"浙AK2191","plate_state":"正常","plate_subtype":"","plate_type":"临时车","port_id":"4","port_type":"出口","signature":"773f066d5746926a5afd95f8806403d058571725fc8e95d2991751402916362e","timestamp":"1531121160"}
                    model("ParkingRecord")->handOpenDoor($param_array);
                    break;

                default:
                    trace("Carpark.php callbacknotice 未处理的apiname<" . $apiname . ">", "error");
                    break;
            }
        }

        //判断停车场是否是自己的

        //trace($param_urldecode, "debug");
    }

    /**
     * 车牌查询接口
     * @return [type] [description]
     */
    public function parkingvehiclequery()
    {

    }

}
