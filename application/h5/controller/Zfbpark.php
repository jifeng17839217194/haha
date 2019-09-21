<?php
namespace app\h5\controller;
use think\Controller;
use think\Request;
use think\Db;
use think\Session;
/**
* 支付宝包月
*/
class Zfbpark extends Controller
{	
	protected $base_url='http://ipay.iaapp.cn';
	public function _initialize()
	{
		$action_name=Request::instance()->action();	
		if($action_name!='get_one_step'&&$action_name!='zfb_get_back'){
			$from_url=$this->base_url.url($action_name);
			session('from_url',$from_url);
		}	
	}
	/*清空session*/
	public function session_clear()
	{
		Session::clear();
	}
	/*微信添加订单定时任务*/
	public function test_add_wx()
	{
		dump(model("Pay")->tradeQueryRequest('ZZ1538456473116859'));
	}
	/*生活号接入验证*/
	public function verfiy(){		
		import('Alipay.HttpRequst');
		import('Alipay.AopSdk');
		import('Alipay.Gateway');
		require('../extend/Alipay/config.php');
		import('Alipay.function',EXTEND_PATH,'.inc.php');
		header("Content-type: text/html; charset=utf-8");
		if (get_magic_quotes_gpc()) {
			foreach($_POST as $key => $value) {
				$_POST[$key] = stripslashes($value);
			}
			foreach($_GET as $key => $value) {
				$_GET[$key] = stripslashes($value);
			}
			foreach($_REQUEST as $key => $value) {
				$_REQUEST[$key] = stripslashes($value);
			}
		}
		trace('支付宝验证get：'.json_encode($_GET));
		trace('支付宝验证post：'.json_encode($_POST));
		$sign = \HttpRequest::getRequest("sign");
		$sign_type = \HttpRequest::getRequest("sign_type");
		$biz_content = \HttpRequest::getRequest("biz_content");
		$service = \HttpRequest::getRequest("service");
		$charset = \HttpRequest::getRequest("charset");
		trace('get数据:'.json_encode($_GET));
		trace('post数据:'.json_encode($_POST));		
		if (empty ( $sign ) || empty ( $sign_type ) || empty ( $biz_content ) || empty ( $service ) || empty ( $charset )) {
			echo "some parameter is empty.";
			trace("some parameter is empty.");
			exit();
		}	

		$as = new \AopClient();
		$as->alipayrsaPublicKey=$config['alipay_public_key'];
		trace('rsaCheckV2参数：'.json_encode($_REQUEST).';;;;;;'.$config['alipay_public_key'].';;;;;;;;;;;;'.$config['sign_type']);
		$sign_verify = $as->rsaCheckV2($_REQUEST, $config['alipay_public_key'],$config['sign_type']);
		trace('验证结果: '.json_encode($sign_verify));
		if(!$sign_verify) {
			trace( "sign qianming verfiy fail.");
			// 如果验证网关时，请求参数签名失败，则按照标准格式返回，方便在服务窗后台查看。
			if (\HttpRequest::getRequest("service")=="alipay.service.check") {
				$gw = new \Gateway();
				$gw->verifygw(false);
			} else {
				echo "sign verfiy fail.";
				trace("sign verfiy fail.");
			}
			exit();
		}

		// 验证网关请求
		if (\HttpRequest::getRequest("service") == "alipay.service.check") {
			$gw = new \Gateway();
			$gw->verifygw(true);
		} else if (\HttpRequest::getRequest("service") == "alipay.mobile.public.message.notify") {
			// 处理收到的消息
			echo 'messgae susscee';
		}
	}
	/*
	*获取
	*/
	public function get_one_step()
	{
		trace("授权开始");
		$url='https://openauth.alipay.com/oauth2/publicAppAuthorize.htm?app_id='.config('alipay_shh_app_id').'&scope=auth_user,auth_base&redirect_uri=http://'.$_SERVER['HTTP_HOST'].url('zfb_get_back');
		trace("url：".$url);
		header("Location:$url");
	}

