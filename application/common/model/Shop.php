<?php
namespace app\common\model;

use think\Model;

class Shop extends Model
{
    protected $type = [
        //'sysconfig' => 'object',
        'shop_addtime' => 'timestamp',
    ];
    protected $autoWriteTimestamp = false;

    /**
     * 获得店铺列表
     * @param  [type] $whereArray [description]
     * @return [type]             [description]
     */
    public function getShopList($whereArray)
    {
        //$this->where($whereArray)->select();
    }

    /**
     * 创建一个简单的商户
     * @return [object] [shop one]
     */
    public function baseadd($shop_name = "", $shop_agent_id = 0)
    {
        $newData["shop_name"]     = $shop_name;
        $newData["shop_addtime"]  = time();
        $newData["shop_active"]   = 1;
        $newData["shop_agent_id"] = $shop_agent_id;
        $newData["shop_id_token"] = md5($shop_name . getMillisecond()); //初始化一个 支付宝授权后返回判断商户的唯一识别码（相当于ID，只是更复杂）
        $this->data($newData)->save();
        return $this;
    }
}
