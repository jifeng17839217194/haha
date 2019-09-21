<?php
namespace app\common\model;

use think\Model;

/**
 * 易泊API的集合
 */
class Epapi extends Model
{
    protected $type = [
        //'sysconfig' => 'object',
        //'user_last_logintime' => 'timestamp',
    ];
    protected $autoWriteTimestamp = false;

    //关联
    public function profile()
    {
        //return $this->belongsTo('Province','city_province_id')->field('province_name');
    }

    //接口数据验证
    public function verifyPostData($token="")
    {
        $postdata = isset($_POST)?$_POST:[];
        if(isset($postdata["sign"]))
        {
            $guest_sign = strtolower($postdata["sign"]);
            $server_sign = strtolower(publicRequestjiami($postdata,$token));

            if($guest_sign!=$server_sign)
            {
                return ["code"=>0,"message"=>"未授权或登入已过期，尝试重新登入","data"=>$server_sign];
            }
            else
            {
                return ["code"=>1,"message"=>"","data"=>""];
            }
        }
        else
        {
            return ["code"=>0,"message"=>"参数必需含有sign(加密签名)","data"=>""];
        }
    }

    /**
     * [sendData description]
     * @param  [type] $apiname   [description]
     * 获取停车的进出口的 getPortId "{\"park_id\":\"" .$park_id. "\"}";
     * 
     * 播放声音 LED_playVoice "{\"park_id\":\"" . $park_id . "\",\"port_id\":\"". $port_id."\",\"voice_text\":\"专注科技测试的声音2\"}"
     * 
     * 播放文字 LED_display "{\"park_id\":\"" . $park_id . "\",\"port_id\":\"".$port_id."\",\"text\":[{\"row_index\":\"1\",\"row_text\":\"第一行\",\"row_color\":\"\"},{\"row_index\":\"2\",\"row_text\":\"第二行\",\"row_color\":\"\"}]}"
     * 
     * 抬杠 OpenDoor "{\"park_id\":\"" . $park_id . "\",\"port_id\":\"".$port_id."\"}"
     * 
     * 获取停车位 GetPlaceCount  "{\"park_id\":\"" . $park_id . "\"}"
     * 
     * 提前获取缴费金额 GetPayInfo  "{\"park_id\":\"" . $park_id . "\",\"plate_id\":\"浙ak219m\",\"order_id\":\"\"}"
     * 
     * 预支付 payok "{\"park_id\":\"" .$park_id. "\",\"port_id\":\".$port_id.\",\"plate_id\":\"浙ak219m\",\"order_id\":\"\",\"cario_id\":\"\",\"pay_time\":\"".date("Y-m-d",time())."\",\"pay_amount\":\"100.20\",\"pay_id\":\"\",\"pay_finish_type\":\"1\",\"accept_account_id\":\"\",\"pay_type\":\"1\"}"
     * 
     * 获取车辆进出记录 findCarIoInfoIn "{\"park_id\":\"" .$park_id. "\",\"cario_id\":\"\",\"plate_id\":\"浙ak219m\",\"time_begin\":\"\",\"time_end\":\"\"}"
     * 
     * 添加内部车 addInnerCar "{\"park_id\":\"" .$park_id. "\",\"plate_id\":\"临ABC".mt_rand(111,999)."\",\"isinout\":\"场内\",\"plate_color\":\"黄牌\",\"plate_type\":\"临时车\",\"plate_state\":\"正常\",\"plate_subtype\":\"\",\"free_time\":\"1\",\"begin_date\":\"\",\"end_date\":\"\",\"carown_name\":\"\",\"carown_sex\":\"\",\"carown_phone\":\"135".mt_rand(1111,9999)."\",\"carown_cardtype\":\"\",\"carown_cardnum\":\"\",\"carown_birsday\":\"\",\"carown_address\":\"\",\"charg_scheme\":\"\",\"del_record\":\"0\"}"
     *
     *
     * 
     * @param  [type] $paramJson [description]
     * @return [type]            [description]
     */
    public function sendData($apiname, $paramJsonArray, $synchronous = "no")
    {
        trace("硬件通信","debug");
        trace("动作：".$apiname."，参数↓↓","debug");
        trace($paramJsonArray,"debug");
        $sendData                = [];
        $sendData["apiname"]     = $apiname;
        $sendData["param"]       = json_encode($paramJsonArray); //"{\"park_id\":\"" .$park_id. "\"}";这里转成字符串，在C#验证签名时比较方便
        $sendData["synchronous"] = ($synchronous == "yes" ? "yes" : "no");
        $sendData["timestamp"]   = time();
        //数据签名
        $server_sign      = strtolower(publicRequestjiami($sendData, config("carpark_token")));
        $sendData["sign"] = $server_sign;
        $client           = $this->swooleclient();
        $client->send(json_encode($sendData)); //不能加“JSON_UNESCAPED_UNICODE”,停车LED的识别中文出错
        $rscode = $client->recv();
        $rscode = str_replace("\n\r\n\t\t\r\n", "", $rscode);
        $rs = json_decode($rscode, 1);
        trace("硬件返回：","debug");
        trace($rs,"debug");
        return $rs;
    }

    //服务器要先安装 swoole
    public function swooleclient()
    {
        $client = new \swoole_client(SWOOLE_SOCK_TCP);
        //包结束符检测
        $client->set(array(
            'open_eof_check'     => true,
            'package_eof'        => "\r\n\t\t\r\n", //约定包的结束符
            'package_max_length' => 1024 * 1024 * 2,
        ));
        if (!$client->connect('47.97.190.139', 39050, -1)) {
        //if (!$client->connect('116.62.172.60', 30001, -1)) {
        //链接到中转服务器
            $error = $client->errCode;
            trace("swoole_client connect 47.97.190.139 $error:", "error");
            exit("<br />connect failed. Error: {" . $error . "}<br />");
        }
        $client->recv();
        return $client;
    }

    /**
     * 获取中转服务器的系统状态
     * @return [type] [description]
     */
    /*public function getStatic()
    {
        $client = new \swoole_client(SWOOLE_SOCK_TCP);
        //包结束符检测
        $client->set(array(
            'open_eof_check'     => true,
            'package_eof'        => "\r\n\t\t\r\n", //约定包的结束符
            'package_max_length' => 1024 * 1024 * 2,
        ));
        if (!$client->connect('47.97.190.139', 39050, -1)) {
            //链接到中转服务器
            return ["code" => 0, "message" => $client->errCode, "data" => ""];
        } else {
            return ["code" => 1, "message" => "", "data" => ""];
        }
    }*/
}
