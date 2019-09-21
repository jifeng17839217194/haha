<?php
namespace app\common\model;

use think\Model;

class Shop_Attr extends Model
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
}
