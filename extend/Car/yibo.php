<?php
/**
 * 易泊外挂版本
 */
class Yibo
{

    public function __construct()
    {

    }

    public function access($data)
    {
        

        //[
        //    "car_access_out_access_id"  => $autoId,
        //    "car_access_number_plate"   => $plate_id,
        //    "car_access_out_port_id"    => $port_id,
        //    "car_access_out_time"       => is_numeric($access_time) ?: strtotime($access_time),
        //    "car_access_color"          => $plate_color,
        //    "car_access_out_cartype"    => $cartype_id,
        //    "car_access_out_parking_id" => $porking_id,
        //    "car_access_addtime"        => time(),
        //];

        //判断该停车记录__停车场是否存在
        if($porkingOne=db("parking")->where(["parking_uuid"=>$data["car_access_out_parking_id"]])->find())
        {
        	// $param_array = ["access_id" => $data["car_access_out_access_id"], "access_time" => $data["car_access_out_access_id"], "cario_id" => "105", "park_id" => "25404241023598608", "park_name" => "杭州专注科技停车场", "parkingName" => "智能车牌识别停车场", "parking_spaceNum" => "1139", "plate_color" => "蓝", "plate_id" => "浙AK219M", "plate_state" => "正常", "plate_subtype" => "", "plate_type" => "临时车", "port_id" => "1", "signature" => "2d96882fe5f7aa15f4805fc43bf5b2085259541c09ab306757403a762a29b52e", "timestamp" => "1531121002"];
        	// model("ParkingRecord")->carin($param_array);

        }
        else
        {
        	if($data["car_access_out_cartype"]==3)db("car_access")->insert($data);//只记录临时车
        }

        
    }
}
