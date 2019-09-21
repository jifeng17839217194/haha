<?php
namespace app\common\model;

use JPush\Client as JPush;
use think\Model;

class Push extends Model
{
    protected $type = [
        //'sysconfig' => 'object',
        //'user_last_logintime' => 'timestamp',
    ];
    protected $autoWriteTimestamp = false;

    /**
     * //消息推送,采用"极光"
     * CJH 2016-12-26
    类型1，点击消息框，跳到某个页面：
    $pushdata = array(
    "title" => "标题",
    "text" => "查看详情",
    "parpam" => array(
    "needlogin" => 1,
    "type" => "openwin",
    "pagename" => "somepagename",
    "pageparam" => array("id" =>123)
    )
    );

    类型2，无提示的自定义消息，负责使用事件，后台传数据,支持多事件：
    $pushdata = array(
    "title" => "标题",
    "text" => "查看详情",
    "parpam" => array(
    "needlogin" => 1,
    "type" => "sendevent",
    "event" => array(
    array(
    "eventname" => "baoxiustatuschange",
    "eventparam" => array(
    "id" => 123
    ),
    ),
    array(
    "eventname" => "baoxiustatuschange2",
    "eventparam" => array(
    "id" => 123
    ),
    )
    )
    )
    );

    类型3，结合（类型1、类型2）：
    $pushdata = array(
    "title" => "标题",
    "text" => "查看详情",
    "parpam" => array(
    "needlogin" => 1,
    "type" => "openwinsendevent",
    "pagename" => "somepagename",
    "pageparam" => array("id" =>123),
    "event" => array(
    array(
    "eventname" => "baoxiustatuschange",
    "eventparam" => array(
    "id" => 123
    ),
    )
    )
    )
    );

     */

    /**
     * [push2user 给指定的用户发送推送]
     * @param  [type] $user_id      [接收者的user_id]
     * @param  [type] $pushdata     [消息数组]
     * @param  string $userOrsellor ["用户端or商家端":seller，user]
     * @return [type]               [no return]
     */
    public function push2user($user_id, $pushdata, $userOrsellor = "user")
    {
        $fields     = $userOrsellor == "user" ? "user_pushtoken" : "user_pushtoken_seller";
        $tokenArray = model("user")->where("user_id", "in", $user_id)->column($fields);
        //dump($tokenArray);die();
        if (count($tokenArray) > 0) {
            $this->push($tokenArray, $pushdata, $userOrsellor);
        }

    }

    /**
     * [goEasy 的web 推送]
     * @param  [type] $user_token [description]
     * @param  [type] $dataArray  [description]
     * @return [type]             [description]
     */
    public function goEasyPush($user_token,$dataArray)
    {
        $data["appkey"]=config("goEasy_Common_key");
        $data["channel"]=$user_token;
        $data["content"]=json_encode($dataArray,JSON_UNESCAPED_UNICODE);
        innerHttpsPost("cmd/innerrequest/goeasypush",$data);
    }

    /**
     * 发起异步的极光推送
     * @param  string $value [description]
     * @return [type]        [description]
     */
    public function dopush($user_pushtoken,$pushdata)
    {
        $data["registrationid"]=$user_pushtoken;
        $data["parpam"]=json_encode($pushdata,JSON_UNESCAPED_UNICODE);
        innerHttpsPost("cmd/innerrequest/jiguangpush",$data);
    }

    // $pushdata = array(
    //     "title"     => "",
    //     "text"      => "",
    //     "needlogin" => 0,
    //     "type"      => "sendevent",
    //     "pageparam" => array("itemid" => 35, "itemid2" => 356),
    //     "event"     => array(
    //         array(
    //             "eventname"  => "live_" . $allGetData["action"],
    //             "eventparam" => array(
    //                 "live_id" => $live_id,
    //             ),
    //         ),
    //     ),
    // );