	/*
	*回调地址
	*/
	public function zfb_get_back()
	{
		trace('授权回调get：'.json_encode($_GET));
		trace('授权回调requ:'.json_encode($_REQUEST));
		$auth_code=input('auth_code','');
		if(empty($auth_code)){
			die('code为空！');
		}
		trace('支付宝应用授权的CODE:'.$auth_code);
		import('Alipay.AopSdk');
		import('Alipay.function',EXTEND_PATH,'.inc.php');
		$AlipaySystemOauthTokenRequest = new \AlipaySystemOauthTokenRequest ();
		$AlipaySystemOauthTokenRequest->setCode($auth_code);
		$AlipaySystemOauthTokenRequest->setGrantType("authorization_code" );
		
		$access_token_result = aopclient_request_execute($AlipaySystemOauthTokenRequest);
		if (isset($access_token_result->alipay_system_oauth_token_response )) {
			$token_str = $access_token_result->alipay_system_oauth_token_response->access_token;
		}elseif(isset($token->error_response)) {
			echo $token->error_response->sub_msg;
		}	

		trace('支付宝应用授权的Token:'.$token_str);
		$AlipayUserInfoShareRequest = new \AlipayUserInfoShareRequest ();

		$user_info = aopclient_request_execute($AlipayUserInfoShareRequest,$token_str);
		trace('支付宝返回用户信息：'.json_encode($user_info));
		if (isset($user_info->alipay_user_info_share_response)) {
			trace('支付宝用户信息：'.json_encode($user_info->alipay_user_info_share_response));
			$user_info_resp = $user_info->alipay_user_info_share_response;

			$user_id = $user_info_resp->user_id;
			$nick_name = characet($user_info_resp->nick_name);
			trace('支付宝user_id：'.$user_id);
			$one=Db::name('member')->where('member_zfb_userid',$user_id)->find();
			trace("sql查询：".json_encode($one));
			if(empty($one)){
				$data=[
					'member_nickname'=>$nick_name,
					'member_zfb_nickname'=>$nick_name,
					'member_zfb_userid'=>$user_id,
					'member_zfb_content'=>json_encode($user_info->alipay_user_info_share_response),
					'member_addtime'=>time()
				];
				$rs=Db::name('member')->insert($data);
				$member_id = Db::name('member')->getLastInsID();
			}else{
				$member_id=$one['member_id'];
			}
			session('user_id',$user_id);
			session('member_id',$member_id);
			trace("设置的session:".json_encode(input('session.')));
			$from_url=session('from_url');
			if(!empty($from_url)){
				header('Location: '.$from_url);
			}else{
				header('Location: '.$this->base_url.url('park_list'));
			}
		}else{
			trace('支付宝用户信息错误！');
		}
	}

