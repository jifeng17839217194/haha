<?php
namespace app\common\model;

use think\Model;

class Parking extends Model
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
    //向阿里云请求数据
    public function push2aliyun($actionname, $paramArray)
    {
        $alipay = model("alipay");
        switch ($actionname) {
            case "parking.config.set": //停车ISV系统配置接口
                //https://docs.open.alipay.com/api_19/alipay.eco.mycar.parking.config.set/
                $data                           = [];
                $data["merchant_name"]          = $paramArray["merchant_name"]; //
                $data["merchant_service_phone"] = $paramArray["merchant_service_phone"]; //
                $data["account_no"]             = $paramArray["account_no"]; //签约支付宝账号
                $data["interface_info_list"]    = [
                    [
                        "interface_name" => "alipay.eco.mycar.parking.userpage.query",
                        "interface_type" => "interface_page",
                        "interface_url"  => $paramArray["interface_url"], //SPI接口的调用地址url，协议必须为https，对整个url字符串必须进行UrlEncode编码。编码为UTF-8
                    ],
                ]; //
                $data["extend_params"] = ["sys_service_provider_id" => config("sys_service_provider_id")];
                $data["merchant_logo"] = "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABsAAAAbCAYAAACN1PRVAAACdUlEQVRIS71WS3LaQBB9raLs7CJxgUCVxTZwguAbkBvES0upCpzA5ATWRrAMOYHxCcINrGwtV8EJ0GQZh0ynZsxPaPSBSllLzXS//rx+04RX/OgVsVAZzB499Szi98xoA7ABCCJE8i8i8dm9rxJ0IZh9O7etN6svLNEH8ItAU4DFzjHZDO4BeJv4rlMGmAtmj+MuSdyB8JMJQ3HtzvKc2eN5Q1w3FyeB2eHjJ4vom2S+En5rUuak6nkmM5WRxfghmTvCb0VVHVW5lwJTPaKz1ZzBg/+Z0SaQFFg9jIdM6Cae2z2MVJfWonf7/6Xk+6LsVfBi0NwSKgXmjGLBhJ6JDPVRzAB/B5MmAgNtELrEfLf0W1eZ4MbzBvEqSjxXjYn+tmBqjog5SHy3Yaq/ApOEy/1AdNnP/8wYNBGeGxzaOWG8YKK+8C6mKTBVQoDspX+hZirzmcDUpZcg5U3itzqHRvXwKVBzufTdYQrMCeMpAdHmIGNoyEyDrdm79NwMszUHgHbiu2rwd2V0RvGMGLOjwUZxn4CeiVSHhNtGc0pmG5VhCx+NpMrLrErPmDkC0QuVlSATiJn7eTOZ2zPdaMjbxGs1cwkCDEDYqkqRXiofuWzUhyVzdkj9IonSJWZMjXOmDNcN/ZB47mVVNuYBmgiX1cbz1cLUBxWItGqTKk+JkjYiCvh3rZErV/tzc6rq2+Fj2yJ6MJXc+Hie+p6V2RW/1AylaQ9M+Fr8Umsy3ADo5Al5SkFMjdY7yNmqz6R2EE70DkLY7SCM9Q5CDjEC+VwL9nt06PO47Yq5vX5abDCE0lJJFG1UvWgUSjMrMz72vHJmxzo23f8H9keFK911akMAAAAASUVORK5CYII="; //停车场的Logo
                $resultCodeArray       = $alipay->requestSHH("AlipayEcoMycarParkingConfigSetRequest", $data, $paramArray["app_auth_token"]);
                //return $resultCodeArray;
                if ($resultCodeArray["code"] == 10000) {
                    return ["code" => 1, "message" => "成功", "data" => ""];
                } else {
                    return ["code" => 0, "message" => $actionname . (json_encode($resultCodeArray,JSON_UNESCAPED_UNICODE)), "data" => $data];
                }
                break;
            case 'parking.enterinfo.sync': //alipay.eco.mycar.parking.enterinfo.sync(车辆驶入接口)
                //https://docs.open.alipay.com/api_19/alipay.eco.mycar.parking.enterinfo.sync/
                $data                  = [];
                $data["parking_id"]    = $paramArray["ali_parking_id"]; //支付宝停车场ID ，系统唯一
                $data["car_number"]    = strtoupper($paramArray["car_number"]); //车牌号
                $data["in_time"]       = $paramArray["in_time"]; //格式"YYYY-MM-DD HH:mm:ss"，24小时制
                $data["extend_params"] = ["sys_service_provider_id" => config("sys_service_provider_id")];
                //$resultCodeArray  = $alipay->request("AlipayEcoMycarParkingEnterinfoSyncRequest", $data, $paramArray["app_auth_token"]);
                $resultCodeArray = $alipay->requestSHH("AlipayEcoMycarParkingEnterinfoSyncRequest", $data, $paramArray["app_auth_token"]);
                //$resultCodeArray2 = $alipay->requestSHH("AlipayEcoMycarParkingEnterinfoSyncRequest", $data); //兼容2.0停车
                trace("alipay.eco.mycar.parking.enterinfo.sync(车辆驶入接口)", "debug");
                trace($data, "debug");
                if ($resultCodeArray["code"] == 10000) {

                    trace($resultCodeArray, "debug");
                    return ["code" => 1, "message" => "成功", "data" => ""];
                } else {
                    trace("↓↓".$actionname, "error");
                    trace($paramArray, "error");
                    trace($resultCodeArray, "error");
                    return ["code" => 0, "message" => $actionname . (isset($resultCodeArray["sub_msg"]) ? $resultCodeArray["sub_msg"] : $resultCodeArray["msg"]), "data" => $data];
                }
                break;
            case 'parking.exitinfo.sync': //alipay.eco.mycar.parking.exitinfo.sync(车辆驶出接口)
                $data                  = [];
                $data["parking_id"]    = $paramArray["ali_parking_id"]; //支付宝停车场ID ，系统唯一
                $data["car_number"]    = strtoupper($paramArray["car_number"]); //车牌号
                $data["out_time"]      = $paramArray["out_time"]; //格式"YYYY-MM-DD HH:mm:ss"，24小时制
                $data["extend_params"] = ["sys_service_provider_id" => config("sys_service_provider_id")];
                //$resultCodeArray       = $alipay->request("AlipayEcoMycarParkingExitinfoSyncRequest", $data, $paramArray["app_auth_token"]);
                $resultCodeArray = $alipay->requestSHH("AlipayEcoMycarParkingExitinfoSyncRequest", $data, $paramArray["app_auth_token"]);
                //$resultCodeArray2      = $alipay->requestSHH("AlipayEcoMycarParkingExitinfoSyncRequest", $data); //兼容2.0停车
                trace("alipay.eco.mycar.parking.exitinfo.sync(车辆驶出接口)", "debug");
                trace($data, "debug");
                //trace($resultCodeArray2, "debug");
                if ($resultCodeArray["code"] == 10000) {
                    trace($resultCodeArray, "debug");
                    return ["code" => 1, "message" => "成功", "data" => ""];
                } else {
                    trace("↓↓".$actionname, "error");
                    trace($paramArray, "error");
                    trace($resultCodeArray, "error");
                    return ["code" => 0, "message" => $actionname . (isset($resultCodeArray["sub_msg"]) ? $resultCodeArray["sub_msg"] : $resultCodeArray["msg"]), "data" => $data];
                }
                break;
            case 'parking.parkinglotinfo.create': //alipay.eco.mycar.parking.parkinglotinfo.create(录入停车场信息)
                //https://docs.open.alipay.com/api_19/alipay.eco.mycar.parking.parkinglotinfo.create/
                $data                     = [];
                $data["out_parking_id"]   = $paramArray["out_parking_id"]; //ISV停车场ID，由ISV提供，同一个isv或商户范围内唯一
                $data["parking_address"]  = $paramArray["parking_address"]; //停车场地址
                $data["parking_lot_type"] = 2; //停车场类型，1为居民小区、2为商圈停车场（购物中心商业广场商场等）、3为路侧停车、4为公园景点（景点乐园公园老街古镇等）、5为商务楼宇（酒店写字楼商务楼园区等）、6为其他、7为交通枢纽（机场火车站汽车站码头港口等）、8为市政设施（体育场博物图书馆医院学校等）
                $data["parking_poiid"]    = $paramArray["parking_poiid"]; //高德地图唯一标识
                $data["parking_mobile"]   = $paramArray["parking_mobile"]; //停车场客服电话
                $data["pay_type"]         = "1,2,3"; //支付方式（1为支付宝在线缴费，2为支付宝代扣缴费，3当面付)，如支持多种方式以','进行间隔
                $data["parking_name"]     = $paramArray["parking_name"]; //停车场名称
                $data["agent_id"]         = $paramArray["agent_id"]; //服务商ID（2088开头的16位纯数字），由服务商提供给ISV
                $data["extend_params"]    = ["sys_service_provider_id" => config("sys_service_provider_id")];
                $data["mchnt_id"]         = $paramArray["mchnt_id"]; //收款方ID（2088开头的16位纯数字），由停车场收款的业主方提供给ISV，该字段暂用于机具和物料申领
                $resultCodeArray          = $alipay->requestSHH("AlipayEcoMycarParkingParkinglotinfoCreateRequest", $data, $paramArray["app_auth_token"]);
                //return $resultCodeArray;
                if ($resultCodeArray["code"] == 10000) {
                    return ["code" => 1, "message" => "成功", "data" => $resultCodeArray];
                } else {
                    return ["code" => 0, "message" => $actionname . (json_encode($resultCodeArray,JSON_UNESCAPED_UNICODE)), "data" => $data];
                }
                break;
            case 'parking.parkinglotinfo.update': //alipay.eco.mycar.parking.parkinglotinfo.update(更新停车场信息)
                //https://docs.open.alipay.com/api_19/alipay.eco.mycar.parking.parkinglotinfo.update//
                $data                     = [];
                $data["parking_id"]       = $paramArray["parking_id"]; //支付宝返回停车场id，系统唯一
                $data["parking_address"]  = $paramArray["parking_address"]; //停车场地址
                $data["parking_name"]     = $paramArray["parking_name"]; //停车场名称
                $data["parking_lot_type"] = 5; //停车场类型，1为居民小区、2为商圈停车场（购物中心商业广场商场等）、3为路侧停车、4为公园景点（景点乐园公园老街古镇等）、5为商务楼宇（酒店写字楼商务楼园区等）、6为其他、7为交通枢纽（机场火车站汽车站码头港口等）、8为市政设施（体育场博物图书馆医院学校等）
                $data["parking_poiid"]    = $paramArray["parking_poiid"]; //高德地图唯一标识
                $data["parking_mobile"]   = $paramArray["parking_mobile"]; //停车场客服电话
                $data["pay_type"]         = "1,2,3"; //支付方式（1为支付宝在线缴费，2为支付宝代扣缴费，3当面付)，如支持多种方式以','进行间隔
                $data["agent_id"]         = $paramArray["agent_id"]; //服务商ID（2088开头的16位纯数字），由服务商提供给ISV
                $data["extend_params"]    = ["sys_service_provider_id" => config("sys_service_provider_id")];
                $resultCodeArray          = model("alipay")->requestSHH("AlipayEcoMycarParkingParkinglotinfoUpdateRequest", $data, $paramArray["app_auth_token"]);
                //return $resultCodeArray;
                if ($resultCodeArray["code"] == 10000) {
                    return ["code" => 1, "message" => "成功", "data" => $resultCodeArray];
                } else {
                    return ["code" => 0, "message" => $actionname . (isset($resultCodeArray["sub_msg"]) ? $resultCodeArray["sub_msg"] : $resultCodeArray["msg"]), "data" => $resultCodeArray];
                }
                break;
            case 'parking.vehicle.query': //alipay.eco.mycar.parking.vehicle.query(车牌查询接口)
                //https://docs.open.alipay.com/api_19/alipay.eco.mycar.parking.vehicle.query/
                $data            = [];
                $data["car_id"]  = $paramArray["car_id"]; //支付宝用户车辆ID，系统唯一。（该参数会在停车平台用户点击查询缴费，跳转到ISV停车缴费查询页面时，从请求中传递）
                $resultCodeArray = $alipay->requestSHH("AlipayEcoMycarParkingVehicleQueryRequest", $data, null, $paramArray["auth_token"]);
                //return $resultCodeArray;
                if ($resultCodeArray["code"] == 10000) {
                    return ["code" => 1, "message" => "成功", "data" => $resultCodeArray];
                } else {
                    return ["code" => 0, "message" => json_encode($resultCodeArray, JSON_UNESCAPED_UNICODE), "data" => $data];
                }
                break;
            case "parking.order.update": //alipay.eco.mycar.parking.order.update(订单更新接口) (还没有调用到，2018-9-5 09:34:23,CJH)
                $data                  = [];
                $data["user_id"]       = $paramArray["ali_user_id"]; //停车缴费支付宝用户的ID，请ISV保证用户ID的正确性，以免导致用户在停车平台查询不到相关的订单信息 2088006362935583
                $data["order_no"]      = $paramArray["ali_order_no"]; //支付宝支付流水号，系统唯一,PO20160805204323394865
                $data["order_status"]  = $paramArray["order_status"]; //用户停车订单状态，0：成功，1：失败
                $data["extend_params"] = ["sys_service_provider_id" => config("sys_service_provider_id")];
                $resultCodeArray       = $alipay->requestSHH("AlipayEcoMycarParkingOrderUpdateRequest", $data, $paramArray["app_auth_token"]);
                //return $resultCodeArray;
                if ($resultCodeArray["code"] == 10000) {
                    return ["code" => 1, "message" => "成功", "data" => $resultCodeArray];
                } else {
                    return ["code" => 0, "message" => $actionname . (isset($resultCodeArray["sub_msg"]) ? $resultCodeArray["sub_msg"] : $resultCodeArray["msg"]), "data" => $data];
                }
                break;
            case "parking.order.sync": //alipay.eco.mycar.parking.order.sync(订单同步接口)
                //https://docs.open.alipay.com/api_19/alipay.eco.mycar.parking.order.sync/
                $data                   = [];
                $data["user_id"]        = $paramArray["ali_user_id"]; //停车缴费支付宝用户的ID，请ISV保证用户ID的正确性，以免导致用户在停车平台查询不到相关的订单信息 2088006362935583
                $data["out_parking_id"] = $paramArray["parking_id"]; //ISV停车场ID，由ISV提供，同一个isv或商户范围内唯一
                $data["parking_name"]   = $paramArray["parking_name"]; //停车场名称，由ISV定义，尽量与高德地图上的一致
                $data["car_number"]     = strtoupper($paramArray["car_number"]); //车牌
                $data["out_order_no"]   = $paramArray["order_no"]; //设备商订单号，由ISV系统生成
                $data["order_status"]   = $paramArray["order_status"]; //设备商订单状态，0：成功，1：失败   1
                $data["order_time"]     = $paramArray["order_add_time"]; //订单创建时间，格式"YYYY-MM-DD HH:mm:ss"，24小时制
                $data["order_no"]       = $paramArray["ali_order_no"]; //支付宝支付流水，系统唯一
                $data["pay_time"]       = $paramArray["pay_time"]; //缴费时间, 格式"YYYYMM-DD HH:mm:ss"，24小时制
                $data["pay_type"]       = ($paramArray["parking_record_pay_type"] == "noconfirmpayment" ? 2 : 1); //付款方式，1：支付宝在线缴费 ，2：支付宝代扣缴费
                $data["pay_money"]      = $paramArray["pay_money"]; //缴费金额，保留小数点后两位
                $data["in_time"]        = $paramArray["in_time"]; //入场时间，格式"YYYY-MM-DD HH:mm:ss"，24小时制
                $data["parking_id"]     = $paramArray["ali_parking_id"]; //支付宝停车场id，系统唯一
                $data["in_duration"]    = $paramArray["in_duration"]; //停车时长（以分为单位）
                $data["card_number"]    = "*"; //如果是停车卡缴费，则填入停车卡卡号，否则为'*'
                $data["extend_params"]  = ["sys_service_provider_id" => config("sys_service_provider_id")];
                trace("alipay.eco.mycar.parking.order.sync(订单同步接口)", "debug");
                trace($data, "debug");
                trace($paramArray["parking_record_pay_type"], "debug");
                $resultCodeArray = $alipay->requestSHH("AlipayEcoMycarParkingOrderSyncRequest", $data, $paramArray["app_auth_token"]);
                // if ($paramArray["parking_record_pay_type"] == "noconfirmpayment") {
                // } else {
                //     $resultCodeArray = $alipay->request("AlipayEcoMycarParkingOrderSyncRequest", $data, $paramArray["app_auth_token"]);
                // }
                if ($resultCodeArray["code"] == 10000) {
                    trace($resultCodeArray, "debug");
                    return ["code" => 1, "message" => "成功", "data" => $resultCodeArray];
                } else {
                    trace("↓↓".$actionname, "error");
                    trace($paramArray, "error");
                    trace($resultCodeArray, "error");
                    return ["code" => 0, "message" => $actionname . (isset($resultCodeArray["sub_msg"]) ? $resultCodeArray["sub_msg"] : $resultCodeArray["msg"]), "data" => [$data, $resultCodeArray]];
                }
                break;
            case 'parking.agreement.query': //alipay.eco.mycar.parking.agreement.query(车牌代扣状态查询API)
                $data               = [];
                $data["car_number"] = strtoupper($paramArray["car_number"]); //车牌号
                $resultCodeArray    = $alipay->requestSHH("AlipayEcoMycarParkingAgreementQueryRequest", $data);
                //trace("alipay.eco.mycar.parking.agreement.query(车牌代扣状态查询API)", "debug");
                //trace($data, "debug");
                //trace($resultCodeArray, "debug");
                //agreement_status 车牌代扣状态，0：为支持代扣，1：为不支持代扣
                if ($resultCodeArray["code"] == 10000 && $resultCodeArray["agreement_status"] == 0) {
                    return ["code" => 1, "message" => "支持支付宝自动代扣", "data" => ""]; //支持代扣
                } else {
                    return ["code" => 0, "message" => $actionname . (isset($resultCodeArray["sub_msg"]) ? $resultCodeArray["sub_msg"] : $resultCodeArray["msg"]), "data" => $resultCodeArray];
                }
                break;
            case 'parking.order.pay': //alipay.eco.mycar.parking.order.pay(停车缴费代扣接口API)
                $data                   = [];
                $data["car_number"]     = strtoupper($paramArray["car_number"]);
                $data["out_trade_no"]   = $paramArray["out_trade_no"];
                $data["subject"]        = $paramArray["subject"];
                $data["total_fee"]      = $paramArray["total_fee"];
                $data["seller_id"]      = $paramArray["seller_id"];
                $data["out_parking_id"] = $paramArray["out_parking_id"];
                $data["agent_id"]       = $paramArray["agent_id"];
                $data["parking_id"]     = $paramArray["parking_id"];
                //$data["car_number_color"] = $paramArray["car_number_color"]; //代扣暂不支持非蓝色车牌
                $data["car_number_color"] = "blue"; //支付宝目前只支持蓝色车牌
                $data["body"]             = $paramArray["body"];
                $resultCodeArray          = $alipay->requestSHH("AlipayEcoMycarParkingOrderPayRequest", $data);
                trace("alipay.eco.mycar.parking.order.pay(停车缴费代扣接口API)", "debug");
                trace($data, "debug");
                trace($resultCodeArray, "debug");
                /*
                支付宝返回
                array (
                'msg' => 'Success',
                'gmt_payment' => '2018-09-04 15:13:58',
                'code' => '10000',
                'out_trade_no' => '459',
                'user_id' => '2088002258467231',
                'total_fee' => '0.01',
                'trade_no' => '2018090422001467231001030742',
                'fund_bill_list' => '[{"amount":0.01,"fund_channel":"10"}]',
                )*/
                if ($resultCodeArray["code"] == 10000 && $resultCodeArray["msg"] == "Success") {
                    return ["code" => 1, "message" => "支付宝自动代扣成功", "data" => $resultCodeArray]; //代扣成功
                } else {
                    trace("alipay.eco.mycar.parking.order.pay(停车缴费代扣接口API)", "error");
                    trace($data, "error");
                    trace($resultCodeArray, "error");
                    return ["code" => 0, "message" => $actionname . (isset($resultCodeArray["sub_msg"]) ? $resultCodeArray["sub_msg"] : $resultCodeArray["msg"]), "data" => $data];
                }
                break;
            default:
                # code...
                break;
        }
    }
}