    //发送推送，采用自定义（透传的方式） //适合APP已经启动的情况下发送各种内容
    //2017-5-23 17:56:33，新版方式，app全部只接收透传消息(缺点：android下，app退出了(推送进程保留了)，有新透传时通知栏只显示“你有新的消息”)
    public function pushDiyMsg($RegistrationId, $parpam = array("title" => "标题", "text" => "查看详情", "needlogin" => 0, "type" => "openwin", "pagename" => "somepagename", "pageparam" => array("itemid" => 35, "itemid2" => 356)), $userOrsellor = "user")
    {

        $ajpush_AppKey   = config("ajpush_AppKey");
        $ajpush_Secret   = config("ajpush_Secret");
        $apns_production = config("apns_production");
        if ($userOrsellor == "seller") {
            $ajpush_AppKey   = config("ajpush_AppKey_seller");
            $ajpush_Secret   = config("ajpush_Secret_seller");
            $apns_production = config("apns_production_seller");
        }

        $client = new JPush($ajpush_AppKey, $ajpush_Secret);

        $client = $client->push();

        if ($RegistrationId == "all") {

            $client = $client->addAllAudience(); //所有接受者

        } else {

            $client = $client->addRegistrationId(is_array($RegistrationId) ? $RegistrationId : array($RegistrationId)); //按注册ID推送， http://docs.jiguang.cn/server/rest_api_v3_push/
        }

        $client = $client->options(
            array(
                "time_to_live"    => 60*2,//离线2分钟
                "apns_production" => $apns_production,
            )
        );

        $client = $client->setPlatform(array('android', 'ios')); //android只推透传
        $client = $client->message("", array(
            'title'  => $parpam["text"],
            'content_type' => 'text',
            'extras' => array_merge($parpam,["onlyandroid"=>"yes"]),//ios客户端屏蔽标示
        )); //透传

        //$client = $client->addAndroidNotification('Hi, android notification', 'notification title', 1, array("key1"=>"value1", "key2"=>"value2"));
        $client = $client->iosNotification( //ios只推通知（自己在APP端屏蔽掉透传）
            $parpam["title"] . "\r\n" . $parpam["text"], array(
                'sound'    => 'sound.caf',
                'badge'    => '0',
                // 'content-available' => true,
                // 'mutable-content' => true,
                'category' => 'jiguang',
                'extras'   => $parpam,
            )
        );


        $result = $client->send();
        return $result;

    }

    //适合发送通知、广告、营销(适合APP未启动、或需要单向通信)，需要用户点击才能进行下一步
    public function push($RegistrationId, $parpam = array("title" => "标题", "text" => "查看详情", "needlogin" => 0, "type" => "openwin", "pagename" => "somepagename", "pageparam" => array("itemid" => 35, "itemid2" => 356)), $userOrsellor = "user")
    {

        $ajpush_AppKey   = config("ajpush_AppKey");
        $ajpush_Secret   = config("ajpush_Secret");
        $apns_production = config("apns_production");
        if ($userOrsellor == "seller") {
            $ajpush_AppKey   = config("ajpush_AppKey_seller");
            $ajpush_Secret   = config("ajpush_Secret_seller");
            $apns_production = config("apns_production_seller");
        }

        $client = new JPush($ajpush_AppKey, $ajpush_Secret);

        $client = $client->push();

        if ($RegistrationId == "all") {

            $client = $client->addAllAudience(); //所有接受者

        } else {

            $client = $client->addRegistrationId(is_array($RegistrationId) ? $RegistrationId : array($RegistrationId)); //按注册ID推送， http://docs.jiguang.cn/server/rest_api_v3_push/
        }

        $client = $client->options(
            array(
                "time_to_live"    => 60*2,//离线2分钟
                "apns_production" => $apns_production,
            )
        );

        $client = $client->setPlatform(array('android', 'ios')); //android只推透传
        /*
        $client = $client->message("", array(
            'title'  => $parpam["text"],
            'content_type' => 'text',
            'extras' => array_merge($parpam,["onlyandroid"=>"yes"]),//ios客户端屏蔽标示
        )); //透传*/

        $client = $client->addAndroidNotification($parpam["text"], $parpam["title"], 0, $parpam);
        $client = $client->iosNotification(
            $parpam["title"] . "\r\n" . $parpam["text"], array(
                'sound'    => 'sound.caf',
                'badge'    => '0',
                // 'content-available' => true,
                // 'mutable-content' => true,
                'category' => 'jiguang',
                'extras'   => $parpam,
            )
        );


        $result = $client->send();
        return $result;
    }

}
