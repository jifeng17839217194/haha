<?php
namespace app\h5\controller;
use think\Controller;
use think\Request;
use think\Db;
use think\Log;
use think\Session;
use EasyWeChat\Foundation\Application;
/**
* 微信包月网站
*/
class Wxpark extends Controller
{
	protected $app;
	protected $base_url='http://ipay.iaapp.cn';
	public function _initialize()
	{
		$action_name=Request::instance()->action();
		if($action_name!='wx_user_check'){
			$from_url=$this->base_url.url($action_name);
			session('from_url',$from_url);
		}	
		$options=$this->get_options();
		$this->app = new Application($options);		
		$js=$this->app->js;
		$this->assign('js',$js);
	}
	/*
	*easywechat 配置
	*/
	public function get_options()
	{
		return [
		 	/**
		     * Debug 模式，bool 值：true/false
		     *
		     * 当值为 false 时，所有的日志都不会记录
		     */
		    'debug'  => true,
		     /**
		     * 账号基本信息，请从微信公众平台/开放平台获取
		     */
		    'app_id' => config('wxpay_app_id'),
		    'secret' => config('wxpay_app_secret'),
		    'token'  => 'parking',
		    'aes_key' => 'cMUX0l2rfhxn3uXE1bsbHKpnHbSE1B0g6YG3nUTYVab', // EncodingAESKey，安全模式与兼容模式下请一定要填写！！！

		    'log' => [
		        'level' => 'debug',
		        'file'  => '/www/wwwroot/ipay.iaapp.cn/runtime/easywechat.log', // 绝对路径！！！！
		    ],

		    'oauth' => [
			      'scopes'   => ['snsapi_userinfo'],
			      'callback' => url('wx_user_check'),
			  ],

		];
	}	
	/*
	*微信自动登录
	*/
	public function wx_auto_login()
	{
		$response = $this->app->oauth->redirect();
        $response->send();   
	}
		/*
	微信查询订单
	*/	
	public function wx_pay_result_search()
	{
		$payment = $this->app->payment;
		$orderNo = "ZZ1538456473116859";
		var_dump($payment->query($orderNo));
	}
	/*
	*判断是否已登录过
	*/
	public function wx_user_check()
	{
		$user = $this->app->oauth->user();
		// $user 可以用的方法:
		// $user->getId();  // 对应微信的 OPENID
		// $user->getNickname(); // 对应微信的 nickname
		// $user->getName(); // 对应微信的 name
		// $user->getAvatar(); // 头像网址
		// $user->getOriginal(); // 原始API返回的结果
		// $user->getToken(); // access_token， 比如用于地址共享时使用
		$openid=$user->getId();
		trace('openid'.$openid);
		$one=Db::name('member')->where('member_wx_openid',$openid)->find();
		trace(json_encode($one));
		if(empty($one)){
			$data=[
				'member_nickname'=>$user->getName(),
				'member_wx_openid'=>$openid,
				'member_wx_nickname'=>$user->getNickname(),
				'member_wx_headimgurl'=>$user->getAvatar(),
				'member_addtime'=>time()
			];
			$rs=Db::name('member')->insert($data);
			$member_id = Db::name('member')->getLastInsID();
		}else{
			$member_id=$one['member_id'];
		}
		session('openid',$openid);
		session('member_id',$member_id);
		trace(json_encode(input('session.')));
		$from_url=session('from_url');
		if(!empty($from_url)){
			header('Location: '.$from_url);
		}else{
			header('Location: '.$this->base_url.url('nearby_park_list'));
		}
	}
	/*
	*token验证
	*/
	public function valid()
	{
		
		$response =$this->app->server->serve();
		$response->send();
	}
	/*
	*添加菜单
	*/
	public function add_menus()
	{	
		$buttons=[
			[
			"name"=>'附近停车场',
			'type'=>'view',
			'url'=>$this->base_url.url('nearby_park_list'),
			],
			[
			"name"=>'快速缴费',
			'type'=>'view',
			'url'=>$this->base_url.url('querynum'),
			],
			[
			"name"=>'我的',
			'type'=>'view',
			'url'=>$this->base_url.url('user'),
			],
		];
		$menu = $this->app->menu;
		$menu->add($buttons);
	}
	/*
	*查询菜单
	*/
	public function get_menus()
	{
		$menu = $this->app->menu;
		$menus = $menu->all();
		var_dump($menus);
	}
	/*
	*定时任务 设置过期
	*By
	*create:2018-8-31
	*/
	public function update_record_status()
	{
		$rs=Db::name("member_car_record")->where("record_end_time<now()")->setField("record_status",2);
		trace("更新sql:". Db::name('member_car_record')->getLastSql());
	}
	/*
	*附近停车场列表页面
	*By
	*Create:2018-8-15
	*/
	public function nearby_park_list()
	{
		$openid=session("openid");
		//var_dump($openid);
		if(empty($openid)){
			$this->wx_auto_login();
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
	*我的
	*By
	*create :2018-8-17
	*/
	public function user()
	{
		$openid=session("openid");
		$member_id=session('member_id');
		if(empty($openid)||empty($member_id)){
			$this->wx_auto_login();
		}
		$member_info=model('member')->get_info($member_id);
		return view('park_user',['member_info'=>$member_info]);
	}
	/*
	*我的账户
	*By
	*create:2018-8-18
	*/
	public function user_info()
	{
		$openid=session('openid');
		$member_id=session('member_id');
		if(empty($openid)||empty($member_id)){
			$this->wx_auto_login();
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
		$openid=session('openid');
		$member_id=session('member_id');
		if(empty($openid)||empty($member_id)){
			$this->wx_auto_login();
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
		$openid=session('openid');
		$member_id=session('member_id');
		if(empty($openid)||empty($member_id)){
			$this->wx_auto_login();
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
		$openid=session('openid');
		$member_id=session('member_id');
		if(empty($openid)||empty($member_id)){
			$this->wx_auto_login();
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
		$openid=session('openid');
		$member_id=session('member_id');
		if(empty($openid)||empty($member_id)){
			$this->wx_auto_login();
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
		$openid=session('openid');
		$member_id=session('member_id');
		if(empty($openid)||empty($member_id)){
			$this->wx_auto_login();
		}		
		$compnay_info=model('member')->get_company_info($member_id);
		return view('park_user_company',['compnay_info'=>$compnay_info]);
	}
	/*
	*保存公司信息
	*/
	public function user_company_save()
	{
		$openid=session('openid');
		$member_id=session('member_id');
		if(empty($openid)||empty($member_id)){
			$this->wx_auto_login();
		}
		$company_name=input('company_name','','trim');
		$company_address=input('company_address','','trim');
		$rs=model('member')->set_company($member_id,$company_name,$company_address);
		return json($rs);
	}
	/*
	*包月列表
	*By
	*create:2018-8-20
	*/
	public function get_car_monthly_list()
	{
		$openid=session('openid');
		$member_id=session('member_id');
		if(empty($openid)||empty($member_id)){
			$this->wx_auto_login();
		}
		//$pageIndex=input('pageIndex',0);
		$rs=model('MemberCarRecord')->get_list($member_id);
		return json($rs);
	}
	/*
	*绑定车牌
	*By
	*create:2018-8-20
	*/
	public function bind_car_num()
	{
		$openid=session('openid');
		$member_id=session('member_id');
		if(empty($openid)||empty($member_id)){
			$this->wx_auto_login();
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
		$openid=session('openid');
		$member_id=session('member_id');
		if(empty($openid)||empty($member_id)){
			$this->wx_auto_login();
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
	public function user_carnum()
	{
		$openid=session('openid');
		$member_id=session('member_id');
		if(empty($openid)||empty($member_id)){
			$this->wx_auto_login();
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
		$openid=session('openid');
		$member_id=session('member_id');
		if(empty($openid)||empty($member_id)){
			$this->wx_auto_login();
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
		$openid=session('openid');
		$member_id=session('member_id');
		if(empty($openid)||empty($member_id)){
			$this->wx_auto_login();
		}
		$data=input('data','','trim');
		$data=json_decode($data,true);
		$rs=model('MemberCar')->del($member_id,$data['car_id']);
		return json($rs);
	}
	/*
	*包月支付页面
	*By
	*create:2018-8-21
	*update:2018-8-22
	*/
	public function querynum()
	{
		$openid=session('openid');
		$member_id=session('member_id');
		if(empty($openid)||empty($member_id)){
			$this->wx_auto_login();
		}
		//获取收银员信息
		// $user_id=model("user")->get_user_info($parking_id);
		// $park_info=model("store")->get_store_name($park_info);
		return view('querynum');
	}
	/*
	*微信支付返回
	*By
	*create:2018-8-22
	*/
	public function paysuccesswx()
	{
		trance('微信支付返回GET：'.json_encode($_GET));
		trance('微信支付返回POST：').json_encode($_POST);
	}
	/*
	*包月停车场信息
	*/
	public function pay()
	{
		$openid=session('openid');
		$member_id=session('member_id');
		if(empty($openid)||empty($member_id)){
			$this->wx_auto_login();
		}
		$parking_id=input('park_id',0);
		if(empty($parking_id)){
			$this->assign('进入非正常页面！');
		}
 
		$user_id=model("user")->get_user_info($parking_id);		
		$park_info=model("store")->get_store_info($parking_id);
		return view('park_pay',['buy_open_id'=>$openid,'user_id'=>$user_id,'park_info'=>$park_info]);
	}
	/*
	*设置车牌号
	*By
	*create:2018-8-23
	*/
	public function set_car_num()
	{
		$openid=session('openid');
		$member_id=session('member_id');
		if(empty($openid)||empty($member_id)){
			return json(['code'=>0,'msg'=>'请重新设置！','data'=>'']);
		}
		$car_number=input("car_number",'');
		if(empty($car_number)){
			return json(['code'=>0,'msg'=>'车牌号不能为空！','data'=>'']);
		}
		session($openid,$car_number);
		return json(['code'=>1,'msg'=>'','data'=>'']);
	}
	/*
	*创建月包订单
	*By
	*create:2018-8-22
	*/
	public function create_car_month()
	{
		$openid=session('openid');
		$member_id=session('member_id');
		if(empty($openid)||empty($member_id)){
			$this->wx_auto_login();
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
		$ser_car_number=session($openid);
		if($car_number!=$ser_car_number){
			return json(['code'=>0,'msg'=>'非法车牌信息！','data'=>'']);
		}
		$rs=model('member_car_record')->add(['member_id'=>$member_id,'parking_id'=>$parking_id,'current_month'=>$month_current,'car_number'=>$car_number]);
		return json($rs);	
	}
	/*
	*包月列表
	*By
	*create:2018-8-29
	*/
	public function park_info()
	{
		$openid=session('openid');
		$member_id=session('member_id');
		if(empty($openid)||empty($member_id)){
			$this->wx_auto_login();
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
		$openid=session('openid');
		$member_id=session('member_id');
		if(empty($openid)||empty($member_id)){
			$this->wx_auto_login();
		}
		$rs=model("member_car_record")->get_list(['member_id'=>$member_id]);
		return json($rs);
	}
	/*
	*我的车牌列表
	*By
	*create:2018-8-30
	*/
	public function user_cartnum()
	{
		return view('park_user_cartnum');
	}
	/*
	*空方法
	*/
	public function _empty()
	{
		return view();
	}
	/*
	*是否包月 test
	*/
	public function search_month()
	{
		var_dump(model('member_car_record')->search_month(array (
  'car_number' => '浙AU863Y',
  'store_id' => 1056,
)));
	}
}