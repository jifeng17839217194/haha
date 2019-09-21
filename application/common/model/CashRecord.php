<?php
namespace app\common\model;
use think\Model;
class CashRecord extends Model
{
    protected $type = [
        'cashrecord_addtime' => 'timestamp',
        //'user_last_logintime' => 'timestamp',
    ];
    protected $autoWriteTimestamp = false;

    //  定义全局的查询范围
    protected function base($query)
    {
        
    }


    //新增流水记录
    public function addOne($userId,$data)
    {
        $data["cashrecord_addtime"]=time();
        $data["cashrecord_ip"]=request()->ip();
        $data["cashrecord_obj_id"]=$userId;
        //dump($data);
        $this->data($data)->isUpdate(false)->save();
        //echo $this->getlastsql();
        //echo "<hr>";
        return $this->cashrecord_id;
    }
}
