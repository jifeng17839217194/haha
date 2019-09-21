<?php
namespace app\common\model;

use think\Model;

class ShopData extends Model
{
    protected $type = [
        'shop_data_master_id_images' => 'array',
        'shop_data_store_images' => 'array',
        'shop_data_other_images' => 'array',
        //'user_last_logintime' => 'timestamp',
    ];
    protected $autoWriteTimestamp = false;

    //关联
    public function profile()
    {
        //return $this->belongsTo('Province','city_province_id')->field('province_name');
    }
}
