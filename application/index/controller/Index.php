<?php
namespace app\index\controller;

use think\Controller;

class Index extends Controller
{
    //test用
    public function index()
    {
        //获取声音文件
        //dump(model("baidu")->getcashsoundfile(123456));

        //Url::root("/");
        //echo request()->domain() .url("api/notifyurl/index?channel=alipay");
        // echo innerHttpsPost("cmd/innerrequest/addscheduledtasks",[
        //     "scheduled_tasks_title"         => "微信支付结果查询",
        //     "scheduled_tasks_start_time"    => time(), //马上开始
        //     "scheduled_tasks_end_time"      => time() + 5 * 24, //只查询120秒
        //     "scheduled_tasks_time_interval" => json_encode([5],JSON_UNESCAPED_UNICODE), //间隔5秒
        //     "scheduled_tasks_name"          => "check_wxpay_order_status", //检测微信订单的状态
        //     "scheduled_tasks_param"         => json_encode(["out_trade_no" => getMillisecond()],JSON_UNESCAPED_UNICODE),
        // ]);

        //$rs=model("wxpay")->reverseOrder(["out_trade_no" => 'ZZ15057146131167', "payment" => ['sub_merchant_id' => 1469820702]]);
        //dump($rs);

        //dump(model("pay")->tradeQueryRequest("ZZ15057240111178"));
        //define("AOP_SDK_WORK_DIR","1");

        // $pushdata           = model("order")->where(["order_id" => 818])->find();
        // $Postdata["parpam"] = json_encode($pushdata, JSON_UNESCAPED_UNICODE);
        // innerHttpsPost("cmd/innerrequest/push2xcxserver", $Postdata);

        return view();
    }

    public function price()
    {
        $rsArray = model("parking_record")->getPrice(["car_number" => "浙AD750C","user_id"=>10000163]); //加上停车场限制
        dump($rsArray);
    }

