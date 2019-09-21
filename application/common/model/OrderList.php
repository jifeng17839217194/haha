<?php
namespace app\common\model;

use think\Model;

class OrderList extends Model
{
    protected $type = [
        'order_addtime' => 'timestamp',
        'order_paytime' => 'timestamp',
        'order_wuliu_sendtime' => 'timestamp',
        'order_wuliu_gettime' => 'timestamp',
        
    ];

    protected $autoWriteTimestamp = false;

    
}
