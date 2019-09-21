<?php
namespace app\common\model;

use think\Model;

class ParkingRecord extends Model
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
    /**
     * 停车场入场
     * 推送车辆进场信息
     * 临时车进场，接收车辆进场信息
     * @return [type] [description]
     */
    public function carin($DataArray = [])
    {
        $car_number    = $DataArray["car_number"]; //车牌  字符串(string) 是       浙AD0093
        $in_time       = $DataArray["access_time"]; //进场时间unix时间戳格式   数字(number)  是       1528686040
        $car_type      = $DataArray["car_type"]; //车型  字符串(string) 是       大车
        $in_type       = (isset($DataArray["in_type"]) ? $DataArray["in_type"] : "智能识别"); //进场类型    字符串(string) 是       通道扫牌
        $order_id      = $DataArray["order_id"]; //订单记录号(车辆在停车场停车唯一订单编号)   字符串(string) 是       325101
        $empty_plot    = $DataArray["empty_plot"]; //空闲车位数   数字(number)  是       20
        $in_channel_id = $DataArray["port_id"]; //进场通道    字符串(string) 是       A1
        $plate_type    = $DataArray["plate_type"]; //
        $park_id    = $DataArray["park_id"];//by  订单好冲突，在缓存中加入停车场iD

        if (!$in_channel_id) {
            return ["code" => 0, "message" => "进场通道不可为空", "data" => ""];
        }

        if(cache("car_number_carin_".$park_id.$order_id))//防止SDK版本，http版本同时推过来相同的数据,采用cache的方式，防止下面的程序出错，sql不完整
        {
            trace(input("signtype", "SDK").":重复入场推送记录II");
            return ["code"=>0,"message"=>"重复入场推送记录,停止操作","data"=>""];
        }
        else
        {
            cache("car_number_carin_".$park_id.$order_id,true);
            trace(input("signtype", "SDK").":入场开始记录");
        }

        //锁定停车场
        $parking_id = db("parking")->where(["parking_uuid" => $DataArray["park_id"]])->value("parking_id");

        db("parking_channel")->where(["parking_channel_uuid" => $in_channel_id, "parking_channel_parking_id" => $parking_id])->update(["parking_channel_in_or_out" => "in"]);

        $parking_channel_id = db("parking_channel")->where(["parking_channel_uuid" => $in_channel_id, "parking_channel_parking_id" => $parking_id])->order("parking_channel_id desc")->value("parking_channel_id"); //2018-11-26 15:53:12 新增 order by parking_channel_id desc,，同一个park_uuid删除重建后，遗留下重复的车道（数据库没有清理干净）产生进入历史停车的错误数据

        //整理要写入数据
        $saveData                                 = [];
        $saveData["parking_record_car_number"]    = $car_number;
        $saveData["parking_record_in_time"]       = $in_time;
        $saveData["parking_record_car_type"]      = $car_type;
        $saveData["parking_record_in_type"]       = $in_type;
        $saveData["parking_record_order_id"]      = $order_id;
        $saveData["parking_record_empty_plot"]    = $empty_plot;
        $saveData["parking_record_in_channel_id"] = $parking_channel_id;
        $saveData["parking_plate_type"]           = $plate_type;
        $saveData["parking_record_parking_id"]    = $parking_id;
        //判断重复
        if (db("parking_record")->where(["parking_record_order_id" => $order_id, "parking_record_parking_id" => $parking_id])->find()) {
            db("parking_record")->where(["parking_record_order_id" => $order_id, "parking_record_parking_id" => $parking_id])->update($saveData);
            //return ["code" => 0, "message" => "订单号" . $order_id . "已经存在", "data" => ""];
        } else {
            $saveData["parking_record_addtime"] = time();
            //写入数据库
            db("parking_record")->insert($saveData);
        }

        //临时车推送到支付宝那里去
        if ($plate_type == "临时车") {
            $parkingOne = db("parking")->where(["parking_id" => $parking_id])->field("parking_ali_parking_id,parking_store_id,parking_id")->find();

            $paramArray                   = [];
            $paramArray["ali_parking_id"] = $parkingOne["parking_ali_parking_id"];
            $paramArray["car_number"]     = $DataArray["car_number"];
            $paramArray["in_time"]        = date("Y-m-d H:i:s", $in_time);
            $shop_alipay_one              = db("shop")->join("__STORE__", "store_shop_id=shop_id", "left")->where(["store_id" => $parkingOne["parking_store_id"]])->field("shop_alipay_app_auth_token_auto_pay,store_parking_poiid")->find();

            //判断是否开启了无感支付 2018-10-30 13:59:45
            if ($shop_alipay_one["store_parking_poiid"]) {
                $paramArray["app_auth_token"] = $shop_alipay_one["shop_alipay_app_auth_token_auto_pay"];
                $setRs                        = model("parking")->push2aliyun("parking.enterinfo.sync", $paramArray);
            }

            /*
        $paramArray2["car_number"]     = $DataArray["car_number"];
        $paramArray2["app_auth_token"] = $shop_alipay_app_auth_token;
        $setRs                         = model("parking")->push2aliyun("parking.agreement.query", $paramArray2);
         */
        }
        //__临时车推送到支付宝那里去
        return ["code" => 1, "message" => "记录成功", "data" => ""];
    }

    //入场更新车牌号
    public function updateincar($DataArray = [])
    {
        //锁定停车场
        $parking_id = db("parking")->where(["parking_uuid" => $DataArray["park_id"]])->value("parking_id");
        db("parking_record")->where(["parking_record_order_id" => $DataArray["order_id"], "parking_record_parking_id" => $parking_id])->update(["parking_record_car_number" => $DataArray["car_number"]]);
    }
    /**
     * 推送车辆出场信息
     * 临时车出场，接收车辆出场信息；
     *
     * @return [type] [description]
     */
    public function carout($DataArray = [])
    {
        $car_number = $DataArray["car_number"]; // // 车牌  字符串(string) 否       浙AD0093
        $car_type   = $DataArray["car_type"]; // "car_type"); // 车型  字符串(string) 否       大车
        $order_id   = $DataArray["order_id"]; // "order_id"); // 订单记录号(车辆在停车场停车唯一订单编号)   字符串(string) 是       325101，如果订单号是出场时才生成的；那么其它入场字段也必需是必填项；
        $empty_plot = $DataArray["empty_plot"]; // "empty_plot"); // 空闲车位数   数字(number)  是       20
        $out_time   = $DataArray["access_time"]; // "out_time"); // 出场时间（unix时间戳格式） 字符串(string) 是       1528699811
        $park_id    = $DataArray["park_id"];//by  订单好冲突，在缓存中加入停车场iD

        if(cache("car_number_carout_".$park_id.$order_id))//防止SDK版本，http版本同时推过来相同的数据
        {
            trace(input("signtype", "SDK").":重复出场推送记录");
            return ["code"=>0,"message"=>"重复出场推送记录,停止操作","data"=>""];
        }
        else
        {
            cache("car_number_carout_".$park_id.$order_id,true);
            trace(input("signtype", "SDK").":出场开始记录");
        }

        //锁定停车场
        $parking_id = db("parking")->where(["parking_uuid" => $DataArray["park_id"]])->cache(1)->value("parking_id");

        //更新到最后的车牌号
        db("parking_channel")->where(["parking_channel_uuid" => $DataArray["port_id"], "parking_channel_parking_id" => $parking_id])->update(["parking_channel_car_number" => $car_number, "parking_channel_car_number_time" => time(), "parking_channel_in_or_out" => "out"]);

        if ($DataArray["plate_type"] == "临时车") {
            //会造成SDK ，没有响应
            $this->fixInCanRecord($car_number, $DataArray["park_id"], $order_id); //检查是否要入口数据补全
        }

        $parking_record_one_old = db("parking_record")->where(["parking_record_real_pay_time" => 0, "parking_record_order_id" => $order_id, "parking_record_parking_id" => $parking_id])->find();

        if (!$parking_record_one_old) //重复出场(前面已经缴过费0了)
        {

            trace("无入场记录,可能是缴费结束又倒车进入了", "debug");
            return ["code" => 0, "message" => "无入场记录", "data" => ""];
        }

        $in_time        = $parking_record_one_old["parking_record_in_time"];
        $duration       = ceil(($DataArray["access_time"] - $in_time) / 60); // "duration"); // 停车时长(分钟)    数字(number)  是       50
        $out_type       = (isset($DataArray["out_type"]) ? $DataArray["out_type"] : "智能识别"); //进场类型    字符串(string)
        $pay_type       = "offlinepay"; // "pay_type", "offlinepay"); // 支付类型    字符串(string) 是   offlinepay  支付类型:offlinepay(请求离线支付),facepay(当面付)
        $auth_code      = ""; // "auth_code"); //   微信、支付宝付款码   字符串(string) 否       当pay_type为facepay时，必传此字段
        $out_channel_id = $DataArray["port_id"]; // "out_channel_id"); // 出场通道    字符串(string) 是       A1
        $out_user_id    = $DataArray["operator_name"]; // "out_user_id"); //   出场收费员编号 字符串(string) 是       23456
        $total          = $DataArray["total_amount"]; // "total"); // 停车费
        $plate_type     = $DataArray["plate_type"];
        $ParkingRecord  = $this;
        //出场信息更新
        $updateRecord = [];
        //不管有没有收费，这里都相等的，变态
        /*if ($DataArray["amount_receivables"] == $DataArray["amount_spaid"]) {
        //线下支付过了
        $pay_type                                      = "cash";
        $updateRecord["parking_record_real_pay_time"]  = $out_time;
        $updateRecord["parking_record_pay_state"]      = 100;
        $updateRecord["parking_record_real_pay_total"] = $DataArray["amount_spaid"];
        }*/
        $updateRecord["parking_record_empty_plot"] = $empty_plot;
        $updateRecord["parking_record_out_time"]   = $out_time;
        $updateRecord["parking_record_duration"]   = $duration;
        $updateRecord["parking_record_out_type"]   = $out_type;
        //请求的支付类型
        $updateRecord["parking_record_pay_type"] = $pay_type;
        $updateRecord["parking_plate_type"]      = $plate_type;
        $parking_channel_out_id                  = model("parking_channel")->where(["parking_channel_uuid" => $out_channel_id, "parking_channel_parking_id" => $parking_id])->order("parking_channel_id desc")->value("parking_channel_id"); //2018-11-26 15:53:12 新增 order by parking_channel_id desc,，同一个park_uuid删除重建后，遗留下重复的车道（数据库没有清理干净）产生进入历史停车的错误数据

        $updateRecord["parking_record_out_channel_id"] = $parking_channel_out_id;
        $updateRecord["parking_record_out_user_id"]    = $out_user_id;
        if ($total > 0) {
            $updateRecord["parking_record_total"]               = $total; //场内提前支付后，这里的金额会变成0（也是当前停车费的金额）
            $updateRecord["parking_record_get_price_last_time"] = $out_time;
        }
        db("parking_record")->where(["parking_record_order_id" => $order_id, "parking_record_parking_id" => $parking_id])->update($updateRecord);
        //临时车推送到支付宝那里去
        if ($plate_type == "临时车") {
            $paramArray                   = [];
            $parkingOne                   = db("parking")->where(["parking_id" => $parking_id])->field("parking_ali_parking_id,parking_store_id,parking_id")->find();
            $paramArray["ali_parking_id"] = $parkingOne["parking_ali_parking_id"];
            $paramArray["car_number"]     = $DataArray["car_number"];
            $paramArray["in_time"]        = date("Y-m-d H:i:s", $in_time);
            $paramArray["out_time"]       = date("Y-m-d H:i:s", $out_time);

            $shop_alipay_one = db("shop")->join("__STORE__", "store_shop_id=shop_id", "left")->where(["store_id" => $parkingOne["parking_store_id"]])->field("shop_alipay_app_auth_token_auto_pay,store_parking_poiid")->find();
            //判断是否开启了无感支付 2018-10-30 13:59:45
            if ($shop_alipay_one["store_parking_poiid"]) {
                $paramArray["app_auth_token"] = $shop_alipay_one["shop_alipay_app_auth_token_auto_pay"];
                $setRs                        = model("parking")->push2aliyun("parking.exitinfo.sync", $paramArray);
            }
        }
        //__临时车推送到支付宝那里去
        //发起支付
        if ($total > 0) {
//无车费的就不要发起支付（免费10分钟内的、提前支付过的无需要发起支付）
            $theNewOne = $this->where(["parking_record_order_id" => $order_id, "parking_record_parking_id" => $parking_id])->find();
            $this->dopay($theNewOne);
        }
        return ["code" => 1, "message" => "", "data" => ""];
    }

    //手动抬杆(进场和出场)
    public function handOpenDoor($DataArray = [])
    {
        //trace("手动抬杆(进场和出场)");
        //trace($DataArray);
        //抬杠了，就清除当前道口的车牌号（有些是付现金手动开门的，手动开门的车子的记录就设置为已支付）
        $where["parking_channel_in_or_out"] = "out";
        $where["parking_channel_uuid"]      = $DataArray["port_id"];
        $where["parking_uuid"]              = $DataArray["park_id"];

        db("parking_channel")->join("__PARKING__", "parking_id=parking_channel_parking_id", "left")->where($where)->update(["parking_channel_car_number" => ""]);

        //手动开闸的，都设置为订单已经支付
        //$where2["parking_record_out_time"]=0;//(每个临时车出场都是有出场时间记录的，这个条件不可行)

        $where2["parking_channel_in_or_out"]     = "out";
        $where2["parking_channel_uuid"]          = $DataArray["port_id"];
        $where2["parking_uuid"]                  = $DataArray["park_id"];
        $where2["parking_record_real_pay_total"] = 0; //实际支付是0（现金，或者没有支付就出场了）
        $where2["parking_record_pay_type"]       = "offlinepay";
        $where2["parking_record_order_id"]       = $DataArray["order_id"];

        $update2["parking_record_out_type"] = "手动开闸";

        db("parking_record")->join("__PARKING_CHANNEL__", "parking_record_out_channel_id=parking_channel_id", "left")->join("__PARKING__", "parking_id=parking_channel_parking_id", "left")->where($where2)->update($update2);

    }

    /**
     * 停车记录发起支付
     * @param  [object] $ParkingRecordOne [description]
     * @return [type]                   [description]
     */
    public function dopay($ParkingRecordOne)
    {
        $parking_record = model("parking_record");
        if ($ParkingRecordOne->parking_record_pay_state == 100) {
            return ["code" => 0, "message" => "该订单已经支付过", "data" => ["state" => 100]];
        }
        //trace($ParkingRecordOne->parking_record_pay_type,"error");
        switch ($ParkingRecordOne->parking_record_pay_type) {
//请求的支付类型(offlinepay、facepay)
            //offlinepay：wallet（电子钱包/余额）,free（免费放行）,monthuser（月卡会员）,noconfirmpayment（无感支付）
            //facepay：facepay（微信，支付宝或扫码枪）
            case 'offlinepay':
                $is_pay_success      = false;
                $parking_channel_one = db("parking_channel")->join("__PARKING__", "parking_channel_parking_id=parking_id")->join("__STORE__", "parking_store_id=store_id", "left")->join("__SHOP__", "store_shop_id=shop_id", "left")->where(["parking_channel_id" => $ParkingRecordOne->parking_record_out_channel_id])->find();

                //如果是包月会员，直接放行
                /*if (!$is_pay_success) {
                $month_param        = ["car_number" => $ParkingRecordOne->parking_record_car_number, "store_id" => $parking_channel_one["parking_store_id"], "parking_id" => $parking_channel_one["parking_store_id"]];
                $is_month_member_rs = model("member_car_record")->search_month($month_param);
                trace("查询是否是包月车↓↓↓", "debug");
                trace($month_param, "debug");
                trace($is_month_member_rs, "debug");
                if ($is_month_member_rs["code"] == 1) {
                $is_month_member_rs["data"]["record_id"] = 11;
                //修改支付方式
                model("parking_record")->save(["parking_record_pay_type" => "monthuser", "parking_record_real_pay_time" => time(), "parking_record_pay_state" => 100, "parking_record_real_pay_id" => $is_month_member_rs["data"]["record_id"]], ["parking_record_id" => $ParkingRecordOne->parking_record_id]);
                $this->parkingRecordStateChange($ParkingRecordOne->parking_record_id);
                //会触发停车后续动作
                $is_pay_success = true;
                }
                }*/
                //如果是包月会员，直接放行
                //如果不是包月，且电子钱包余额充足
                //如果不是包月，且电子钱包余额不足，回复提醒发起支付宝、微信支付
                //__如果是内部会员
                //如果不是内部会员，发去支付宝、微信无感支付（无感失败，发起单面付）
                /*执行无感支付的流程*/
                if (!$is_pay_success && trim($parking_channel_one["store_parking_poiid"])) {
                    $paramArray2["car_number"] = $ParkingRecordOne->parking_record_car_number;
                    $agreementRs               = model("parking")->push2aliyun("parking.agreement.query", $paramArray2);
                    trace("是否支持无感支付的查询II", "debug");
                    trace($ParkingRecordOne->parking_record_car_number, "debug");
                    trace($agreementRs, "debug");
                    if ($agreementRs["code"] == 1) //支持支付宝的无感支付
                    {
                        //创建订单
                        $newOrder = model("order")->createOrder(["channel" => "auto_pay_alipay", "buyer_id" => 0, "user_id" => $parking_channel_one["parking_channel_user_id"], "total_amount" => $ParkingRecordOne->parking_record_total, "subject" => $ParkingRecordOne->parking_record_car_number . "停车代扣缴费", "guest_brief" => "", "product_code" => "", "create_where" => "parking", "sale_order_num" => $ParkingRecordOne->parking_record_id]);
                        //先抬杠
                        $paramArrayOrderPay                 = [];
                        $paramArrayOrderPay["car_number"]   = $ParkingRecordOne->parking_record_car_number;
                        $paramArrayOrderPay["out_trade_no"] = $newOrder["data"]["out_trade_no"];
                        $paramArrayOrderPay["subject"]      = $ParkingRecordOne->parking_record_car_number . "停车代扣缴费";
                        $paramArrayOrderPay["total_fee"]    = $ParkingRecordOne->parking_record_total;
                        //$paramArrayOrderPay["seller_id"]        = config("sys_service_provider_id");
                        $paramArrayOrderPay["seller_id"]        = $parking_channel_one["shop_alipay_seller_id"];
                        $paramArrayOrderPay["parking_id"]       = $parking_channel_one["parking_ali_parking_id"];
                        $paramArrayOrderPay["out_parking_id"]   = $parking_channel_one["parking_channel_parking_id"];
                        $paramArrayOrderPay["agent_id"]         = config("sys_service_provider_id"); //代扣返佣的支付宝用户号。以2088开头的纯16位数。
                        $paramArrayOrderPay["car_number_color"] = $ParkingRecordOne->parking_record_car_type;
                        $paramArrayOrderPay["body"]             = "停车免密支付";
                        $agreementRs                            = model("parking")->push2aliyun("parking.order.pay", $paramArrayOrderPay);
                        if ($agreementRs["code"] == 1) //无感支付成功了!
                        {
                            //修改支付方式
                            model("parking_record")->save(["parking_record_pay_type" => "noconfirmpayment"], ["parking_record_id" => $ParkingRecordOne->parking_record_id]);
                            $order_num = db("order")->where(["order_other_sale_order_num" => $ParkingRecordOne->parking_record_id])->value("order_num");
                            //修改主订单
                            model("order")->orderStatusChange($order_num, $agreementRs["data"]["total_fee"], $agreementRs["data"]["trade_no"], "auto_pay_alipay", "TRADE_SUCCESS", $agreementRs["data"]);
                            //orderStatusChange 会触发停车后续动作
                        }
                        $is_pay_success = true;
                    }
                    //发起无感支付
                    /*
                $ParkingRecordOne->parking_record_real_pay_type  = "noconfirmpayment";
                $ParkingRecordOne->parking_record_real_pay_total = $ParkingRecordOne->parking_record_total;
                $ParkingRecordOne->parking_record_real_pay_time  = time();
                $ParkingRecordOne->parking_record_pay_state      = 100;
                $ParkingRecordOne->save();
                 */
                }
                /*__执行无感支付的流程*/
                //return ["code" => 0, "message" => "自动支付失败，请转付款码", "data" => ["state" => 400]];
                return ["code" => 1, "message" => "支付成功", "data" => ["state" => 100]];
                break;
            case 'facepay': //发起当面付
                $parking_channel = model("parking_channel");
                //获取收费员账号ID
                $parking_channel_one = $parking_channel->where(["parking_channel_id" => $ParkingRecordOne->parking_record_out_channel_id])->find();
                $user_id             = 0;
                if (!($user_id = $parking_channel_one->parking_channel_user_id)) //没有收费员
                {
                    $parking_store_id = model("parking")->where(["parking_id" => $parking_channel_one->parking_channel_parking_id])->value("parking_store_id");
                    //自动创建
                }
                if ($user_id) {
                    $paramData = [
                        "user_id"        => $user_id,
                        "total_amount"   => $ParkingRecordOne->parking_record_total,
                        "subject"        => $ParkingRecordOne->parking_record_car_number . "停车收费",
                        "auth_code"      => $ParkingRecordOne->parking_record_auth_code,
                        "version"        => "1.0",
                        "time"           => time(),
                        "create_where"   => "parking",
                        "sale_order_num" => $ParkingRecordOne->parking_record_id,
                    ];
                    $paramData["sign"] = publicRequestjiami($paramData, model("user")->where(["user_id" => $user_id])->value("user_token"));
                    $url               = config("inner_post_domain") . url("api/pay/barpay");
                    $httpPostHtml      = httpsPost($url, $paramData); //转php-frm执行了
                    //$httpPostHtmlArray = json_decode($httpPostHtml, 1);
                }
                //__创建支付订单
                return ["code" => 0, "message" => "车主正在支付中", "data" => ["state" => 600]];
                //return ["code" => 1, "message" => "支付成功", "data" => ["state" => 100]];
                break;
            default:
                # code...
                break;
        }
    }

    /**
     * 补全入场记录
     * 遇到入场时、无网络、无电、服务器异常等情况
     * 在查询费用、或者出口时自动补全入口记录
     * 在车子已经出场时$in_port_id、$local_order_id参数是必填的；
     * @return [type] [description]
     */
    public function fixInCanRecord($car_number, $parking_uuid, $local_order_id)
    {
        $this_car_number     = $car_number;
        $parking_id          = db("parking")->where(["parking_uuid" => $parking_uuid])->cache(1)->value("parking_id");
        $last_parking_record = db("parking_record")->where(["parking_record_parking_id" => $parking_id, "parking_record_car_number" => $this_car_number])->order("parking_record_id desc")->field("parking_record_in_time,parking_record_out_time,parking_record_order_id")->find();

        if ($last_parking_record["parking_record_order_id"] == $local_order_id && $local_order_id != "") //拥有入场记录,不执行不全
        {
            trace("拥有入场记录,不执行", "debug");
            return true;
        }

        //使用易泊的数据
        $last_access_id = db("auto_" . $parking_id . "_tcaraccess")->where(["plate_id" => $car_number])->order("access_id desc")->value("access_id");
        if (!$last_access_id) {
            trace("入场记录修复失败，进出场时间停留少于30秒", "error");
            return;
        }
        $cario_one = db("auto_" . $parking_id . "_tcario")->where(["accessid_in|accessid_out" => $last_access_id])->field("accessid_in,cario_id")->find(); //场内查费用 或 出口已扫到车牌了
        if ($cario_one) //理论上一定有的
        {
            $accessid_in_record = db("auto_" . $parking_id . "_tcaraccess")->where(["access_id" => $cario_one["accessid_in"]])->find();

            $fix_data                = [];
            $fix_data["apiname"]     = "entercar"; //动作名称
            $fix_data["park_id"]     = $parking_uuid; //停车场ID
            $fix_data["car_number"]  = $car_number; //车牌号
            $fix_data["access_time"] = strtotime($accessid_in_record["access_time"]); //通过时间，时间戳
            $fix_data["car_type"]    = "小车"; //车辆类型（大车、小车，可为空）
            $fix_data["in_type"]     = "autofix"; //进场类型（自动识别等，可为空）
            $fix_data["order_id"]    = $cario_one["cario_id"]; //停车场的订单号
            $fix_data["empty_plot"]  = ""; //空闲车位（可为空）
            $fix_data["port_id"]     = $accessid_in_record["port_id"]; //通道ID
            $fix_data["plate_type"]  = "临时车"; //临时车
            trace("fixInCanRecord", "debug");
            trace($fix_data, "debug");
            model("ParkingRecord")->carin($fix_data);
            trace("入场记录修复成功", "debug");
        } else {
            trace("入场记录修复失败，这种问题基本不大可能会出现的", "error");
        }

        //先去线下查一次记录
        //         $parking_uuid            = db("parking")->where(["parking_id" => $parking_id])->value("parking_uuid");
        //         $sendData                = [];
        //         $sendData["uuid"]        = $parking_uuid;
        //         $sendData["car_number"]  = $this_car_number;
        //         $sendData["from_compay"] = "epapi";
        //         $Parkcommon              = new \Parkcommon\Apiget();
        //         $rsArray                 = $Parkcommon->findCarIoInfoInLast($sendData);
        //         if ($rsArray["code"] == 1) //查询成功（与硬件通信成功）
        //         {

//             $is_need_fix = false;
        //             if (!$last_parking_record) {
        // //云端查不到记录
        //                 $is_need_fix = true;
        //             } else {
        //                 if ($last_parking_record["parking_record_in_time"] != $rsArray["data"]["in_time"]) {
        // //云端记录不一致（历史停车记录）
        //                     $is_need_fix = true;
        //                 }
        //             }
        //             if ($is_need_fix) {
        //                 //没有出场通过价格查询获得停车场的本地ID
        //                 //已经出场通过，车辆日志查询出场日志，获得停车场的本地ID

//                 if ($local_order_id == "") {
        //                     //到出口时才自动补全，下面代码不需要了
        //                     $sendData                = [];
        //                 $sendData["uuid"]        = $parking_uuid;
        //                 $sendData["order_id"]    = "";
        //                 $sendData["car_number"]  = $car_number;
        //                 $sendData["from_compay"] = "epapi";
        //                 $Parkcommon              = new \Parkcommon\Apiget();
        //                 $rsArray                 = $Parkcommon->getOrderFee($sendData);
        //                 if (ceil($rsArray["code"]) == 1) {
        //                 $local_order_id= $rsArray["data"]["order_id"];

//                 } else {
        //                 trace("场内车肯定能查询到价格的，如果是出场车调用就肯定错误了", "error");
        //                 }
        //                 }

//                 if (!$in_port_id) {
        //                     $in_port_id = db("parking_channel")->where(["parking_channel_in_or_out" => "in", "parking_channel_parking_id" => $parking_id])->value("parking_channel_uuid"); //随机取一个入口
        //                 }

//                 if ($local_order_id != "") {
        //                     $fix_data                = [];
        //                     $fix_data["apiname"]     = "entercar"; //动作名称
        //                     $fix_data["park_id"]     = $parking_uuid; //停车场ID
        //                     $fix_data["car_number"]  = $this_car_number; //车牌号
        //                     $fix_data["access_time"] = $rsArray["data"]["in_time"]; //通过时间，时间戳
        //                     $fix_data["car_type"]    = "小车"; //车辆类型（大车、小车，可为空）
        //                     $fix_data["in_type"]     = "autofix"; //进场类型（自动识别等，可为空）
        //                     $fix_data["order_id"]    = $local_order_id; //停车场的订单号
        //                     $fix_data["empty_plot"]  = ""; //空闲车位（可为空）
        //                     $fix_data["port_id"]     = $in_port_id; //通道ID
        //                     $fix_data["plate_type"]  = "临时车"; //临时车
        //                     trace("fixInCanRecord", "debug");
        //                     trace($fix_data, "debug");
        //                     model("ParkingRecord")->carin($fix_data);
        //                 } else {
        //                     trace("入场记录修复失败，有可能车子已经出场了，函数调用位置错误", "error");
        //                 }

//             }
        //         }

    }

    /**
     * 获得车辆/订单金额（最新）
     * @param  [type] $paramArray ["parking_record_id"=>123,"car_number"=>"浙AD750C","user_id"=>123]
     * @return [type]             [description] 返回了全字段的，其它地方可以用到；避免2次数据库查询
     */
    public function getPrice($paramArray = [])
    {
        //trace($paramArray,"error");
        $where = [];
        if (isset($paramArray["car_number"])) {
            $where["parking_record_car_number"] = $paramArray["car_number"]; //车牌号
        }
        if (isset($paramArray["parking_record_id"])) {
            $where["parking_record_id"] = $paramArray["parking_record_id"]; //订单号
        }
        if (isset($paramArray["user_id"]) && $paramArray["user_id"]) { //指定停车场
            $where["parking_channel_parking_id"] = $parking_channel_parking_id = db("parking_channel")->where(["parking_channel_user_id" => $paramArray["user_id"]])->value("parking_channel_parking_id"); //只查本停车场的车查询
        } else {
            return ["code" => 0, "message" => "丢失停车场user_id信息", "data" => ""];
        }

        $parking_record_one = db("parking_record")->join("__PARKING_CHANNEL__", "parking_channel_id=parking_record_in_channel_id", "left")->join("__PARKING__", "parking_id=parking_channel_parking_id", "left")->where($where)->order("parking_record_id desc")->find();

        $this->fixInCanRecord($paramArray["car_number"], $parking_record_one["parking_uuid"], $parking_record_one["parking_record_order_id"]); //检查是否要入口数据补全
        trace("CCJJHH");
        trace($parking_record_one);

        if ($parking_record_one) {
            //查到的是本次的，或者历史上一次的完整停车记录
            $rs_code      = 1;
            $rs_message   = "";
            $total_amount = 0;

            if ($parking_record_one["parking_record_out_time"] > 0) //已经出场了
            {
                $total_amount = $parking_record_one["parking_record_total"]; //①金额在出场时API有推送过来的;

                if ($parking_record_one["parking_record_out_type"] == "手动开闸") //非线上支付，用现金或者免费
                {
                    return ["code" => 0, "message" => "该车已线下支付(现金或免费)", "data" => ""];
                }

                if ((time() - $parking_record_one["parking_record_out_time"]) > 60 * 10) // 超过10分钟，直接认为已经离场了
                {
                    return ["code" => 0, "message" => "该车已离场", "data" => ""];
                }

            } else {
                //SDK去查询价格(1~2秒)

                $sendData                = [];
                $sendData["uuid"]        = $parking_record_one["parking_uuid"];
                $sendData["order_id"]    = $parking_record_one["parking_record_order_id"];
                $sendData["car_number"]  = $parking_record_one["parking_record_car_number"];
                $sendData["from_compay"] = "epapi";
                $Parkcommon              = new \Parkcommon\Apiget();
                $rsArray                 = $Parkcommon->getOrderFee($sendData);

                trace("SDK去查询价格");
                trace($rsArray);
                if (ceil($rsArray["code"]) == 0) {
                    //易泊没有查询到
//                    $rs_code      = 0;
//                    $rs_message   = $rsArray["message"];
//                    $total_amount = 0;
                    //查到了，已支付状态
                    if($rsArray["message"] == '已支付！！'){
                        $rs_code      = 1;
                        $rs_message   = $rsArray["message"];
                        $total_amount = $parking_record_one['parking_record_real_pay_total'];
                    }else{
                        return ["code" => 0, "message" => "没有该停车信息", "data" => ""];
                    }
                } else {
                    $total_amount = $rsArray["data"]["fee"];

                    //$total_amount = 0;
                    //写到本地的数据里去，后面要用到的
                    db("parking_record")->where(["parking_record_id" => $parking_record_one["parking_record_id"]])->update(["parking_record_total" => $total_amount, "parking_record_get_price_last_time" => time()]);
                    //重新查询新的记录
                    $parking_record_one = db("parking_record")->join("__PARKING_CHANNEL__", "parking_channel_id=parking_record_in_channel_id", "left")->join("__PARKING__", "parking_id=parking_channel_parking_id", "left")->where($where)->order("parking_record_id desc")->find();

                    trace("CCJJHH_新的值");
                    trace($parking_record_one);
                }
            }
            return ["code" => $rs_code, "message" => $rs_message, "data" => array_merge($parking_record_one, ["total_amount" => $total_amount])];
        } else {
            return ["code" => 0, "message" => "没有该停车信息", "data" => ""];
        }
    }

    /**
     * 订单状态被改变了才触发
     * order 记录已经修改完毕
     * parking record 的记录还未修改
     * 由order->orderStatusChange() 同步调用
     * @param  string $value [description]
     * @return [type]        [description]
     */
    public function parkingRecordStateChange($parking_record_id)
    {
        $paramArrayToEP01 = [];
        $voice_text       = ""; //LED的文字
        $OpenDoorData     = [];
        $LEDDisplayData   = [];
        $LEDPlayVoiceData = [];
        //$epapi            = model("epapi");
        $Parkcommon = new \Parkcommon\Apiget();

        //判断是否场内预付
        if (!$parking_record_out_channel_id = db("parking_record")->where(["parking_record_id" => $parking_record_id])->value("parking_record_out_channel_id")) //已经付款了，但还是没有出场的ID，即场内付款
        {
            $parking_record_one = db("parking_record")->join("__PARKING_CHANNEL__", "parking_record_in_channel_id=parking_channel_id", "left")->join("__PARKING__", "parking_id=parking_channel_parking_id", "left")->join("__STORE__", "parking_store_id=store_id")->where(["parking_record_id" => $parking_record_id])->field("parking_uuid,parking_channel_uuid,parking_record_car_number,parking_record_real_pay_time,parking_record_order_id,parking_id")->find();
            //缴费成功的推送
            $paramArrayToEP01                = [];
            $paramArrayToEP01["from_compay"] = isset($parking_record_one["parking_from_compay"]) ? $parking_record_one["parking_from_compay"] : "epapi";
            $paramArrayToEP01["uuid"]        = $parking_record_one["parking_uuid"];
            //$paramArrayToEP01["out_port_id"]       = $parking_record_one["parking_channel_uuid"];
            $paramArrayToEP01["out_port_id"] = "";
            $paramArrayToEP01["car_number"]  = $parking_record_one["parking_record_car_number"];
            $paramArrayToEP01["order_no"]    = $parking_record_id;
            $paramArrayToEP01["order_id"]    = $parking_record_one["parking_record_order_id"];
            //$paramArrayToEP01["pay_time"]          = $parking_record_one["parking_record_real_pay_time"];
            //$paramArrayToEP01["pay_amount"]        = 0;
            $paramArrayToEP01["pay_id"]            = $parking_record_id;
            $paramArrayToEP01["accept_account_id"] = "";
            $paramArrayToEP01["pay_type"]          = 2; //按支付宝的给
            trace("向易泊发送支付完成这个事件1", "debug");
            //trace($paramArrayToEP01, "debug");
            $paramArrayToEP01["pay_finish_type"] = 0; //0预支付完成，1出场支付完成
            $orderOneObject                      = model("order")->where(["order_other_sale_order_num" => $parking_record_id, "order_create_where" => 'parking'])->order(["order_id" => "desc"])->find();
            $paramArrayToEP01["pay_amount"]      = $orderOneObject->order_pay_realprice;
            $paramArrayToEP01["pay_time"]        = strtotime($orderOneObject->order_pay_time);
            //同步修改停车记录
            $parkingRecordupdateData["parking_record_pay_state"]      = $orderOneObject->order_status;
            $parkingRecordupdateData["parking_record_real_pay_time"]  = strtotime($orderOneObject->order_pay_time);
            $parkingRecordupdateData["parking_record_real_pay_total"] = $orderOneObject->order_pay_realprice;
            $this->isUpdate(true)->save($parkingRecordupdateData, ["parking_record_id" => $orderOneObject->order_other_sale_order_num]);
            //__缴费成功的推送
        } else //出口付的
        {
            //指定出口的
            //要考虑中央付费的记录（即无出口的数据，，2018-9-21 11:44:27）
            $parking_record_one = db("parking_record")->join("__PARKING_CHANNEL__", "parking_record_out_channel_id=parking_channel_id", "left")->join("__PARKING__", "parking_id=parking_channel_parking_id", "left")->join("__STORE__", "parking_store_id=store_id")->where(["parking_record_id" => $parking_record_id])->field("parking_uuid,parking_channel_uuid,parking_record_car_number,parking_record_order_id,parking_id,parking_ali_parking_id,parking_name,parking_record_in_time,parking_record_out_time,parking_record_duration,store_name,parking_record_pay_type,parking_channel_car_number,parking_record_real_pay_time,parking_channel_car_number_time")->find();
            trace($parking_record_one, "debug");
            // trace("AAA","debug");
            // trace($parking_record_one["parking_record_pay_type"],"debug");
            // trace("CJHCJH","debug");
            switch ($parking_record_one["parking_record_pay_type"]) {
                case 'monthuser': //包月
                    /*
                    //缴费成功的推送
                    $paramArrayToEP01                      = [];\
                    $paramArrayToEP01["from_compay"]       = isset($parking_record_one["parking_from_compay"])?$parking_record_one["parking_from_compay"]:"epapi";
                    $paramArrayToEP01["uuid"]           = $parking_record_one["parking_uuid"];
                    $paramArrayToEP01["out_port_id"]           = $parking_record_one["parking_channel_uuid"];
                    $paramArrayToEP01["car_number"]          = $parking_record_one["parking_record_car_number"];
                    $paramArrayToEP01["order_no"]          = "";
                    $paramArrayToEP01["order_no"]          = $parking_record_one["parking_record_order_id"];
                    $paramArrayToEP01["pay_time"]          = $parking_record_one["parking_record_real_pay_time"];
                    $paramArrayToEP01["pay_amount"]        = 0;
                    $paramArrayToEP01["pay_id"]            = $parking_record_id;
                    $paramArrayToEP01["accept_account_id"] = "";
                    $paramArrayToEP01["pay_type"] = 2; //按支付宝的给
                    $voice_text = "包月车,本次不计费";
                    trace("向易泊发送支付完成这个事件", "debug");
                    trace($paramArrayToEP01, "debug");
                    $paramArrayToEP01["pay_finish_type"] = 1; //0预支付完成，1出场支付完成
                    //__缴费成功的推送
                    $OpenDoorData = [
                    "park_id" => $parking_record_one["parking_uuid"],
                    "port_id" => $parking_record_one["parking_channel_uuid"],
                    ];
                    $LEDDisplayData = [
                    "park_id" => $parking_record_one["parking_uuid"],
                    "port_id" => $parking_record_one["parking_channel_uuid"],
                    "text"    => [
                    ["row_index" => "1", "row_text" => $parking_record_one["parking_record_car_number"], "row_color" => ""],
                    ["row_index" => "2", "row_text" => "包月车", "row_color" => ""],
                    ["row_index" => "3", "row_text" => "本次不计费", "row_color" => ""],
                    ["row_index" => "4", "row_text" => "一路顺风", "row_color" => ""],
                    ]];
                    $LEDPlayVoiceData = [
                    "park_id"    => $parking_record_one["parking_uuid"],
                    "port_id"    => $parking_record_one["parking_channel_uuid"],
                    "voice_text" => $voice_text,
                    ];
                     */
                    break;
                default:
                    $orderOneObject = model("order")->where(["order_other_sale_order_num" => $parking_record_id, "order_create_where" => 'parking'])->order(["order_id" => "desc"])->find();
                    //同步修改停车记录
                    $parkingRecordupdateData["parking_record_pay_state"]      = $orderOneObject->order_status;
                    $parkingRecordupdateData["parking_record_real_pay_time"]  = strtotime($orderOneObject->order_pay_time);
                    $parkingRecordupdateData["parking_record_real_pay_total"] = $orderOneObject->order_pay_realprice;
                    $this->isUpdate(true)->save($parkingRecordupdateData, ["parking_record_id" => $orderOneObject->order_other_sale_order_num]);
                    //如果是异步更新（相对于当面付同步返回的情况）的，就向client推送一条记录
                    if (
                        ((db("order_pay_log")->where(["order_pay_log_order_id" => $orderOneObject->order_id, "order_pay_log_status" => $orderOneObject->order_status])->order("order_pay_log_status")->value("order_pay_log_from")) == "asynchronous")
                        or
                        ($parking_record_one["parking_record_pay_type"] == "noconfirmpayment") //无感支付的情况
                    ) {
                        $rsWt = model("pay")->payWayTranslate($orderOneObject->order_channel_id);
                        //trace("接收到了" . $rsWt . "的停车H5支付推送", "debug");
                        //$parking_channel_one = db("parking_channel")->where(["parking_channel_user_id" => $orderOneObject->order_user_id])->field("parking_channel_car_number,parking_channel_uuid")->find();
                        $paramArrayToEP01                      = [];
                        $paramArrayToEP01["from_compay"]       = isset($parking_record_one["parking_from_compay"]) ? $parking_record_one["parking_from_compay"] : "epapi";
                        $paramArrayToEP01["uuid"]              = $parking_record_one["parking_uuid"];
                        $paramArrayToEP01["out_port_id"]       = $parking_record_one["parking_channel_uuid"];
                        $paramArrayToEP01["car_number"]        = $parking_record_one["parking_record_car_number"];
                        $paramArrayToEP01["order_no"]          = "";
                        $paramArrayToEP01["order_id"]          = $parking_record_one["parking_record_order_id"];
                        $paramArrayToEP01["pay_time"]          = strtotime($orderOneObject->order_pay_time);
                        $paramArrayToEP01["pay_amount"]        = $orderOneObject->order_pay_realprice;
                        $paramArrayToEP01["pay_id"]            = $orderOneObject->order_num;
                        $paramArrayToEP01["accept_account_id"] = "";
                        $voice_text                            = "";
                        if ($rsWt) {
                            if (strpos($rsWt, "alipay") !== false) {
                                $pay_type   = 2;
                                $voice_text = "支付宝收钱" . $orderOneObject->order_pay_realprice . "元";
                                if ($parking_record_one["parking_record_pay_type"] == "noconfirmpayment") {
                                    $voice_text = "支付宝无感代扣" . $orderOneObject->order_pay_realprice . "元";
                                }
                            }
                            if (strpos($rsWt, "wxpay") !== false) {
                                $pay_type   = 1;
                                $voice_text = "微信收钱" . $orderOneObject->order_pay_realprice . "元";
                            }
                        }
                        $paramArrayToEP01["pay_type"] = $pay_type;
                        //$epapi                        = model("epapi");
                        trace("向易泊发送支付完成这个事件2", "debug");
                        //trace($paramArrayToEP01, "debug");
                        //判断是否要立即开闸(车已经在出口的匝门口了)；当前支付的车牌号与当前二维码绑定的口子最后获取的是否是一致的；一致就立即开门；
                        $parking_channel_car_number          = $parking_record_one["parking_channel_car_number"];
                        $paramArrayToEP01["pay_finish_type"] = 0; //0预支付完成，1出场支付完成（会自动开闸）
                        //trace($parking_record_one["parking_record_car_number"].":::".$parking_channel_car_number,"debug");
                        //车已经在出口的匝门口了
                        //
                        //if ($parking_record_one["parking_record_car_number"] == $parking_channel_car_number && (time() - $parking_record_one["parking_channel_car_number_time"] < 1 * 60)) //需要开门，声音提醒，文字显示,(出口扫牌是15分钟内的，避免一辆车进进出出都是相同数据，造成错乱)

                        if ($parking_record_one["parking_record_car_number"] == $parking_channel_car_number) // 出口已经清除了上次的车牌号，所有不需要时间判断了  2018年11月14日17:32:17
                        {
                            trace($parking_record_one["parking_record_car_number"] . ":::" . $parking_channel_car_number, "debug");
                            trace("执行了出口立扫付(自动抬杠)", "debug");
                            $paramArrayToEP01["pay_finish_type"] = 1; //0预支付完成，1出场支付完成（会自动开闸）

                            // $OpenDoorData = [
                            //     "park_id" => $parking_record_one["parking_uuid"],
                            //     "port_id" => $parking_record_one["parking_channel_uuid"],
                            // ];

                            $LEDDisplayData = [
                                "from_compay" => (isset($parking_record_one["parking_from_compay"]) ? $parking_record_one["parking_from_compay"] : "epapi"),
                                "uuid"        => $parking_record_one["parking_uuid"],
                                "port_id"     => $parking_record_one["parking_channel_uuid"],
                                "text"        => [
                                    ["row_index" => "1", "row_text" => "支付成功", "row_color" => ""],
                                    ["row_index" => "2", "row_text" => $parking_record_one["parking_record_car_number"], "row_color" => ""],
                                    ["row_index" => "3", "row_text" => $orderOneObject->order_pay_realprice . "元", "row_color" => ""],
                                    ["row_index" => "4", "row_text" => "一路顺风", "row_color" => ""],
                                ]];
                            $LEDPlayVoiceData = [
                                "from_compay" => (isset($parking_record_one["parking_from_compay"]) ? $parking_record_one["parking_from_compay"] : "epapi"),
                                "uuid"        => $parking_record_one["parking_uuid"],
                                "port_id"     => $parking_record_one["parking_channel_uuid"],
                                "voice_text"  => $voice_text,
                            ];
                        }

                        //如果是有支付宝停车记录的，更新支付宝支付记录
                        if ($rsWt) {
                            if (strpos($rsWt, "alipay") !== false && $parking_record_one["parking_record_pay_type"] == "noconfirmpayment") {
                                $paramArray                            = [];
                                $order_pay_log_one                     = db("order_pay_log")->where(["order_pay_log_order_id" => $orderOneObject->order_id, "order_pay_log_status" => $orderOneObject->order_status])->order("order_pay_log_id desc")->field(["order_pay_log_returncode", "order_pay_log_user_id"])->find();
                                $order_pay_log_returncode_array        = json_decode($order_pay_log_one["order_pay_log_returncode"], 1);
                                $paramArray["ali_user_id"]             = isset($order_pay_log_returncode_array["buyer_id"]) ? $order_pay_log_returncode_array["buyer_id"] : $order_pay_log_one["order_pay_log_user_id"];
                                $paramArray["parking_id"]              = $parking_record_one["parking_id"];
                                $paramArray["parking_name"]            = $parking_record_one["store_name"];
                                $paramArray["car_number"]              = $parking_record_one["parking_record_car_number"];
                                $paramArray["order_no"]                = $orderOneObject->order_num;
                                $paramArray["order_status"]            = 0; //0：成功，1：失败
                                $paramArray["order_add_time"]          = date("Y-m-d H:i:s", strtotime($orderOneObject->order_addtime));
                                $paramArray["ali_order_no"]            = $order_pay_log_returncode_array["trade_no"];
                                $paramArray["pay_time"]                = date("Y-m-d H:i:s", strtotime($orderOneObject->order_pay_time));
                                $paramArray["pay_money"]               = $orderOneObject->order_pay_realprice;
                                $paramArray["in_time"]                 = date("Y-m-d H:i:s", $parking_record_one["parking_record_in_time"]);
                                $paramArray["ali_parking_id"]          = $parking_record_one["parking_ali_parking_id"];
                                $paramArray["parking_record_pay_type"] = $parking_record_one["parking_record_pay_type"];
                                $paramArray["in_duration"]             = ceil((strtotime($orderOneObject->order_pay_time) - $parking_record_one["parking_record_in_time"]) / 60);
                                $shop_alipay_app_auth_token_auto_pay   = db("shop")->join("__STORE__", "shop_id=store_shop_id")->join("__PARKING__", "store_id=parking_store_id")->where(["parking_id" => $parking_record_one["parking_id"]])->value("shop_alipay_app_auth_token_auto_pay");
                                $paramArray["app_auth_token"]          = $shop_alipay_app_auth_token_auto_pay; //以商户的身份才获得数据的
                                $setRs                                 = model("parking")->push2aliyun("parking.order.sync", $paramArray);
                            }
                        }
                    } else {
                        //同步的，已经有数据同步返回，不处理
                    }
                    break;
            }
        }
        if ($paramArrayToEP01) {
            trace("告诉易泊计费系统该订单支付完成↓↓", "debug");
            $rs = $Parkcommon->sendPayOk($paramArrayToEP01);
            //$rs = $epapi->sendData("payok", $paramArrayToEP01, "yes");
            trace($rs, "debug");
        }
        if ($OpenDoorData) {
            //开道闸
            //"{\"park_id\":\"" . $park_id . "\",\"port_id\":\"".$port_id."\"}"
            //$rs = $Parkcommon->openDoor($OpenDoorData);
            //$epapi->sendData("OpenDoor", $OpenDoorData, "yes"); //无需回调
        }
        if ($LEDDisplayData) {
            $Parkcommon->ledDisplay($LEDDisplayData);
            //$epapi->sendData("LED_display", $LEDDisplayData, "yes"); //无需回调
        }
        if ($LEDPlayVoiceData) {
            $Parkcommon->playVoice($LEDPlayVoiceData);
            //$epapi->sendData("LED_playVoice", $LEDPlayVoiceData, "yes"); //无需回调
        }
    }

    //新增包月记录
    public function addMonthCar($record_id)
    {
        $one                     = db("member_car_record")->join("__STORE__", "record_store_id=store_id", "left")->join("__PARKING__", "store_id=parking_store_id", "left")->where(["record_id" => $record_id])->find();
        $sendData                = [];
        $sendData["from_compay"] = isset($one["parking_from_compay"]) ? $one["parking_from_compay"] : "epapi";
        $sendData["uuid"]            = $one["parking_uuid"];
        $sendData["car_number"]      = strtoupper($one["record_car_number_plate"]);
        $sendData["isinout"]         = ""; //是否进入， 场内、场外 可为空
        $sendData["plate_color"]     = ""; //车牌颜色 可为空
        $sendData["plate_type"]      = "月租车";
        $sendData["plate_state"]     = "正常";
        $sendData["plate_subtype"]   = "";
        $sendData["free_time"]       = "";
        $sendData["begin_date"]      = date("Y-m-d", $one["record_start_time"]);
        $sendData["end_date"]        = date("Y-m-d", $one["record_end_time"]);
        $sendData["carown_name"]     = "网上会员" . $one["record_member_id"];
        $sendData["carown_sex"]      = "";
        $sendData["carown_phone"]    = $one["record_member_id"];
        $sendData["carown_cardtype"] = "";
        $sendData["carown_cardnum"]  = "";
        $sendData["carown_birsday"]  = "";
        $sendData["carown_address"]  = "";
        $sendData["charg_scheme"]    = "";
        $sendData["del_record"]      = 0;
        $Parkcommon                  = new \Parkcommon\Apiget();
        $rsArray                     = $Parkcommon->addInnerCar($sendData); //添加包月车
        //$rsArray = model("epapi")->sendData("addInnerCar", $sendData); //添加包月车
        trace("向易泊发送包月信息", "debug");
        trace($rsArray, "debug");
        //trace($rsArray);
        return $rsArray;
    }
}
