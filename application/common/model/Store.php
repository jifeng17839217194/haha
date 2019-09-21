<?php

namespace app\common\model;



use think\Model;

use think\Log;



class Store extends Model

{

    protected $PerPage=2;

    protected $type = [

        //'sysconfig' => 'object',

        'store_addtime' => 'timestamp',

    ];

    protected $autoWriteTimestamp = false;



    /**

     * 店铺级别的登入

     * @param  [type] $user_name     [description]

     * @param  [type] $user_password [description]

     * @return [type]                [description]

     */

    public function login($user_name, $user_password)

    {

        $user_rs = model("user")->login($user_name, $user_password, true); //账号登入

        if ($user_rs["code"] == 1) {

            //判断

            $userOne = $user_rs["data"];

            if ($otherOne = model("store")->join("__SHOP__", "shop_id=store_shop_id")->where(["store_id" => $userOne->user_store_id])->find()) {

                if ($otherOne->shop_active != 1) {

                    return ["code" => 0, "message" => "该商家已被禁用", "data" => ""];

                }

                $user_rs["data"]["store_name"]                  = $otherOne->store_name;
                $user_rs["data"]["store_open_reward"]                  = $otherOne->store_open_reward;
                $user_rs["data"]["store_open_funds_authorized"] = $otherOne->store_open_funds_authorized;

                return $user_rs;

            } else {

                return ["code" => 0, "message" => "该账号未绑定店铺", "data" => ""];

            }

        } else {

            return $user_rs;

        }

    }



    //关联

    public function profile()

    {

        //return $this->belongsTo('Province','city_province_id')->field('province_name');

    }

     /*

    * 附近停车场列表

    * By

    * create:2018-8-17

    * @param  [type] $lng     [description]

    * @param  [type] $lat     [description]

    */

    public function get_park_list($lng,$lat,$PageIndex=1)

    {

        $where=[];

        $where['store_is_park']=1;

        $PageStart=$this->PerPage*($PageIndex-1);

        $list=$this->alias("a")->join('__USER__ b','a.store_id=b.user_store_id')->field("DISTINCT a.store_id,a.store_name,a.store_address,a.store_temporary_parking_fee,a.store_monthly_fee,a.store_parking_lat,a.store_parking_lng,ROUND(6378.138*2*ASIN(SQRT(POW(SIN(($lat*PI()/180-a.store_parking_lat*PI()/180)/2),2)+COS($lat*PI()/180)*COS(a.store_parking_lat*PI()/180)*POW(SIN(($lng*PI()/180-a.store_parking_lng*PI()/180)/2),2)))*1000) as juli")->where($where)->where("ROUND(6378.138*2*ASIN(SQRT(POW(SIN(($lat*PI()/180-a.store_parking_lat*PI()/180)/2),2)+COS($lat*PI()/180)*COS(a.store_parking_lat*PI()/180)*POW(SIN(($lng*PI()/180-a.store_parking_lng*PI()/180)/2),2))))<5")->order('juli asc')->limit($PageStart,$this->PerPage)->select();//5公里内的停车场

        trace('停车场sql：'.$this->getlastsql());

        $list_data=[];

        $i=$PageStart;

        foreach ($list as $v) {



            if($v->juli>=1000)

            {

                $v->juli=round(bcdiv($v->juli, 1000),2).'千米';

            }else{

                $v->juli=$v->juli.'米';

            }

            $v->num=++$i;

            $list_data[]=$v;

        }

        return $list_data;

    }

    /*

    *获得停车场名字

    *By

    *create:2018-8-22

    */

    public function get_store_info($parking_id)

    {

        if(empty($parking_id)) return '';

        $park_info=$this->where("store_id",$parking_id)->field('store_id,store_name,store_monthly_fee,store_parking_content,store_parking_lng,store_parking_lat')->find();

        return $park_info; 

    }

}