	public function park_list()
	{
		$member_id=session('member_id');
		trace('member_id:'.$member_id);
		if(empty($member_id)){
			trace('未登录');
			$this->get_one_step();
		}		
		return view('park_list');
	}
	/*
	*获取停车场列表数据
	*By
	*create :2018-8-17
	*/
	public function get_park_list()
	{
		$lng=input('single_lng',0);
		$lat=input('single_lat',0);
		$pageIndex=input("pageIndex",1);
		if($lng<-180||$lng>180||$lat<-90||$lat>90){
			return json(['code'=>0,'msg'=>'经纬度不正确','data'=>'']);
		}
		$park_list=model('store')->get_park_list($lng,$lat,$pageIndex);
		return json(['code'=>1,'msg'=>'','data'=>$park_list]);
	}
	/*
	*包月支付页面
	*By
	*/
	public function querynum()
	{
		$member_id=session('member_id');
		trace('member_id:'.$member_id);
		if(empty($member_id)){
			trace('未登录');
			$this->get_one_step();
		}	
		return view('querynum');
	}
	/*
	*设置车牌号
	*By
	*create:2018-8-23
	*/
	public function set_car_num()
	{
		$user_id=session("user_id");
		$member_id=session('member_id');
		if(empty($user_id)||empty($member_id)){
			return json(['code'=>0,'msg'=>'请重新设置！','data'=>'']);
		}
		$car_number=input("car_number",'');
		if(empty($car_number)){
			return json(['code'=>0,'msg'=>'车牌号不能为空！','data'=>'']);
		}
		session($user_id,$car_number);
		return json(['code'=>1,'msg'=>'','data'=>'']);
	}
	/*
	*我的
	*By
	*/
	public function user()
	{
		$member_id=session('member_id');
		trace('member_id:'.$member_id);
		if(empty($member_id)){
			trace('未登录');
			$this->get_one_step();die;
		}		
		$member_info=model('member')->get_info($member_id);
		return view('park_user',['member_info'=>$member_info]);
	}
	/*
	*包月停车场信息
	*/
	public function pay()
	{
		$user_id=session("user_id");
		$member_id=session('member_id');
		if(empty($user_id)||empty($member_id)){
			$this->get_one_step();die;
		}
		$parking_id=input('park_id',0);
		if(empty($parking_id)){
			$this->assign('进入非正常页面！');
		}
 
		$shou_user_id=model("user")->get_user_info($parking_id);		
		$park_info=model("store")->get_store_info($parking_id);
		return view('park_pay',['buy_open_id'=>$user_id,'user_id'=>$shou_user_id,'park_info'=>$park_info]);
	}
	/*
	*创建月包订单
	*By
	*create:2018-8-22
	*/
	public function create_car_month()
	{
		$user_id=session("user_id");
		$member_id=session('member_id');
		if(empty($user_id)||empty($member_id)){
			$this->get_one_step();
		}
		$parking_id=input('park_id',0);
		if(empty($parking_id)){
			return json(['code'=>0,'msg'=>'无此停车场！','data'=>'']);
		}
		$month_current=input('current_month',0);
		if(empty($month_current)||$month_current>2){
			return json(['code'=>0,'msg'=>'包月信息不正确！','data'=>'']);
		}
		$car_number=input("car_number",'');
		if(empty($car_number)){
			return json(['code'=>0,'msg'=>'车牌信息错误！','data'=>'']);
		}
		$ser_car_number=session($user_id);
		if($car_number!=$ser_car_number){
			return json(['code'=>0,'msg'=>'非法车牌信息！','data'=>'']);
		}
		$rs=model('member_car_record')->add(['member_id'=>$member_id,'parking_id'=>$parking_id,'current_month'=>$month_current,'car_number'=>$car_number]);
		return json($rs);	
	}
	/*
	*我的账户
	*By
	*create:2018-8-18
	*/
	public function user_info()
	{
		$user_id=session("user_id");
		$member_id=session('member_id');
		if(empty($user_id)||empty($member_id)){
			$this->get_one_step();
		}
		$member_info=Db::name('member')->find($member_id);
		$this->assign('member_info',$member_info);
		return view('park_user_info');	
	}
	/*
	*获得真实姓名
	*By
	*create:2018-8-18
	*/
	public function user_realname()
	{
		$user_id=session("user_id");
		$member_id=session('member_id');
		if(empty($user_id)||empty($member_id)){
			$this->get_one_step();
		}
		$realname=model('member')->get_realname($member_id);
		return view('park_user_name',['member_realname'=>$realname]);
	}
	/*
	*保存真实姓名
	*By
	*create:2018-8-18
	*/
	public function user_realname_save()
	{
		$user_id=session("user_id");
		$member_id=session('member_id');
		if(empty($user_id)||empty($member_id)){
			$this->get_one_step();
		}
		$member_realname=input('member_realname','','trim');
		$rs=model('member')->realname_save($member_id,$member_realname);
		return json($rs);
	}
	/*
	*获得手机号
	*By
	*create:2018-8-18
	*/
	public function user_tel()
	{
		$user_id=session("user_id");
		$member_id=session('member_id');
		if(empty($user_id)||empty($member_id)){
			$this->get_one_step();
		}
		$user_tel=model('member')->get_tel($member_id);
		return view('park_user_tel',['member_tel'=>$user_tel]);
	}
	/*
	*保存手机号
	*By
	*create:2018-8-18
	*/
	public function user_tel_save()
	{
		$user_id=session("user_id");
		$member_id=session('member_id');
		if(empty($user_id)||empty($member_id)){
			$this->get_one_step();
		}
		$member_tel=input('member_tel','','trim');
		$rs=model('member')->tel_save($member_id,$member_tel);
		return json($rs);
	}
	/*
	*公司信息
	*By
	*create:2018-8-18
	*/
	public function user_company()
	{
		$user_id=session("user_id");
		$member_id=session('member_id');
		if(empty($user_id)||empty($member_id)){
			$this->get_one_step();
		}
		$compnay_info=model('member')->get_company_info($member_id);
		return view('park_user_company',['compnay_info'=>$compnay_info]);
	}
	/*
	*保存公司信息
	*/
	public function user_company_save()
	{
		$user_id=session("user_id");
		$member_id=session('member_id');
		if(empty($user_id)||empty($member_id)){
			$this->get_one_step();
		}
		$company_name=input('company_name','','trim');
		$company_address=input('company_address','','trim');
		$rs=model('member')->set_company($member_id,$company_name,$company_address);
		return json($rs);
	}
	/*
	*绑定车牌
	*By
	*create:2018-8-20
	*/
	public function bind_car_num()
	{
		$user_id=session("user_id");
		$member_id=session('member_id');
		if(empty($user_id)||empty($member_id)){
			$this->get_one_step();
		}
		return view('park_bind_cartnum');
	}
	/*
	*绑定车牌保存
	*By
	*create:2018-8-20
	*/
	public function bind_carnum_save()
	{
		$user_id=session("user_id");
		$member_id=session('member_id');
		if(empty($user_id)||empty($member_id)){
			$this->get_one_step();
		}
		$car_number=input('car_number');
		$rs=model('MemberCar')->add($member_id,$car_number);
		return json($rs);
	}	
	/*
	*车牌列表页面
	*By
	*create:2018-8-20
	*/	
	public function user_cartnum()
	{
		$user_id=session("user_id");
		$member_id=session('member_id');
		if(empty($user_id)||empty($member_id)){
			$this->get_one_step();
		}
		return view('park_user_cartnum');
	}
	/*
	*车牌列表
	*By
	*create:2018-8-18
	*/
	public function user_carnum_list()
	{
		$user_id=session("user_id");
		$member_id=session('member_id');
		if(empty($user_id)||empty($member_id)){
			$this->get_one_step();
		}
		$rs=model('MemberCar')->get_list($member_id);
		return json($rs);
	}
	/*
	*车牌删除
	*By
	*create:2018-8-20
	*/
	public function user_carnum_del()
	{
		$user_id=session("user_id");
		$member_id=session('member_id');
		if(empty($user_id)||empty($member_id)){
			$this->get_one_step();
		}
		$data=input('data','','trim');
		$data=json_decode($data,true);
		$rs=model('MemberCar')->del($member_id,$data['car_id']);
		return json($rs);
	}
	/*
	*包月列表
	*By
	*create:2018-8-29
	*/
	public function park_info()
	{
		$user_id=session("user_id");
		$member_id=session('member_id');
		if(empty($user_id)||empty($member_id)){
			$this->get_one_step();
		}
		return view();
	}
	/*
	*包月列表信息
	*By
	*create:2018-8-29	
	*/
	public function get_park_month_list()
	{
		$user_id=session("user_id");
		$member_id=session('member_id');
		if(empty($user_id)||empty($member_id)){
			$this->get_one_step();
		}
		$rs=model("member_car_record")->get_list(['member_id'=>$member_id]);
		return json($rs);
	}
}