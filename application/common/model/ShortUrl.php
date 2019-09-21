<?php
namespace app\common\model;

use think\Model;

class ShortUrl extends Model
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

    public function geturl($short_url_key)
    {
        return request()->domain()."/".$short_url_key;//使用TP的路由功能
    }

    /**
     * 生成唯一的6位地址码,大小字母、小写字母、数字组成；必需跟TP的路由规则一致[a-zA-Z0-9]{6}
     * @param  string $value [description]
     * @return [type]        [description]
     */
    public function getkeyval()
    {
        return $this->getRandChar(6);
    }


    //随机字符串
    public function getRandChar($length)
    {
        $str    = null;
        $strPol = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz";
        $max    = strlen($strPol) - 1;
        for ($i = 0; $i < $length; $i++) {
            $str .= $strPol[rand(0, $max)]; //rand($min,$max)生成介于min和max两个数之间的一个随机整数
        }
        return $str;
    }

    /**
     * [收银员绑定聚合码]
     * @param  [type] $user_id     [description]
     * @param  [type] $qrcodevalue [description]
     * @return [type]              [description]
     */
    public function bindqrcode($user_id,$qrcodevalue)
    {
        //判断聚合码是否有效
        $where_check["short_url_key"]=$qrcodevalue;
        if($shortUrlOne=$this->where($where_check)->find())
        {
            if($shortUrlOne->short_url_active_addtime==0)
            {
                $shortUrlOne->short_url_action="payqrcode";
                $shortUrlOne->short_url_active_addtime=time();
                $shortUrlOne->short_url_data = $user_id;
                $shortUrlOne->save();
                return ["code"=>1,"message"=>"绑定成功！","data"=>""];   
            }
            else
            {
                return ["code"=>1,"message"=>"错误，不可重复绑定","data"=>""];      
            }
        }
        else
        {
            return ["code"=>0,"message"=>"错误，聚合码不存在","data"=>""];
        }
    }
}
