<?php
namespace app\common\model;

use think\Model;

class OrderPayLog extends Model
{
    protected $type = [
        'refund_time' => 'timestamp',//订单详情api接口用到这个字段，放这里转义下
        'order_pay_log_addtime' => 'timestamp',
    ];
    protected $autoWriteTimestamp = false;

    //关联
    public function profile()
    {
        //return $this->belongsTo('Province','city_province_id')->field('province_name');
    }
}