    public function client()
    {
        //model("parking_record")->addMonthCar(52);
        // $epapi=model("epapi");
        // $rsArray = $epapi->sendData("findCarIoInfoIn", array(
        //    'park_id'  => '25404241023598620',
        //    'plate_id' => '甘A776R6'
        //    ),"no");

        // dump($rsArray);
        

        //$rsArray = model("parking_record")->getPrice(["car_number" => "浙C2K91M","user_id"=>10000163]); //加上停车场限制
        //dump($rsArray);
        /*$sendData            = [];
        $sendData["park_id"] = "25404241023598608";
        $rsArray             = $epapi->sendData("getPortId", $sendData,"yes");

        dump($rsArray);

        $epapi->sendData("LED_display", [
            "park_id" => "25404241023598608",
            "port_id" => 4,
            "text"    => [
                ["row_index" => "1", "row_text" => "支付成功", "row_color" => ""],
                ["row_index" => "2", "row_text" => "浙AU8600", "row_color" => ""],
                ["row_index" => "3", "row_text" =>"22元", "row_color" => ""],
                ["row_index" => "4", "row_text" => "一路顺风", "row_color" => ""],
            ],
        ], "yes"); //无需回调

        $epapi->sendData("LED_playVoice", [
            "park_id"    => "25404241023598608",
            "port_id"    => 4,
            "voice_text" => "支付宝更新停车场信息代扣8元,当前是系统测试,一路顺风,".date("Y年m月d日H:i:s",time()),
        ], "yes"); //无需回调*/

        // $paramArray                   = [];
        // $paramArray["ali_parking_id"] = "PI1531223350190704887";
        // $paramArray["car_number"]     = "浙ad750c";
        // $paramArray["in_time"]        = date("Y-m-d H:i:s", time());
        // $shopOne                      = db("shop")->where(["shop_id" => 10010])->find();
        // $paramArray["app_auth_token"] = $shopOne["shop_alipay_app_auth_token"];
        // $setRs                        = model("parking")->push2aliyun("parking.enterinfo.sync", $paramArray);
        // dump($setRs);
        /*$Epapi   = model("epapi");
        $park_id = "25404241023598608";
        $port_id = 1;

        $param=[];
        $param["park_id"]=$park_id;
        $param["port_id"]=$port_id;
        $param["voice_text"]="88";
        echo $Epapi->sendData("LED_playVoice", $param);

        $param=[];
        $param["park_id"]=$park_id;
        $param["port_id"]=$port_id;
        echo $Epapi->sendData("OpenDoor", $param);

        echo $Epapi->sendData("GetPlaceCount", $param);*/

        // //给别人发消息

        // /*echo  model("parking_record")->pushtoclient(1,["code"=>1,"message"=>"","data"=>"no thing"]);
        // die();*/
        // //__给别人发消息

        // //先链接服务器
        // $client = new \swoole_client(SWOOLE_SOCK_TCP);
        // if (!$client->connect('127.0.0.1', config("swoole_port"), -1)) {
        //     exit("connect failed. Error: {$client->errCode}\n");
        // }
        // echo "链接服务器=>" . getMillisecond() . ":" . $client->recv() . "<br />";
        // //__先链接服务器

        // //初始化链接数据
        // $sendData             = [];
        // $sendData["apiname"]  = "appinitializes";
        // $sendData["park_id"]  = "tcc9001";
        // $sendData["local_id"] = "sys_ygj_01";
        // $sendData["channels"] = ['A1', 'A2', 'A3'];

        // $client->send($this->datasign($sendData));
        // echo "数据初始化=>" . ":" . $client->recv() . "<br />";
        // //__初始化链接数据

        //临时车入场
        /*$sendData                      = [];
        $sendData["apiname"]           = "carin";
        $sendData["car_number"]        = "浙AD".mt_rand(111,999)."C";
        $sendData["in_time"]           = time();
        $sendData["car_type"]          = "小车";
        $sendData["in_type"]           = "通道扫牌";
        $sendData["in_user_id"]        = "9001";
        $sendData["order_id"]          = time();
        $sendData["empty_plot"]        = mt_rand(99,999);
        $sendData["in_channel_id"]     = "A1";
        $sendData["worksite_id"]       = "worksite2";
        $sendData["work_station_uuid"] = "uuid12345656";
        $sendData["in_remark"]         = "没有备注";

        $client->send($this->datasign($sendData));
        echo "临时车入场=>" . ":" . $client->recv() . "<br />";*/
        //__临时车入场

        //临时车出场
        /*$sendData                      = [];
        $sendData["apiname"]           = "carout";
        $sendData["car_number"]        = "浙BD".mt_rand(111,999)."C";
        $sendData["in_time"]           = (time()-3600);
        $sendData["car_type"]          = "小车";
        $sendData["in_type"]           = "通道扫牌";
        $sendData["in_user_id"]        = "9001";
        $sendData["order_id"]          = getMillisecond();//date("YmdHi",time());
        $sendData["empty_plot"]        = mt_rand(99,999);
        $sendData["in_channel_id"]     = "A1";
        $sendData["worksite_id"]       = "worksite2";
        $sendData["work_station_uuid"] = "uuid12345656";
        $sendData["in_remark"]         = "没有备注";
        $sendData["out_time"]          = time();
        $sendData["duration"]          = 60;
        $sendData["out_type"]          = "通道扫牌";
        $sendData["pay_type"]          = "facepay"; //offlinepay&facepay
        $sendData["auth_code"]         = "134604038415311087";
        $sendData["out_channel_id"]    = "A2";
        $sendData["out_user_id"]       = "9002";
        $sendData["out_remark"]        = "出场没有备注";
        $sendData["total"]             = 0.01;

        $client->send($this->datasign($sendData));
        echo "临时车出场=>" . ":" . $client->recv() . "<br />";*/
        //__临时车入场

        //停车费查询结果推送
        /*$sendData             = [];
        $sendData["apiname"]  = "pushparkingfee";
        $sendData["order_id"] = "1529743776937";
        $sendData["query_time"] = time()-60;
        $sendData["duration"] = 58;
        $sendData["total"] = 5;

        $sendData["query_get_time"] = 5;

        $sendData["park_id"]  = "tcc9001";

        $client->send($this->datasign($sendData));
        echo "停车费查询结果推送=>" . ":" . $client->recv() . "<br />";*/
        //__停车费查询结果推送

        //无牌车入场

        //__无牌车入场

        //查询停车费用
        //echo json_encode(model("parking_record")->getparkingfee(189),JSON_UNESCAPED_UNICODE);

        //$client->close();
        return view();
    }

    public function datasign($dataArray = [])
    {
        return model("tcp")->datasign($dataArray);
    }

}
