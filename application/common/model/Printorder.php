<?php
namespace app\common\model;

use think\Model;
/**
 * 打印模块
 */
class Printorder extends Model
{
    protected $type = [
        //'sysconfig' => 'object',
        //'user_last_logintime' => 'timestamp',
    ];
    protected $autoWriteTimestamp = false;

    /**
     * 打印模块
     * @param  string $value [description]
     * @return [type]        [description]
     */
    public function printTmp($order_num)
    {
        $printArrayData=$this->getPrintDataFromOrder($order_num);
        return implode("\n",$printArrayData);//这里的'\n'是给C#打印里面的换行
    }

    /**
     * 根据订单$order_num，获取要打印的信息
     * @param  [type] $order_num [description]
     * @return [type]            [description]
     */
    public function getPrintDataFromOrder($order_num)
    {
        $order = model("order");
        $store = model("store");
        $orderOne=$order->where(["order_num"=>$order_num])->field(false)->find();
        $storeOne = $store->where(["store_id"=>$orderOne->order_store_id])->field(false)->find();

        $printdata[]=$storeOne->store_name;//店铺名称
        $printdata[]="--------------------------------------------------";
        $printdata[]="消费金额:¥".$orderOne->order_total_amount;
        $printdata[]="--------------------------------------------------";
        $printdata[]="实际支付:¥".$orderOne->order_pay_realprice;
        $printdata[]="消费日期:".(strtotime($orderOne->order_addtime)==0?"未支付":$orderOne->order_addtime);
        $printdata[]="支付渠道:".model("pay")->payWayTranslate($orderOne->order_channel_id,true)["cn"];
        $printdata[]="收银编号:".$orderOne->order_user_id;
        $printdata[]="联系电话:".$storeOne->store_mobile;
        $printdata[]=$storeOne->store_address;//店铺地址
        $printdata[]="订 单 号:";
        $printdata[]=$orderOne->order_num;
        return $printdata;
    }
}
