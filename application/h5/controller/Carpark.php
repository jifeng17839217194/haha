<?php
namespace app\h5\controller;

use think\Controller;
use think\Log;

class Carpark extends Controller
{
    //消费者_输入停车牌(通用)
    public function parkingfeeinput()
    {
        if (!cookie("buyer_open_id")) {
            echo "当前页面不可直接访问";die();
        }
        //$user_id = input("user_id"); //收费员的ID
        return view();
    }
    public function _empty()
    {
        return view();
    }

    //**无牌车入场
    //从前面一个获取支付宝、微信唯一ID跳过来的
    public function parkingNoNumberInView()
    {
        $user_id = input("user_id");
        if (!cookie("auto_car_number") || !$user_id) {
            echo "不可直接访问";die;
        }
        $parkingOne = db("parking")->join("__STORE__", "store_id=parking_store_id")->join("__PARKING_CHANNEL__", "parking_channel_parking_id=parking_id")->where(["parking_channel_user_id" => $user_id])->find();
        $this->assign("parkingOne", $parkingOne);
        return view();
    }

    //**无牌车入场 提交
    public function parkingNoNumberInPost()
    {
        $user_id = input("user_id");
        if (!cookie("auto_car_number") || !$user_id) {
            echo "不可直接访问";die;
        }
        $parkingOne = db("parking")->join("__STORE__", "store_id=parking_store_id")->join("__PARKING_CHANNEL__", "parking_channel_parking_id=parking_id")->where(["parking_channel_user_id" => $user_id])->find();
        //判断该车有没有在场内
        if (db("parking_record")->where(["parking_record_car_number" => cookie("auto_car_number"), "parking_record_in_time" => ["gt", 0], "parking_record_out_time" => 0])->field("parking_record_id")->find()) {
            return ["code" => 0, "message" => "您已经在场内", "data" => ""];
        } else {
            //，未完成，没时间做了，新功能先放一放 2019-1-17 14:03:43
            //$sendData                = [];
            // $sendData["uuid"]        = $parkingOne["parking_uuid"];
            // $sendData["car_number"]  = cookie("auto_car_number");
            // $sendData["from_compay"] = "epapi";
            // $Parkcommon              = new \Parkcommon\Apiget();
            // $rsArray                 = $Parkcommon->getPort($sendData);
        }

    }

