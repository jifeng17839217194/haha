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
    public function input($DataArray, $key)
    {
        return isset($DataArray[$key]) ? $DataArray[$key] : "";
    }
    /**
     * 初始化
     * @param  [type] $DataArray [description]
     * @return [type]            [description]
     */
    public function appinitializes($DataArray = [], $servParam = [])
    {
        $park_id  = $this->input($DataArray, "park_id");
        $local_id = $this->input($DataArray, "local_id");
        $channels = $this->input($DataArray, "channels");
        $fd       = $servParam[1];
        if (!$park_id) {
            return ["code" => 0, "message" => "停车场编号不可为空", "data" => ""];
        }
        if (!$local_id) //该角色好像用不到（通过“通道”就可以区分了）
        {
            return ["code" => 0, "message" => "收费系统编号不可为空", "data" => ""];
        }
        if (!$channels) {
            return ["code" => 0, "message" => "通道数据不可为空", "data" => ""];
        }
        //查询停车场是否已经记录
        if (!($parking_one = db("parking")->where(["parking_uuid" => $park_id])->find())) {
            $this_parking_id = db("parking")->insertGetId(["parking_uuid" => $park_id, "parking_addtime" => time(), "parking_name" => "自动创建"]);
        } else {
            $this_parking_id = $parking_one["parking_id"];
        }
        $ParkingChannel = model("parking_channel");
        //通道信息，与fd绑定(或者更新绑定)
        foreach ($channels as $parking_channel_uuid) {
            if ($thisParkingChannelOne = $ParkingChannel->where(["parking_channel_parking_id" => $this_parking_id, "parking_channel_uuid" => $parking_channel_uuid])->find()) {
                if ($thisParkingChannelOne->parking_channel_fd != $fd) {
                    model("parking_channel")->isUpdate(true)->save(["parking_channel_fd" => $fd], ["parking_channel_system_uuid" => $local_id]);
                }
            } else {
                db("parking_channel")->insert(["parking_channel_fd" => $fd, "parking_channel_parking_id" => $this_parking_id, "parking_channel_uuid" => $parking_channel_uuid, "parking_channel_system_uuid" => $local_id, "parking_channel_addtime" => time()]);
            }
        }
        //__通道信息，与fd绑定(或者重新绑定)
        return ["code" => 1, "message" => "appinitialize success", "data" => []];
    }
    /**
     *  停车场入场
     * 推送车辆进场信息
     * 临时车进场，接收车辆进场信息；需要骏鹏推送
     * @return [type] [description]
     */
    public function carin($DataArray = [], $servParam = [])
    {
        $car_number        = $this->input($DataArray, "car_number"); //车牌  字符串(string) 是       浙AD0093
        $in_time           = $this->input($DataArray, "in_time"); //进场时间unix时间戳格式   数字(number)  是       1528686040
        $car_type          = $this->input($DataArray, "car_type"); //车型  字符串(string) 是       大车
        $in_type           = $this->input($DataArray, "in_type"); //进场类型    字符串(string) 是       通道扫牌
        $in_user_id        = $this->input($DataArray, "in_user_id"); //入场收费员id 字符串(string) 是       880099
        $order_id          = $this->input($DataArray, "order_id"); //订单记录号(车辆在停车场停车唯一订单编号)   字符串(string) 是       325101
        $empty_plot        = $this->input($DataArray, "empty_plot"); //空闲车位数   数字(number)  是       20
        $in_channel_id     = $this->input($DataArray, "in_channel_id"); //进场通道    字符串(string) 是       A1
        $worksite_id       = $this->input($DataArray, "worksite_id"); //岗亭/工作站id    数字(number)  是   0   23
        $work_station_uuid = $this->input($DataArray, "work_station_uuid"); //岗亭/工作站唯一标识  字符串(string) 是   0   qwrrw23534
        $in_remark         = $this->input($DataArray, "in_remark"); //备注  字符串(string) 是       入场信息备注
        if (!$car_number) {
            return ["code" => 0, "message" => "车牌不可为空", "data" => ""];
        }
        if (!is_numeric($in_time)) {
            return ["code" => 0, "message" => "进场时间必需是数字", "data" => ""];
        }
        if (!$order_id) {
            return ["code" => 0, "message" => "订单记录号不可为空", "data" => ""];
        }
        if (!$in_channel_id) {
            return ["code" => 0, "message" => "进场通道不可为空", "data" => ""];
        } else {
            $parking_channel_id = model("parking_channel")->where(["parking_channel_uuid" => $in_channel_id])->value("parking_channel_id");
        }
        //整理要写入数据
        $saveData                                     = [];
        $saveData["parking_record_car_number"]        = $car_number;
        $saveData["parking_record_in_time"]           = $in_time;
        $saveData["parking_record_car_type"]          = $car_type;
        $saveData["parking_record_in_type"]           = $in_type;
        $saveData["parking_record_in_user_id"]        = $in_user_id;
        $saveData["parking_record_order_id"]          = $order_id;
        $saveData["parking_record_empty_plot"]        = $empty_plot;
        $saveData["parking_record_in_channel_id"]     = $parking_channel_id;
        $saveData["parking_record_worksite_id"]       = $worksite_id;
        $saveData["parking_record_work_station_uuid"] = $work_station_uuid;
        $saveData["parking_record_in_remark"]         = $in_remark;
        $saveData["parking_record_addtime"]           = time();
        //判断重复
        if ($this->where(["parking_record_order_id" => $order_id])->find()) {
            return ["code" => 0, "message" => "订单号" . $order_id . "已经存在", "data" => ""];
        }
        //写入数据库
        db("parking_record")->insert($saveData);
        return ["code" => 1, "message" => "记录成功", "data" => ""];
    }
    /**
     * 推送车辆出场信息
     * 临时车出场，接收车辆出场信息；需要骏鹏推送 回调：1、同步返回： 2、异步放回；
     * @return [type] [description]
     */
    public function carout($DataArray = [], $servParam = [])
    {
        $car_number        = $this->input($DataArray, "car_number"); // 车牌  字符串(string) 否       浙AD0093
        $car_type          = $this->input($DataArray, "car_type"); // 车型  字符串(string) 否       大车
        $in_type           = $this->input($DataArray, "in_type"); //   进场类型    字符串(string) 否       通道扫牌
        $in_user_id        = $this->input($DataArray, "in_user_id"); // 入场收费员id 字符串(string) 是       880099
        $in_time           = $this->input($DataArray, "in_time"); //   进场时间unix时间戳格式   字符串(string) 否       1528686040
        $order_id          = $this->input($DataArray, "order_id"); // 订单记录号(车辆在停车场停车唯一订单编号)   字符串(string) 是       325101，如果订单号是出场时才生成的；那么其它入场字段也必需是必填项；
        $empty_plot        = $this->input($DataArray, "empty_plot"); // 空闲车位数   数字(number)  是       20
        $in_channel_id     = $this->input($DataArray, "in_channel_id"); //   进场通道    字符串(string) 否       A1
        $worksite_id       = $this->input($DataArray, "worksite_id"); //   岗亭/工作站id    字符串(string) 否   23
        $work_station_uuid = $this->input($DataArray, "work_station_uuid"); //   岗亭/工作站唯一标识  字符串(string) 否       qwrrw23534
        $in_remark         = $this->input($DataArray, "in_remark"); //   备注  字符串(string) 否       入场信息备注
        $out_time          = $this->input($DataArray, "out_time"); // 出场时间（unix时间戳格式） 字符串(string) 是       1528699811
        $duration          = $this->input($DataArray, "duration"); // 停车时长(分钟)    数字(number)  是       50
        $out_type          = $this->input($DataArray, "out_type"); // 出场类型    字符串(string) 是       通道扫牌
        $pay_type          = $this->input($DataArray, "pay_type", "offlinepay"); // 支付类型    字符串(string) 是   offlinepay  支付类型:offlinepay(请求离线支付),facepay(当面付)
        $auth_code         = $this->input($DataArray, "auth_code"); //   微信、支付宝付款码   字符串(string) 否       当pay_type为facepay时，必传此字段
        $out_channel_id    = $this->input($DataArray, "out_channel_id"); // 出场通道    字符串(string) 是       A1
        $out_user_id       = $this->input($DataArray, "out_user_id"); //   出场收费员编号 字符串(string) 是       23456
        $out_remark        = $this->input($DataArray, "out_remark"); // 出场信息备注  字符串(string) 否       出场信息备注
        $total             = $this->input($DataArray, "total"); // 停车费
        $ParkingRecord     = $this;
        if ($pay_type == "facepay" && $auth_code == "") {
            return ["code" => 0, "message" => "请求当面付时，付款码不可为空", "data" => ""];
        }
        if (!$ParkingRecord->where(["parking_record_order_id" => $order_id])->find()) //没有停车订单号
        {
            if (!$car_number) {
                return ["code" => 0, "message" => "车牌不可为空", "data" => ""];
            }
            if (!is_numeric($in_time)) {
                return ["code" => 0, "message" => "进场时间必需是数字", "data" => ""];
            }
            if (!$order_id) {
                return ["code" => 0, "message" => "订单记录号不可为空", "data" => ""];
            }
            if (!$in_channel_id) {
                return ["code" => 0, "message" => "进场通道不可为空", "data" => ""];
            } else {
                $parking_channel_id = model("parking_channel")->where(["parking_channel_uuid" => $in_channel_id])->value("parking_channel_id");
            }
            //重新建立进场数据
            //整理要写入数据
            $saveData                                     = [];
            $saveData["parking_record_car_number"]        = $car_number;
            $saveData["parking_record_in_time"]           = $in_time;
            $saveData["parking_record_car_type"]          = $car_type;
            $saveData["parking_record_in_type"]           = $in_type;
            $saveData["parking_record_in_user_id"]        = $in_user_id;
            $saveData["parking_record_order_id"]          = $order_id;
            $saveData["parking_record_empty_plot"]        = $empty_plot;
            $saveData["parking_record_in_channel_id"]     = $parking_channel_id;
            $saveData["parking_record_worksite_id"]       = $worksite_id;
            $saveData["parking_record_work_station_uuid"] = $work_station_uuid;
            $saveData["parking_record_in_remark"]         = $in_remark;
            $saveData["parking_record_addtime"]           = time();
            //写入数据库
            db("parking_record")->insert($saveData);
        }
        //出场信息更新
        $updateRecord                              = [];
        $updateRecord["parking_record_empty_plot"] = $empty_plot;
        $updateRecord["parking_record_out_time"]   = $out_time;
        $updateRecord["parking_record_duration"]   = $duration;
        $updateRecord["parking_record_out_type"]   = $out_type;
        //请求的支付类型
        $updateRecord["parking_record_pay_type"]            = $pay_type;
        $updateRecord["parking_record_auth_code"]           = $auth_code;
        $parking_channel_out_id                             = model("parking_channel")->where(["parking_channel_uuid" => $out_channel_id])->value("parking_channel_id");
        $updateRecord["parking_record_out_channel_id"]      = $parking_channel_out_id;
        $updateRecord["parking_record_out_user_id"]         = $out_user_id;
        $updateRecord["parking_record_out_remark"]          = $out_remark;
        $updateRecord["parking_record_total"]               = $total;
        $updateRecord["parking_record_get_price_last_time"] = time();
        if (!$out_time) {
            return ["code" => 0, "message" => "出场时间不可为空", "data" => ""];
        }
        if (!$out_channel_id) {
            return ["code" => 0, "message" => "出场通道不可为空", "data" => ""];
        }
        trace($updateRecord, "debug");
        db("parking_record")->where(["parking_record_order_id" => $order_id])->update($updateRecord);
        //发起支付
        $theNewOne = $this->where(["parking_record_order_id" => $order_id])->order("parking_record_id desc")->find();
        $this->dopay($theNewOne);
        return $this->payStateRS($this->where(["parking_record_order_id" => $order_id])->order("parking_record_id desc")->find());
    }
    /**
     * 停车费查询推送
     * @return [type] [description]
     */
    public function pushparkingfee($DataArray = [], $servParam = [])
    {
        $order_id       = $this->input($DataArray, "order_id"); //  订单记录号(车辆在停车场停车唯一订单编号，对应入场订单编号)  字符串(string) 是       10000
        $park_id        = $this->input($DataArray, "park_id"); //    车场编号    字符串(string) 是       20003
        $query_time     = $this->input($DataArray, "query_time"); //  查询价格时间 Number(unix时间戳格式，精确到秒)   字符串(string) 是       1490875218
        $duration       = $this->input($DataArray, "duration"); //  停车时长(分钟)    数字(number)  是       50
        $total          = $this->input($DataArray, "total", 0); //    停车费用    浮点型(float)  是       6.0
        $query_get_time = $this->input($DataArray, "query_get_time"); //  获取到价格的截止时间  字符串(string) 是       1490875218
        if (!$order_id) {
            return ["code" => 0, "message" => "订单记录号不可为空", "data" => ""];
        }
        if (!$park_id) {
            return ["code" => 0, "message" => "车场编号不可为空", "data" => ""];
        }
        if (!$query_time) {
            return ["code" => 0, "message" => "查询的时间不可为空", "data" => ""];
        }
        if ($query_time > time()) {
            return ["code" => 0, "message" => "查询的时间不能大于当前时间", "data" => ""];
        }
        if (!$duration) {
            return ["code" => 0, "message" => "停车时长不可为空", "data" => ""];
        }
        if (intval($duration) < 0) {
            return ["code" => 0, "message" => "停车时长不可小于0", "data" => ""];
        }
        if (!$total) {
            return ["code" => 0, "message" => "停车费用不可为空", "data" => ""];
        }
        if (intval($total) < 0) {
            return ["code" => 0, "message" => "停车费用不可小于0", "data" => ""];
        }
        if (!$query_get_time) {
            return ["code" => 0, "message" => "获取到价格的时间不可为空", "data" => ""];
        }
        if ($query_get_time > time()) {
            return ["code" => 0, "message" => "获取到价格的时间不能大于当前时间", "data" => ""];
        }

        if (!$ParkingRecordOne = $this->where(["parking_record_order_id" => $order_id])->find()) //没有停车订单号
        {
            return ["code" => 0, "message" => "[$order_id]计费订单不存在", "data" => ""];
        } else {
            $updateParkingRecord["parking_record_total"]               = $total;
            $updateParkingRecord["parking_record_get_price_last_time"] = $query_get_time;
            $updateParkingRecord["parking_record_duration"]            = $duration;
            db("parking_record")->where(["parking_record_order_id" => $order_id])->update($updateParkingRecord);
            /**
             * 异步推送状态到消费者APP/H5前端
             * 未完成…… 2018-6-13 09:52:09
             */
            return ["code" => 1, "message" => "接收成功", "data" => ""];
        }
    }

    /**
     * 无牌车请求入场
     * 无需要数据验证
     * @return [type] [description]
     */
    public function noNumberIn($DataArray = [])
    {
        $client_type   = $this->input($DataArray, "client_type");
        $buyer_open_id = cookie("buyer_open_id");
        $user_id       = $this->input($DataArray, "user_id"); //收费账号
        $mobile        = $this->input($DataArray, "mobile");

        if (!preg_match("/^1[0-9]{10}$/i", $mobile)) {
            return ["code" => 0, "message" => "手机号错误", "data" => ""];
        }
        $param               = [];
        $param["in_time"]    = time();
        $param["apiname"]    = "no_car_number_in";
        $param["car_number"] = "临" . $mobile;
        //前置：判断这个人有没有历史未付车辆
        if ($this->where(["parking_record_car_number" => $param["car_number"], "parking_record_pay_state" => ["neq", 100]])->find()) {
            return ["code" => 0, "message" => "该手机号绑定的临时车已入场", "data" => ""];
        }
        $param["user_token"] = $buyer_open_id;
        //获得停车场的数据
        $user      = model("user");
        $usercheck = $user->checkuseractive($user_id);
        if ($usercheck["code"] != 1) {
            return $usercheck;
        }
        $parking_channel_one    = db("parking_channel")->where(["parking_channel_user_id" => $user_id])->find();
        $param["in_channel_id"] = $parking_channel_one["parking_channel_uuid"];
        $fd                     = $parking_channel_one["parking_channel_fd"];
        //return ["code" => 0, "message" => json_encode($param, JSON_UNESCAPED_UNICODE), "data" => $param];
        $pushRs = $this->pushtoclient($fd, $param);
        if ($pushRs["code"] == 1) {
            return $pushRs;
        } else {
            return ["code" => 0, "message" => $pushRs["message"], "data" => $pushRs["data"]];
        }
    }

    /**
     * 无牌车请求出场
     * 无需要数据验证
     * @return [type] [description]
     */
    public function noNumberOut($DataArray = [])
    {
        $client_type   = $this->input($DataArray, "client_type");
        $buyer_open_id = cookie("buyer_open_id");
        $user_id       = $this->input($DataArray, "user_id"); //收费账号
        $mobile        = $this->input($DataArray, "mobile");

        if (!preg_match("/^1[0-9]{10}$/i", $mobile)) {
            return ["code" => 0, "message" => "手机号错误", "data" => ""];
        }

        $param               = [];
        $param["apiname"]    = "no_car_number_out";
        $param["car_number"] = "临" . $mobile;
        $param["out_time"]   = time();

        //前置：判断这个人有没有历史未付车辆
        if (!($parking_record_one = $parking_record->where(["parking_record_car_number" => $param["car_number"], "parking_record_pay_state" => ["neq", 100]])->find())) {
            return ["code" => 0, "message" => "该手机号没有临时车信息", "data" => ""];
        }

        $param["user_token"]     = $buyer_open_id;
        $parking_channel_one     = db("parking_channel")->where(["parking_channel_user_id" => $user_id])->find();
        $param["out_channel_id"] = $parking_channel_one["parking_channel_uuid"];
        $fd                      = $parking_channel_one["parking_channel_fd"];

        $pushRs = $this->pushtoclient($fd, $param);
        if ($pushRs["code"] == 1) {
            return $pushRs;
        } else {
            return ["code" => 0, "message" => $pushRs["message"], "data" => $pushRs["data"]];
        }

    }

    /**
     * 请求获取车辆费用
     * @return [type] [description]
     */
    public function getparkingfee($parking_id)
    {
        $parking_record_one = db("parking_record")->where(["parking_record_id" => $parking_id])->find();
        if (!$parking_record_one) {
            return ["code" => 0, "message" => "该订单不存在", "data" => ""];
        }

        $param["car_number"] = $parking_record_one["parking_record_car_number"];
        $param["order_id"]   = $parking_record_one["parking_record_order_id"];
        $param["query_time"] = time();
        $param["apiname"]    = "getparkingfee";

        $fd = db("parking_channel")->where(["parking_channel_id" => $parking_record_one["parking_record_in_channel_id"]])->value("parking_channel_fd");

        //return ["code" => 0, "message" => $param, "data" => $fd];
        $pushRs = $this->pushtoclient($fd, $param);
        if ($pushRs["code"] == 1) {
            return $pushRs;
        } else {
            return ["code" => 0, "message" => $pushRs["message"], "data" => $pushRs["data"]];
        }
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
        switch ($ParkingRecordOne->parking_record_pay_type) {
//请求的支付类型(offlinepay、facepay)
            //offlinepay：wallet（电子钱包/余额）,free（免费放行）,monthuser（月卡会员）,noconfirmpayment（无感支付）
            //facepay：facepay（微信，支付宝或扫码枪）
            case 'offlinepay':
                //如果是内部会员
                //如果是包月会员，直接放行
                //如果不是包月，且电子钱包余额充足
                //如果不是包月，且电子钱包余额不足，回复提醒发起支付宝、微信支付
                //__如果是内部会员
                //如果不是内部会员，发去支付宝、微信无感支付（无感失败，发起单面付）
                return ["code" => 0, "message" => "自动支付失败，请转付款码", "data" => ["state" => 400]];
                //假装无感支付成功
                $ParkingRecordOne->parking_record_real_pay_type  = "noconfirmpayment";
                $ParkingRecordOne->parking_record_real_pay_total = $ParkingRecordOne->parking_record_total;
                $ParkingRecordOne->parking_record_real_pay_time  = time();
                $ParkingRecordOne->parking_record_pay_state      = 100;
                $ParkingRecordOne->save();
                //__假装无感支付成功
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
                    if ($parking_store_id) {
                        //新增收费账号
                        $user_mobile = time() . mt_rand(10, 99);
                        $newData     = [
                            'user_mobile'      => $user_mobile,
                            'user_realname'    => $parking_channel_one->parking_channel_uuid . "停车场收费账号",
                            'user_store_id'    => $parking_store_id,
                            'user_refund_auth' => 0,
                            'user_token'       => md5($user_mobile . mt_rand(9999999, 99999999) . $parking_store_id),
                            'user_active'      => 1,
                            'user_role'        => 2,
                            'user_play_reward' => 0,
                            'user_addtime'     => time(),
                        ];
                        $user_id = db("user")->insertGetId($newData);
                        $parking_channel->isUpdate(true)->save(["parking_channel_user_id" => $user_id], ["parking_channel_id" => $ParkingRecordOne->parking_record_out_channel_id]);
                    } else {
                        trace("操作顺序错误：请在“经营场地”绑定当前的停车ID:" . $parking_channel_one->parking_channel_parking_id, "debug");
                    }
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
     * 订单状态被改变了才触发
     * 由order orderStatusChange() 同步调用
     * @param  string $value [description]
     * @return [type]        [description]
     */
    public function parkingRecordStateChange($parking_record_id)
    {
        $orderOneObject = model("order")->where(["order_other_sale_order_num" => $parking_record_id])->find();
        //同步修改停车记录
        $parkingRecordupdateData["parking_record_pay_state"]      = $orderOneObject->order_status;
        $parkingRecordupdateData["parking_record_real_pay_time"]  = strtotime($orderOneObject->order_pay_time);
        $parkingRecordupdateData["parking_record_real_pay_total"] = $orderOneObject->order_pay_realprice;
        $this->isUpdate(true)->save($parkingRecordupdateData, ["parking_record_id" => $orderOneObject->order_other_sale_order_num]);
        //如果是异步更新的，就向client推送一条记录
        if ((db("order_pay_log")->where(["order_pay_log_order_id" => $orderOneObject->order_id, "order_pay_log_status" => $orderOneObject->order_status])->order("order_pay_log_status")->value("order_pay_log_from")) == "asynchronous") {
            //要发送的数据
            $rsdata["apiname"] = "payresult";
            $rsdata["data"]    = $this->payStateRS($this->where(["parking_record_id" => $parking_record_id])->order("parking_record_id desc")->find());
            //__要发送的数据
            //查通道编号
            $parking_record_out_channel_id = db("parking_record")->where(["parking_record_id" => $parking_record_id])->value("parking_record_out_channel_id");
            //查通道对应的tcp socetk 的 fd
            $fd = db("parking_channel")->where(["parking_channel_id" => $parking_record_out_channel_id])->value("parking_channel_fd");
            $this->pushtoclient($fd, ($rsdata));
        } else {
            //同步的，已经有数据同步返回，不处理
        }
    }
    /**
     * 推送消息,自带数据签名
     * @param  [type] $fd        [目标fd]
     * @param  [type] $DataArray [传递的数据数组]
     * @return [type]            [description]
     */
    public function pushtoclient($fd, $DataArray)
    {
        $client = new \swoole_client(SWOOLE_SOCK_TCP);
        if (!$client->connect('127.0.0.1', config("swoole_port"))) {
            trace("ParkingRecord.php:connect failed. Error: {$client->errCode}\n", "error");
            die();
        }
        $client->recv();
        $sendData["data"]    = $DataArray;
        $sendData["target"]  = $fd;
        $sendData["apiname"] = "pushtoclient";
        $client->send(model("tcp")->datasign($sendData));
        $rs = $client->recv();
        $client->close();
        if ($rs) {
            $rs = json_decode($rs, 1);
            if ($rs["code"] == 1) {
                return ["code" => 1, "message" => "发送成功", "data" => ""];
            } else {
                return $rs;
            }
        } else {
            return ["code" => 0, "message" => $rs, "data" => ""];
        }

    }

    /**
     * 给骏鹏服务器下行推送
     * @param  string $apiName [接口名称]
     * @param  [type] $param   [数据集]
     * @return [type]          [description]
     */
    /*public function push2JunPeng($api_name = "", $param = [])
    {
    $url                  = config("carpark_junpeng_url");
    $PostData             = [];
    $PostData["time"]     = time();
    $PostData["api_name"] = $api_name;
    switch ($api_name) {
    case 'no_car_number_in': //无牌车入场
    $PostData["car_number"]    = $param["car_number"] ?: ("临" . time() . mt_rand(10, 99));
    $PostData["user_token"]    = $param["user_token"];
    $PostData["park_id"]       = $param["park_id"];
    $PostData["in_channel_id"] = $param["in_channel_id"];
    $PostData["in_time"]       = time();
    break;
    case 'no_car_number_out': //无牌车出场
    $PostData["car_number"]     = $param["car_number"];
    $PostData["user_token"]     = $param["user_token"];
    $PostData["park_id"]        = $param["park_id"];
    $PostData["in_channel_id"]  = $param["in_channel_id"];
    $PostData["in_time"]        = $param["in_time"];
    $PostData["out_channel_id"] = $param["out_channel_id"];
    $PostData["in_time"]        = $param["in_time"];
    $PostData["out_time"]       = time();
    break;
    case 'getparkingfee': //获取某车的停车费，异步返回数据
    $PostData["car_number"] = $param["car_number"];
    $PostData["order_id"]   = $param["order_id"];
    $PostData["park_id"]    = $param["park_id"];
    $PostData["query_time"] = time();
    break;
    case 'payresult': //支付结果通知（异步通知）
    $order_id       = $param["order_id"];
    $ParkingRecord  = $this;
    $theNewOne      = $ParkingRecord->where(["parking_record_order_id" => $order_id])->order("parking_record_id desc")->find();
    $prrs           = $ParkingRecord->payStateRS($theNewOne);
    $PostData["rs"] = $prrs;
    break;
    default:
    # code...
    break;
    }
    //数据加密
    $server_sign      = strtolower(publicRequestjiami($PostData, config("carpark_token")));
    $PostData["sign"] = $server_sign;
    //__数据加密
    //数据推送
    $rsHtml = httpsPost($url, $PostData);
    if (!$rsHtml) {
    return ["code" => 0, "message" => "对方服务器没有返回任何数据", "data" => $rsHtml];
    } else {
    $rsArray = json_decode($rsHtml, 1);
    if (!isset($rsArray["code"]) || !isset($rsArray["message"]) || !isset($rsArray["data"])) {
    return ["code" => 0, "message" => "对方服务器返回数据格式错误", "data" => $rsHtml];
    } else {
    return $rsArray;
    }
    }
    //__数据推送
    }*/
    /**
     * 统一返回支付状态
     * @param  [type] $ParkingRecordOne [原有的数据集]
     * @param  [type] $changeDataArray  [新的数据集]
     * @return [type]                   [array]
     */
    public function payStateRS($ParkingRecordOne, $changeDataArray = [], $message = "")
    {
        $rsData["state"]         = $ParkingRecordOne->parking_record_pay_state;
        $rsData["pay_type"]      = $ParkingRecordOne->parking_record_real_pay_type;
        $rsData["real_pay"]      = $ParkingRecordOne->parking_record_real_pay_total;
        $rsData["order_id"]      = $ParkingRecordOne->parking_record_order_id;
        $rsData["car_number"]    = $ParkingRecordOne->parking_record_car_number;
        $rsData["reduce_amount"] = $ParkingRecordOne->parking_record_reduce_amount;
        $rsData["reduce_remark"] = $ParkingRecordOne->parking_record_reduce_remark;
        $rsData["pay_time"]      = $ParkingRecordOne->parking_record_real_pay_time;
        $rsData["open_door"]     = 0;
        $rsData["pay_type_info"] = "";
        if ($changeDataArray) {
            if (isset($changeDataArray["state"])) {
                $rsData["state"] = $changeDataArray["state"];
            }
            if (isset($changeDataArray["pay_type"])) {
                $rsData["pay_type"] = $changeDataArray["pay_type"];
            }
            if (isset($changeDataArray["real_pay"])) {
                $rsData["real_pay"] = $changeDataArray["real_pay"];
            }
            if (isset($changeDataArray["order_id"])) {
                $rsData["order_id"] = $changeDataArray["order_id"];
            }
            if (isset($changeDataArray["car_number"])) {
                $rsData["car_number"] = $changeDataArray["car_number"];
            }
            if (isset($changeDataArray["reduce_amount"])) {
                $rsData["reduce_amount"] = $changeDataArray["reduce_amount"];
            }
            if (isset($changeDataArray["reduce_remark"])) {
                $rsData["reduce_remark"] = $changeDataArray["reduce_remark"];
            }
            if (isset($changeDataArray["pay_time"])) {
                $rsData["pay_time"] = $changeDataArray["pay_time"];
            }
            if (isset($changeDataArray["open_door"])) {
                $rsData["open_door"] = $changeDataArray["open_door"];
            }
            if (isset($changeDataArray["pay_type_info"])) {
                $rsData["pay_type_info"] = $changeDataArray["pay_type_info"];
            }
        }
        $thismessage = "";
        switch ($ParkingRecordOne->parking_record_pay_state) {
            case 100:
                $thismessage             = "支付成功";
                $rsData["pay_type_info"] = "网络支付";
                $rsData["open_door"]     = 1;
                break;
            case 400:
                $thismessage = "支付失败，已关闭";
                break;
            case 600:
                if ($ParkingRecordOne->parking_record_real_pay_type == "facepay") {
                    $thismessage = "等待支付完成";
                }
                break;
            default:
                # code...
                break;
        }
        $code        = ($ParkingRecordOne->parking_record_pay_state == 100 ? 1 : 0);
        $thismessage = $message ? $message : $thismessage;
        return ["code" => $code, "message" => $thismessage, "data" => $rsData];
    }
}
