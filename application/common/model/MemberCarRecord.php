<?php
namespace app\common\model;
use think\Model;
use think\Log;
use think\Db;
/**
* 会员包月表
*By
* create:2018-8-20
*/
class MemberCarRecord extends Model
{
    //设置字段类型
    protected $type = [
        'record_addtime'=> 'timestamp',
        'record_start_time'=>'timestamp:Y-m-d',
        'record_end_time'=>'timestamp:Y-m-d',
    ];
    //设置时间的方式
    protected $autoWriteTimestamp = false;
    //设置自动的字段
    protected $insert=['record_addtime'];
    //设置默认的值
    public function setRecordAddtimeAttr()
    {
        return time();
    }
    /*
    *收银员包月列表
    *By
    *create:2018-9-11
    */
    public function user_get_list($parameters)
    {
        if(empty($parameters['user_id'])) return ['code'=>0,'msg'=>'收银员信息不正确！','data'=>''];
        $user_info=Db::name('user')->find($parameters['user_id']);
        
        $where=[];
        if($user_info['user_role']==0){
            $store_shop_id=Db::name('store')->where('store_id',$user_info['user_store_id'])->value('store_shop_id');
            $where['record_store_id']=['in',Db::name('store')->where("store_shop_id",$store_shop_id)->column('store_id')];
        }else{
            $where['record_store_id']=$user_info['user_store_id'];
        }
        $where['record_status']=1;
        $list=$this->alias("a")->join("__STORE__ b",'a.record_store_id=b.store_id')->where($where)->paginate($parameters['per_page']);
        trace("收银员包月列表sql:".Db::getlastsql());
        return ['code'=>1,'msg'=>'','data'=>$list];
    }
    /*
    *用户包月列表
    *By
    *create:2018-8-20
    */
    public function get_car_month_list($parameters)
    {
        if(empty($parameters['member_id'])) return ['code'=>0,'msg'=>'会员号不正确！','data'=>''];
        $where=array();
        $where['record_member_id']=$parameters['member_id'];
        $where['record_status']=1;
        $list=$this->field('record_car_id,record_car_number_plate,record_store_id,record_parking_name,record_parking_lng,record_parking_lat,record_parking_monthly_price,record_start_time,record_end_time')->where($where)->select();
        trace('用户包月列表sql：'.$this->getlastsql());
        $list_data=array();
        $current_day_time=strtotime(date("Y-m-d"));
        foreach ($list as $v) {
            if(strtotime($v->record_end_time)>=$current_day_time&&strtotime($v->record_start_time)<$current_day_time){
                $current_month_days=date('t');
                $current_day=date('d',time());
                $v->remaining_days=$current_month_days-$current_day+1;
            }elseif(strtotime($v->record_end_time)>$current_day_time&&strtotime($v->record_start_time)>$current_day_time){
                $v->remaining_days=date('t',strtotime($v->record_start_time));
            }   
            $list_data[]=$v;
        }
        return ['code'=>1,'msg'=>'','data'=>$list_data];
    }
    /*
    *所有包月列表
    *By
    *create date:2018-10-18
    */
    public function get_list($parameters)
    {
        $where=[];
        $where['record_status']=1;
        trace('get_list接受的参数:'.json_encode($parameters));
        if(!empty($parameters['car_month'])){
            $start_time=strtotime($parameters['car_month']." 00:00:00");
            $end_time=strtotime('+1 months',strtotime($parameters['car_month']." 00:00:00"));
            $where['a.record_end_time']=['between',"$start_time,$end_time"];
        }
        if(!empty($parameters['keyword'])){
            $where['a.record_car_number_plate']=['like','%'.$parameters['keyword'].'%'];
        }
        if(!empty($parameters['order_store_id'])){
            trace('接受的停车场ID：'.$parameters['order_store_id']);
            $where['a.record_store_id']=$parameters['order_store_id'];
        }elseif(!empty($parameters['order_shop_id'])){
            $where['b.store_shop_id']=$parameters['order_shop_id'];
        }
        $list=$this->alias("a")->join("__STORE__ b",'a.record_store_id=b.store_id')->where($where)->paginate(10);
        trace('总后台包月列表sql：'.Db::getlastsql());
        return ['code'=>1,'msg'=>'','data'=>$list];     
    }
    /*
    *查询车牌是否包月
    *By
    *create:2018-9-4
    */
    public function search_month($parameters)
    {
        if (empty($parameters)) return ["code"=>0,"message"=>"","data"=>""];
        $where=[];
        $current_time=time();
        $where['record_car_number_plate']=$parameters['car_number'];
        $where['record_store_id']=$parameters['parking_id'];
        $where['record_start_time']=['elt',$current_time];
        $where['record_end_time']=['EGT',$current_time];
        $where['record_status']=1;
        if($one=$this->where($where)->find()){
            return ["code"=>1,"message"=>"","data"=>["record_id"=>$one["record_id"]]];
        }else{
            return ["code"=>0,"message"=>"","data"=>""];
        }       
    }
    /*
    *创建订单
    *By
    *create:2018-8-23
    */
    public function add($parameters)
    {
        if(empty($parameters)) return ['code'=>0,'msg'=>'参数不能为空！','data'=>''];
        if($parameters['current_month']==1)
        {
            $start_time=date('Y-m-01', strtotime(date("Y-m-d")));
            $end_time=date('Y-m-d', strtotime("$start_time +1 month -1 day"));
        }else{
            $start_time=date('Y-m-01', strtotime('+1 month'));
            $end_time=date('Y-m-t', strtotime('+1 month'));
        }
        $park_info=model('store')->get_store_info($parameters['parking_id']);
        if(empty($park_info)){
            return ['code'=>0,'msg'=>'无此停车场信息！','data'=>''];
        }        
        $month_info=$this->where(['record_car_number_plate'=>$parameters['car_number'],'record_store_id'=>$parameters['parking_id'],'record_status'=>1])->order('record_id desc')->find();
        if(!empty($month_info)){
             if($month_info->record_status==1){
                return ['code'=>0,'msg'=>'此车已在此停车场包月！','data'=>''];
             }
        }
        $data_add=array(
            'record_car_number_plate'=>$parameters['car_number'],
            'record_car_id'=>0,
            'record_member_id'=>$parameters['member_id'],
            'record_store_id'=>$parameters['parking_id'],
            'record_parking_name'=>$park_info->store_name,
            'record_parking_lng'=>$park_info->store_parking_lng,
            'record_parking_lat'=>$park_info->store_parking_lat,
            'record_parking_monthly_price'=>$park_info->store_monthly_fee,
            'record_start_time'=>strtotime($start_time),
            'record_end_time'=>strtotime($end_time),            
            );
        $rs=$this->save($data_add);
        $record_id=$this->record_id;
        if($rs){
            return ['code'=>1,'msg'=>'','data'=>['record_id'=>$record_id]];
        }else{
            return ['code'=>0,'msg'=>'错误:'.$this->getError(),'data'=>''];
        }
    }
     /**
     * 获得车辆/订单金额（最新）
     * @param  [type] $paramArray ["parking_record_id"=>123,"car_number"=>"浙AD750C"]
     * @return [type]             [description] 返回了全字段的
     */
    public function getPrice($paramArray = [])
    {
        $where = [];
        if (isset($paramArray["record_car_number_plate"])) {
            $where["record_car_number_plate"] = $paramArray["record_car_number_plate"];
        }
        if (isset($paramArray["record_id"])) {
            $where["record_id"] = $paramArray["record_id"];
        }
        $record_one = db("member_car_record")->where($where)->order("record_id desc")->value("record_parking_monthly_price");
        if ($record_one>0) {
                return ["code" => 1, "message" => '', "data" => ["total_amount" => $record_one]];
        } else {
            return ["code" => 0, "message" => "没有该停车信息", "data" => ""];
        }
    }
    /*
    *修改停车状态
    *By
    *create :2018-8-30
    */
    public function carRecordStateChange($record_id)
    {
        $orderOneObject = model("order")->where(["order_other_sale_order_num" => $record_id, "order_create_where" => 'parking_month'])->order(["order_id" => "desc"])->find();
        trace('包月订单信息：'.json_encode($orderOneObject));
        //同步修改停车记录
        if($orderOneObject->order_status==100){
            $record_status=1;
        }else{
            $record_status=-1;
        }
        $parkingRecordupdateData["record_status"]      = $record_status;
        $parkingRecordupdateData["record_real_pay_time"]  = strtotime($orderOneObject->order_pay_time);
        $parkingRecordupdateData["record_real_pay_total"] = $orderOneObject->order_pay_realprice;
        $this->isUpdate(true)->save($parkingRecordupdateData, ["record_id" => $orderOneObject->order_other_sale_order_num]);
    }
}