    //支付宝、微信显示停车费
    public function parkingfee()
    {
        $user_id       = input("user_id", ""); //url里直接传递了收费员信息(出入口都可以),如果没有，就从支付宝里获取参数
        $car_number    = input("car_number", "", "urldecode"); //url里直接传递了车牌号信息,如果没有，就从支付宝里获取参数
        $buyer_open_id = cookie("buyer_open_id"); ////这个行不一定有值
        //浏览器限制
        $userAgent = strtolower(request()->header("user-agent"));
        //$userAgent ="micromessenger";
        //判断微信or支付宝
        if (preg_match("/micromessenger/", $userAgent)) //微信
        {
            if (!$car_number) {
                echo "<script>alert('车牌号不可为空');</script>";die(); //调试状态，先关闭
            }
            $user_id = $user_id ?: db("parking_record")->join("__PARKING_CHANNEL__", "parking_record_in_channel_id = parking_channel_id", "left")->where(["parking_record_car_number" => $car_number])->order("parking_record_id desc")->value("parking_channel_user_id");
            if (!$user_id) {echo "<script>alert('没有停车记录" . $user_id . "');window.history.back()</script>";die();}
            $channel = "wxpay";
        } else if (preg_match("/alipayclient/", $userAgent)) //1、来自支付宝扫码，2、来自支付宝APP车主停车服务
        {
            if (!$car_number && !$user_id) {
                $channel        = "alipay";
                $car_id         = input("get.car_id"); //支付宝用户车辆ID，系统唯一。（该参数会在停车平台用户点击查询缴费，跳转到ISV停车缴费查询页面时，从请求中传递）
                $ali_parking_id = input("get.parking_id"); //支付宝的停车场ID
                $auth_code      = input("get.auth_code"); //针对用户授权接口，获取用户相关数据时，用于标识用户授权关系。详见用户信息授权
                if (!($thisSameConfig = cache($ali_parking_id . "_" . $car_id))) {
                    $alipay = model("alipay");
                    //获取auth_token
                    $aop     = $alipay->requestBase(1);
                    $request = new \AlipaySystemOauthTokenRequest();
                    $request->setGrantType("authorization_code");
                    $request->setCode($auth_code);
                    $aopresult = $aop->execute($request);
                    if (isset($aopresult->error_response)) {
                        $error_response = json_decode(json_encode($aopresult->error_response, JSON_UNESCAPED_UNICODE), 1);
                        echo "<script>alert('" . json_encode($aopresult->error_response, JSON_UNESCAPED_UNICODE) . "');</script>";die();
                    } else {
                        $rsJsonArray = json_decode(json_encode($aopresult->alipay_system_oauth_token_response, JSON_UNESCAPED_UNICODE), 1);
                        cache(($ali_parking_id . "_" . $car_id . "_buyer_id"), $rsJsonArray["user_id"]);
                        $auth_token = $rsJsonArray["access_token"];
                    }
                    //__获取auth_token
                    //$shop_alipay_app_auth_token = db("shop")->join("__STORE__", "shop_id=store_shop_id")->join("__PARKING__", "store_id=parking_store_id")->where(["parking_ali_parking_id" => $ali_parking_id])->value("shop_alipay_app_auth_token");
                    $paramArray               = [];
                    $paramArray["car_id"]     = $car_id;
                    $paramArray["auth_token"] = $auth_token; //以商户的身份才获得数据的
                    $setRs                    = model("parking")->push2aliyun("parking.vehicle.query", $paramArray);
                    if ($setRs["code"] == 0) {
                        echo "<script>alert('" . $setRs["message"] . "');</script>";die(); //
                    }
                    $car_number = $setRs["data"]["car_number"]; //提取车牌
                    //根据车牌，获取收费员的数据
                    $user_id = db("parking_record")->join("__PARKING_CHANNEL__", "parking_record_in_channel_id = parking_channel_id", "left")->where(["parking_record_car_number" => $car_number])->order("parking_record_id desc")->value("parking_channel_user_id");
                    //echo $user_id;die();
                    //dump($_GET);
                    cache(($ali_parking_id . "_" . $car_id . "_car_number"), $car_number);
                    cache(($ali_parking_id . "_" . $car_id . "_user_id"), $user_id);
                    cache($ali_parking_id . "_" . $car_id, $ali_parking_id);
                }
                $car_number    = cache(($ali_parking_id . "_" . $car_id . "_car_number"));
                $user_id       = cache(($ali_parking_id . "_" . $car_id . "_user_id"));
                $buyer_open_id = cache($ali_parking_id . "_" . $car_id . "_buyer_id");
            }
        } else //未知
        {
            echo "<script>alert('请用支付宝或微信扫描');</script>";die(); //调试状态，先关闭
        }
        //__浏览器限制
        //获取停车的信息
        $parkingOne = db("parking")->join("__STORE__", "store_id=parking_store_id")->join("__PARKING_CHANNEL__", "parking_channel_parking_id=parking_id")->where(["parking_channel_user_id" => $user_id])->find();
        $this->assign("store_name", $parkingOne["store_name"]);
        $this->assign("store_mobile", $parkingOne["store_mobile"]);
        $this->assign("car_number", $car_number);
        $this->assign("user_id", $user_id);
        $this->assign("buyer_open_id", $buyer_open_id); //这个行不一定有值
        return view();
    }
    /*
    异步处理一些数据
     */
    public function parkingfeeasynchronous()
    {
        switch (input("actionname")) {
            case 'getfee': //查询停车费
                $get_price_rs = model("parking_record")->getPrice(["car_number" => input("car_number"), "user_id" => input("user_id")]); //加上停车场限制
                if ($get_price_rs["code"] == 1) {
                    $rsData = [
                        "access_time_in"    => date("Y-m-d H:i:s", $get_price_rs["data"]["parking_record_in_time"]),
                        "access_time_out"   => $get_price_rs["data"]["parking_record_out_time"],
                        "total_time"        => (($get_price_rs["data"]["parking_record_out_time"] > 0) ? time2second($get_price_rs["data"]["parking_record_out_time"] - $get_price_rs["data"]["parking_record_in_time"]) : (time2second(($get_price_rs["data"]["parking_record_get_price_last_time"] ?: time()) - $get_price_rs["data"]["parking_record_in_time"]))),
                        "pay_amount"        => $get_price_rs["data"]["parking_record_total"],
                        "parking_record_id" => $get_price_rs["data"]["parking_record_id"],
                        "pay_state"         => $get_price_rs["data"]["parking_record_pay_state"],
                        "real_pay_total"    => $get_price_rs["data"]["parking_record_real_pay_total"],
                        "reduce_amount"     => $get_price_rs["data"]["parking_record_reduce_amount"],
                    ];
                    Log::write('code=1的数据:'.json_encode($rsData,JSON_UNESCAPED_UNICODE)."\G",'log');
                    return ["code" => 1, "message" => "", "data" => $rsData];
                } else {
                    Log::write('code=0的数据:'.json_encode($get_price_rs,JSON_UNESCAPED_UNICODE)."\G",'log');
                    return $get_price_rs;
                }
                break;
            default:
                # code...
                break;
        }
    }
}
