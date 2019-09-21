<?php
namespace app\common\model;

use think\Model;
use think\Log;
/**
* 会员车牌
*/
class MemberCar extends Model
{
	//设置字段类型
	protected $type = [
        'car_addtime'=> 'timestamp',
    ];

    //设置时间的方式
    protected $autoWriteTimestamp = false;

    //设置自动的字段
    protected $insert=['car_addtime'];

    //设置默认的值
    public function setCarAddtimeAttr()
    {
    	return time();
    }

	/*
	*增加车牌
	*By
	*create:2018-8-20
    */
    public function add($member_id,$car_number)
    {
		if(empty($member_id)&&empty($car_number)) return ['code'=>0,'msg'=>'车牌信息有误！','data'=>''];
		$count=$this->where('car_member_id',$member_id)->where('car_number_plate',$car_number)->count(0);
		if($count){
			return ['code'=>0,'msg'=>'此车牌号已绑定！','data'=>''];
		}
		$car_count=$this->where("car_member_id",$member_id)->count(0);
		if($car_count){//限制 只绑定一个，但是其他的也提示成功
			return ['code'=>1,'msg'=>'绑定成功！','data'=>''];
		}
		$rs=$this->save(['car_member_id'=>$member_id,'car_number_plate'=>$car_number]);
		trace("新增车牌:".json_encode($rs));
		if($rs){
			return ['code'=>1,'msg'=>'绑定成功！','data'=>''];
		}else{
			return ['code'=>0,'msg'=>'','data'=>''];
		}
    }
    /*
    *车牌列表
    *By
    *create:2018-8-20     *
    */
    public function get_list($member_id)
    {
    	if(empty($member_id)) return ['code'=>0,'msg'=>'会员信息不正确！','data'=>''];
    	$list=$this->where('car_member_id',$member_id)->select();
    	return ['code'=>1,'msg'=>'','data'=>$list];
    }
    /*
	*车牌删除
	*By
	*create:2018-8-20
    */
    public function del($member_id,$car_id)
    {
    	if(empty($member_id)) return ['code'=>0,'msg'=>'会员信息不正确！','data'=>''];
    	$rs=$this->destroy($car_id);
    	trace("车牌删除：".$this->getlastsql());
    	if($rs!==false){
    		return ['code'=>1,'msg'=>'删除成功！','data'=>''];
    	}else{
    		return ['code'=>0,'msg'=>'删除错误：'.$this->getError(),'data'=>''];
    	}
    }
}