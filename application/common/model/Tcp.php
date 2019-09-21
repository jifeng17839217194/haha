<?php
namespace app\common\model;
use think\Model;
class Tcp extends Model
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
     * 对tcp服务器的任务分发（来自保安亭的电脑，直接TCP链接，未做任何加密）
     * @param  [type] $serv      [TCP 服务对象]
     * @param  [type] $JSONArray [传递的数据]
     * @return [type]            [description]
     */
    public function dotaskv2($serv,$fd,$from_id,$JSONArray)
    {
        //签名检测
        //$signrs=$this->checkSign($JSONArray);
        //if(!$signrs["code"])return $signrs;
        $apiname = $JSONArray["apiname"];
        $parking_record = model("parking_record");
        switch ($apiname) {//根据不同的apiname转到不同位置
            /*case 'carin'://推送车辆进场信息
                return $parking_record->carin($JSONArray,[$serv,$fd,$from_id]);
                break;
            case 'carout'://推送车辆出场信息
                return $parking_record->carout($JSONArray,[$serv,$fd,$from_id]);
                break;
            case 'pushparkingfee'://停车费查询结果推送
                return $parking_record->pushparkingfee($JSONArray,[$serv,$fd,$from_id]);
                break;
            case 'appinitializes'://初始化推送
                return $parking_record->appinitializes($JSONArray,[$serv,$fd,$from_id]);
                break;
            case 'pushtoclient'://给客户端推送
                return $JSONArray;
                break;*/
            default:
                return ["code"=>0,"message"=>"unknow apiname <".$apiname.">","data"=>""];
                break;
        }
    }

    //给参数加密 内部推送用
    public function datasign($dataArray = [])
    {
        $dataArray["timestamp"] = time();
        $dataArray["sign"]      = strtolower(publicRequestjiami($dataArray, config("carpark_token")));
        return json_encode($dataArray);
    }

    public function checkSign($JSONArray)
    {
        $token = config("carpark_token");
        $postdata = $JSONArray;
        if(!isset($postdata["timestamp"]))
        {
            return ["code"=>0,"message"=>"Parameter must have timestamp field","data"=>""];
        }
        else
        {
            if(abs(time() - $postdata["timestamp"])>60*10)//10分钟的误差
            {
                return ["code"=>0,"message"=>"timestamp无效,超过可允许访问时间","data"=>""];
            }
            else
            {
                if(isset($postdata["sign"]))
                {
                    if(isset($postdata["apiname"]))
                    {
                        
                        $guest_sign = strtolower($postdata["sign"]);
                        $server_sign = strtolower(publicRequestjiami($postdata,$token));
                        if($guest_sign!=$server_sign)
                        {
                            return ["code"=>0,"message"=>"sign error","data"=>""];
                        }
                        else
                        {
                            return ["code"=>1,"message"=>"","data"=>""];
                        }
                    }
                    else
                    {
                        return ["code"=>0,"message"=>"Parameter must have apiname field","data"=>""];
                    }
                }
                else
                {
                    return ["code"=>0,"message"=>"Parameter must have sign field","data"=>""];
                }
            }
        }
    }
}