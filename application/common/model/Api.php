<?php
namespace app\common\model;

use think\Model;

class Api extends Model
{
    protected $type = [
        //'sysconfig' => 'object',
        'api_updatetime' => 'timestamp',
        'api_addtime' => 'timestamp',
    ];
    protected $autoWriteTimestamp = false;

    //关联
    public function profile()
    {
        //return $this->belongsTo('Province','city_province_id')->field('province_name');
    }
}
