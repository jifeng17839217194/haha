<?php
namespace app\common\model;

use think\Model;
use think\Log;
use think\Db;
/**
* 停车会员表
* By
* create:2018-8-18
*/
class Member extends Model
{
	//设置字段类型
	protected $type = [
        'member_addtime'=> 'timestamp',
    ];

    //设置时间的方式
    protected $autoWriteTimestamp = false;

    //设置自动的字段
    protected $insert=['member_addtime'];

    //设置默认的值
    public function setMemberAddtimeAttr()
    {
    	return time();
    }
    /*
    *会员列表
    *By
    *create :2018-9-10
    */
    public function getlist($paramers)
    {
        if(empty($paramers['user_id'])) return ['code'=>0,'msg'=>'未提交当前用户','data'=>''];
        $user_info=Db::name('user')->find($paramers['user_id']);
        $where=[];
        if($user_info['user_role']==0){
            $store_shop_id=Db::name('store')->where('store_id',$user_info['user_store_id'])->value('store_shop_id');
            $where['a.record_store_id']=['in',Db::name('store')->where("store_shop_id",$store_shop_id)->column('store_id')];
        }else{
            $where['a.record_store_id']=$user_info['user_store_id'];
        }
        $where['record_status']=1;
        $list=Db::name("member_car_record")->alias("a")->join('__MEMBER__ b','a.record_member_id=b.member_id','right')->field("distinct a.record_member_id,b.*")->where($where)->paginate($paramers['per_page']);
        trace("会员列表sql:".Db::getlastsql());
        return ['code'=>1,'msg'=>'','data'=>$list];
    }
    /*
    *获得会员信息
    *By
    *create:2018-8-20
    */
    public function get_info($member_id)
    {   if(empty($member_id)){
         return [];
        }
        $member_info=Db::name('member')->where('member_id',$member_id)->field('member_nickname,member_realname')->find($member_id);
        $member_name=empty($member_info['member_realname'])?$member_info['member_nickname']:$member_info['member_realname'];
        $member_car_number_plate=Db::name('member_car')->where('car_member_id',$member_id)->value("car_number_plate");
        return ['member_name'=>$member_name,'member_car_number'=>$member_car_number_plate];
    }
    /*
	*获得真实姓名
	*By
	*create:2018-8-18
    */
    public function get_realname($member_id)
    {
    	if(empty($member_id)) return '';
    	$member_realname=$this->where('member_id',$member_id)->value('member_realname');
    	trace($this->getlastsql());
    	return $member_realname;
    }
    /*
	*保存真实姓名
	*By
	*create:2018-8-18
    */
    public function realname_save($member_id,$member_realname)
    {
    	if(empty($member_id)) return ['code'=>0,'msg'=>'会员号不能为空!','data'=>''];
    	$rs=$this->where('member_id',$member_id)->setField('member_realname',$member_realname);
    	if($rs!==false){
    		return ['code'=>1,'msg'=>'','data'=>''];
    	}else{
    		return ['code'=>0,'msg'=>'修改错误:'.$this->getError(),'data'=>''];
    	}
    }
    /*
	*获得手机号
	*By
	*create:2018-8-18
    */
    public function get_tel($member_id)
    {
    	if(empty($member_id)) return '';
    	$member_tel=$this->where('member_id',$member_id)->value('member_tel');
    	trace($this->getlastsql());
    	return $member_tel;
    }
    /*
	*保存手机号
	*By
	*create:2018-8-18
    */
    public function tel_save($member_id,$member_tel)
    {
    	if(empty($member_id)) return ['code'=>0,'msg'=>'会员号不能为空!','data'=>''];
    	if(!empty($member_tel)&&(!preg_match("/1[0-9]{10}/i", $member_tel))){
    		return ['code'=>0,'msg'=>'手机号格式不正确！','data'=>''];
    	}
    	$rs=$this->where('member_id',$member_id)->setField('member_tel',$member_tel);
    	if($rs!==false){
    		return ['code'=>1,'msg'=>'','data'=>''];
    	}else{
    		return ['code'=>0,'msg'=>'修改错误:'.$this->getError(),'data'=>''];
    	}
    }
    /*
	*获得公司信息
	*By
	*create:2018-8-18
    */
    public function get_company_info($member_id)
    {
    	if(empty($member_id)) return [];
    	$company_info=$this->field("member_company_name,member_company_address")->find($member_id);
    	trace($this->getlastsql());
    	return $company_info;
    }
    /*
	*设置公司信息
	*By
	*create:2018-8-18
    */
    public function set_company($member_id,$company_name,$company_address)
    {
    	if(empty($member_id)) return ['code'=>0,'msg'=>'会员号不能为空！','data'=>''];
    	$data_save=[
    		'member_company_name'=>$company_name,
    		'member_company_address'=>$company_address
    		];
    	$rs=$this->save($data_save,['member_id'=>$member_id]);
    	if($rs!==false){
    		return ['code'=>1,'msg'=>'','data'=>''];
    	}else{
    		return ['code'=>0,'msg'=>'保存错误：'.$this->getError(),'data'=>''];
    	}
    }

}