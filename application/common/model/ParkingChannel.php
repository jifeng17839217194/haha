<?php
namespace app\common\model;

use think\Model;

class ParkingChannel extends Model
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
     * [channelBindUser 通道与收费员的绑定]
     * 前置：停车场的UUID已经与“经营场地”绑定了记录
     * @return [type] [description]
     */
    public function channelBindUser($parking_id)
    {

        if ($parkingOne = db("parking")->where(["parking_id" => $parking_id])->find()) {
            if ($parkingOne["parking_uuid"]) {
                //向停车场查询出入口的数据

                $sendData            = [];
                $sendData["uuid"] = $parkingOne["parking_uuid"];
                $sendData["from_compay"]="epapi";
                $Parkcommon =new \Parkcommon\Apiget();
                $rsArray=$Parkcommon->getPort($sendData);


                //{"error_des":"获取进出口id成功！","park_id":"25404241023598608","park_name":"杭州专注科技停车场","port":[{"centry_name":"岗亭入口","port_id":"1","port_name":"入口"},{"centry_name":"岗亭入口","port_id":"4","port_name":"出口"}],"ret_code":"1","signature":"53cfbe2b4a64c58d7b8aed7b886eefc9fae70e3f60c4d3f82440af56e010b9cc","timestamp":"1531107804"}
                {

                    if ($rsArray["code"]==1) {
                        foreach ($rsArray["data"]["park_list"] as $portOne) {//生成收费员账号
                            $isAddNewUser       = false;
                            $parking_channel_id = 0;
                            if (!$parking_channel_one = db("parking_channel")->where(["parking_channel_parking_id" => $parking_id, "parking_channel_uuid" => $portOne["port_id"]])->find()) {
                                $parking_channel_id = db("parking_channel")->insertGetId(["parking_channel_parking_id" => $parking_id, "parking_channel_uuid" => $portOne["port_id"], "parking_channel_brief" => $portOne["centry_name"] . "_" . $portOne["port_name"], "parking_channel_addtime" => time(),"parking_channel_in_or_out"=>$portOne["in_or_out"]]);
                                $isAddNewUser       = true;
                            } else {
                                $parking_channel_id = $parking_channel_one["parking_channel_id"];
                                //检测账号有没有被删除
                                if (!db("user")->where(["user_id" => $parking_channel_one["parking_channel_user_id"]])->find()) {
                                    $isAddNewUser = true;
                                }
                            }

                            if ($isAddNewUser) {
                                $newData = [
                                    'user_mobile'      => "",
                                    'user_realname'    => $portOne["centry_name"] . "_" . $portOne["port_name"],
                                    'user_store_id'    => $parkingOne["parking_store_id"],
                                    'user_refund_auth' => 0,
                                    'user_active'      => 1,
                                    'user_role'        => 2, //默认是最小权限的“收银员”
                                    'user_play_reward' => 0,
                                    'user_addtime'=>time()
                                ];

                                $user_id=db("user")->insertGetId($newData);
                                db("parking_channel")->where(["parking_channel_id" => $parking_channel_id])->update(["parking_channel_user_id"=>$user_id]);//关联
                            }
                        }
                        return ["code"=>1,"message"=>"","data"=>""];
                    }
                    else
                    {
                        return $rsArray;
                    }
                }

                //__向停车场查询出入口的数据
            }
            else
            {
                return ["code"=>0,"message"=>"未绑定计费uuid","data"=>""];
            }
        }
        else
        {
            return ["code"=>0,"message"=>$parking_id."不存在!","data"=>""];
        }
    }
}
