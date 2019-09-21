<?php
/**
 * Created by PhpStorm.
 * User: ZGL
 * Date: 2019/6/11
 * Time: 9:00
 */

namespace app\api\controller;


use think\Cache;
use think\Controller;
use think\Db;
use think\Log;
use think\Request;

class AppPay extends Controller
{
    public $request;
    public $wx_arr;
    public $ali_arr;

    public function _initialize()
    {
        $this->request = Request::instance();
        $this->wx_arr = [1002,1004,1006];
        $this->ali_arr = [1001,1003,1005,1007];
    }

    /**
     * 收银员收款app登陆
     * @author wzs
     * @return \think\response\Json
     */
    public function login()
    {
        try{
            if(!$this->request->isPost()){
                throw new \Exception('请求方式错误');
            }

            $post = $this->request->post();
            if(!isset($post['user_name']) || empty($post['user_name']) || !isset($post['password']) || empty($post['password'])){
                throw new \Exception('参数错误');
            }
            $user_name = $post['user_name'];
            $password = $post['password'];
            $user_info = Db::name('user')->where(['user_mobile'=>$user_name,'user_password'=>md5(md5(md5($password))),'user_role'=>2])->field('user_id,user_realname,user_store_id')->find();
            if(empty($user_info)){
                throw new \Exception('账号或者密码错误');
            }

            $store_info = Db::name('store')->where(['store_id'=>$user_info['user_store_id']])->field('store_shop_id')->find();
            if(empty($store_info)){
                throw new \Exception('店铺不存在');
            }

            $this->getCache($user_info['user_id']);

            $store = Db::name('store')->where(['store_id'=>$user_info['user_store_id']])->field('store_name')->find();
            $data_res = ['user_id'=>$user_info['user_id'],'store_id'=>$user_info['user_store_id'],
                'shop_id'=>$store_info['store_shop_id'],'user_real_name'=>$user_info['user_realname'],'store_name'=>$store['store_name']];

            return json(['code'=>200,'msg'=>'登陆成功','data'=>$data_res]);
        }catch (\Exception $e){
            return json(['code'=>400,'msg'=>$e->getMessage(),'data'=>null]);
        }
    }

    /**
     * 创建订单
     * @author wzs
     * @return Json
     */
    public function createOrder()
    {
        Db::startTrans();
        try{
            if(!$this->request->isPost()){
                throw new \Exception('请求方式错误');
            }
            $post = $this->request->post();

            if(!isset($post['user_id']) || empty($post['user_id']) ||!isset($post['total_amount']) || empty($post['total_amount'])){
                throw new \Exception('参数错误');
            }
            if (!is_numeric($post["user_id"]) || !is_numeric($post["total_amount"])) {
                throw new \Exception('参数格式错误');
            }
            if ($post["total_amount"] > 9999999) {
                throw new \Exception('收款金额超限');
            }

            $user_id = $post['user_id'];
            $user_data = $this->getCache($post['user_id']);
            $shop_id = $user_data['shop_id'];
            $store_id = $user_data['store_id'];
            $total_amount = config('sx_site') == 1 ? 1 : $post['total_amount'];

            //生成订单记录
            $order_num = model('order')->getOrderNum();

            $insert_order_data = ['order_num'=>$order_num,
                'order_user_id'=>$user_id,
                'order_addtime'=>time(),
                'order_total_amount'=>$total_amount/100,
                'order_status'=>0,
                'order_shop_id'=>$shop_id,
                'order_store_id'=>$store_id,
                'order_create_where'=>'app'];

            $insert_order_id = Db::name('order')->insertGetId($insert_order_data);

            if(!$insert_order_id){
                throw new \Exception('生成订单失败');
            }

            Db::commit();
            return json(['code'=>200,'msg'=>'订单创建成功','data'=>['order_id'=>$insert_order_id]]);
        }catch (\Exception $e){
            Db::rollback();
            return json(['code'=>400,'msg'=>$e->getMessage(),'data'=>null]);
        }
    }

    /**
     * 支付
     * @author wzs
     * @return \think\response\Json
     */
    public function doPay()
    {
        try{
            if(!$this->request->isPost()){
                throw new \Exception('请求方式错误');
            }
            $post = $this->request->post();
            $auth_code = '';
            if(!isset($post['order_id']) || empty($post['order_id']) || !isset($post['type']) || empty($post['type'])){
                throw new \Exception('请求参数错误');
            }

            if($post['type'] == 2){
                if(!isset($post['auth_code']) || empty($post['auth_code'])){
                    throw new \Exception('请求参数错误');
                }
                $auth_code = $post['auth_code'];
            }

            $order_info = Db::name('order')->where(['order_id'=>$post['order_id']])->field('order_user_id,order_num,order_total_amount')->find();
            $total_amount = $order_info['order_total_amount'];
            $order_num = $order_info['order_num'];
            $user_shop_info = $this->getCache($order_info['order_user_id']);
            $shop_info = Db::name('shop')->where(['shop_id'=>$user_shop_info['shop_id']])->field('shop_wxpay_sub_mch_id,shop_alipay_app_auth_token')->find();
            Log::write(json_encode($user_shop_info,JSON_UNESCAPED_UNICODE),'log');

            $shop_alipay_app_auth_token = $shop_info['shop_alipay_app_auth_token'];
            $shop_wxpay_sub_mch_id = $shop_info['shop_wxpay_sub_mch_id'];
            if(empty($shop_alipay_app_auth_token) && empty($shop_wxpay_sub_mch_id)){
                throw new \Exception('授权错误');
            }
            $order_subject = '现金支付';
            $pay_res = true;
            switch ($post['type']){
                case 1:$pay_res = ['order_pay_time'=>time(),'order_trade_no'=>'','order_channel_id'=>1008,'total_amount'=>$total_amount];break;
                case 2:$pay_res = $this->sweepCodePay($order_num,$total_amount,$auth_code,$shop_alipay_app_auth_token,$shop_wxpay_sub_mch_id);$order_subject = '扫码支付';break;
            }

            if($pay_res === false){
                throw new \Exception('扫码支付失败');
            }

            $up_res = Db::name('order')->where(['order_id'=>$post['order_id']])->update(['order_status'=>100,'order_channel_id'=>$pay_res['order_channel_id'],
                'order_subject'=>$order_subject,
                'order_pay_time'=>$pay_res['order_pay_time'],
                'order_trade_no'=>$pay_res['order_trade_no'],'order_pay_realprice'=>$pay_res['total_amount']]);
            Log::write(json_encode($pay_res,JSON_UNESCAPED_UNICODE),'log');

            if(!$up_res){
                throw new \Exception('更新支付状态失败');
            }

            return json(['code'=>200,'msg'=>'付款成功','data'=>null]);
        }catch (\Exception $e){
            return json(['code'=>400,'msg'=>$e->getMessage(),'data'=>null]);
        }
    }

    /**
     * 支付宝扫码枪支付
     * @author wzs
     * @param $order_num
     * @param $total_amount
     * @param $auth_code
     * @param $shop_alipay_app_auth_token
     * @param $shop_wxpay_sub_mch_id
     * @return array|bool|void
     */
    public function sweepCodePay($order_num,$total_amount,$auth_code,$shop_alipay_app_auth_token,$shop_wxpay_sub_mch_id)
    {
        $start_num = substr($auth_code,0,2);
        $ali_num_array = [25,26,27,28,29,30];
        if(in_array($start_num,$ali_num_array)){
            $content = ['out_trade_no'=>$order_num,'scene'=>'bar_code','auth_code'=>$auth_code,
                'product_code'=>'FACE_TO_FACE_PAYMENT','subject'=>'支付宝扫码支付','total_amount'=>$total_amount];
            $content = json_encode($content,JSON_UNESCAPED_UNICODE);

            $pay_result = SmilePay::barCodePay($content,$shop_alipay_app_auth_token);
        }else{
            $content = ['auth_code'=>$auth_code,'order_num'=>$order_num,
                'subject'=>'微信扫码支付','total_amount'=>$total_amount];

            $pay_result = WxPay::barcodePay($content,$shop_wxpay_sub_mch_id);
        }

        if(empty($pay_result)){
            return false;
        }

        return $pay_result;
    }

    /**
     * 二码合一统一（收款码）
     * @author wzs
     * @return \think\response\Json
     */
    public function getQrcodeImg()
    {
        try{
            if(!$this->request->isGet()){
                throw new \Exception('请求方式错误');
            }
            $get = $this->request->get();

            if(!isset($get['user_id']) || empty($get['user_id'])){
                throw new \Exception('请求参数错误');
            }
            if (!is_numeric($get["user_id"])) {
                throw new \Exception('参数格式错误');
            }

            $code_img_url = request()->domain().'/api/app_pay/qrcode?uid='.$get['user_id'];

            return json(['code'=>200,'msg'=>'请求成功','data'=>$code_img_url]);
        }catch (\Exception $e){
            return json(['code'=>400,'msg'=>$e->getMessage(),'data'=>null]);
        }
    }

    /**
     * 跳转到聚合码页面
     * @author wzs
     */
    public function qrcode()
    {
        //收银员ID
        $uid       = input("uid");
        $reward    = input("reward", 0);
        $param     = url("h5/index/appjsapipay?user_id=" . $uid . "&reward=" . $reward);
        $returnUrl = request()->domain() . "/index.php?s=" . $param; //因为微信要写固定的授权目录，所以用兼容模式，参数写成变动的,可增加删除参数
        //echo $returnUrl;die();
        $this->redirect($returnUrl); //转向统一页面
    }

    /**
     * 修改密码
     * @author wzs
     * @return \think\response\Json
     */
    public function changePw()
    {
        try{
            if(!$this->request->isPost()){
                throw new \Exception('请求方式错误');
            }
            $post = $this->request->post();

            if(!isset($post['user_id']) || empty($post['user_id']) || !isset($post['password']) || empty($post['password'])
                || !isset($post['new_password']) || empty($post['new_password'])){
                throw new \Exception('请求参数错误');
            }
            if (!is_numeric($post["user_id"])) {
                throw new \Exception('参数格式错误');
            }

            $user_id = $post['user_id'];
            $password = $post['password'];
            $new_password = $post['new_password'];
            $is_user_exist = Db::name('user')->where(['user_id'=>$user_id,'user_password'=>md5(md5(md5($password)))])->count();
            if(empty($is_user_exist)){
                throw new \Exception('密码错误');
            }
            $change_res = Db::name('user')->where(['user_id'=>$user_id])->update(['user_password'=>md5(md5(md5($new_password)))]);
            if($change_res === false){
                throw new \Exception('密码修改失败');
            }

            return json(['code'=>200,'msg'=>'密码修改成功','data'=>null]);
        }catch (\Exception $e){
            return json(['code'=>400,'msg'=>$e->getMessage(),'data'=>null]);
        }
    }

    /**
     * 订单统计
     * @author wzs
     * @return \think\response\Json
     */
    public function getOrderStat()
    {
        try{
            if(!$this->request->isGet()){
                throw new \Exception('请求方式错误');
            }
            $get = $this->request->get();

            if(!isset($get['user_id']) || empty($get['user_id']) || !isset($get['start_date']) || empty($get['start_date'])
                || !isset($get['end_date']) || empty($get['end_date'])){
                throw new \Exception('请求参数错误');
            }
            if (!is_numeric($get["user_id"])) {
                throw new \Exception('参数格式错误');
            }

            $where['order_user_id'] = $get['user_id'];

            $startTime = strtotime($get['start_date']);
            $endTime = strtotime("{$get['end_date']} + 1 day");

            if($startTime >= $endTime){
                throw new \Exception('日期有误');
            }

            $where['order_addtime'] = ['between',[$startTime,$endTime]];

            $ali_arr = $this->ali_arr;$wx_arr = $this->wx_arr;
            $where['order_status'] = 100;
            $order_count_arr = Db::name('order')->where($where)->field('order_channel_id,count(order_id) as count,sum(order_pay_realprice) as sum_amount')->group('order_channel_id')->select();
            $sum_amount = $all_count = $ali_count = $wx_count = 0;

            array_walk($order_count_arr,function (&$v) use (&$ali_arr,&$wx_arr,&$ali_count,&$wx_count,&$all_count,&$sum_amount){
                if(in_array($v['order_channel_id'],$ali_arr)){
                    $ali_count += $v['count'];
                }else if(in_array($v['order_channel_id'],$wx_arr)){
                    $wx_count += $v['count'];
                }
                $sum_amount += $v['sum_amount'];
            });
            $all_count = $ali_count + $wx_count;

//            $sum_amount = Db::name('order')->where($where)->sum('order_pay_realprice');
            $where['order_status'] = 200;
            $sum_refund_amount = Db::name('order')->where($where)->sum('order_pay_realprice');

            $res_data = ['order_count'=>$all_count,'sum_amount'=>$sum_amount,
                'sum_refund_amount'=>$sum_refund_amount,'ali_count'=>$ali_count,'wx_count'=>$wx_count];

            return json(['code'=>200,'msg'=>'请求成功','data'=>$res_data]);
        }catch (\Exception $e){
            return json(['code'=>400,'msg'=>$e->getMessage(),'data'=>null]);
        }
    }


    /**
     * 订单列表
     * @author wzs
     * @return \think\response\Json
     */
    public function getOrderList()
    {
        try{
            if(!$this->request->isGet()){
                throw new \Exception('请求方式错误');
            }
            $get = $this->request->get();

            if(!isset($get['user_id']) || empty($get['user_id']) ||!isset($get['start_date']) || empty($get['start_date'])
                || !isset($get['end_date']) || empty($get['end_date'])){
                throw new \Exception('请求参数错误');
            }
            if (!is_numeric($get["user_id"])) {
                throw new \Exception('参数格式错误');
            }

            $startTime = strtotime($get['start_date']);
            $endTime = strtotime("{$get['end_date']} + 1 day");

            if($startTime >= $endTime){
                throw new \Exception('日期有误');
            }

            $where['order_user_id'] = $get['user_id'];

            $startTime = strtotime($get['start_date']);
            $endTime = strtotime("{$get['end_date']} + 1 day");
            $where['order_addtime'] = ['between',[$startTime,$endTime]];

            if(isset($get['status'])){
                $status = (int) $get['status'];
                switch ($status){
                    case 1:$where['order_status'] = 100;break;
                    case 2:$where['order_status'] = 200;break;
                    case 3:$where['order_status'] = 0;break;
                }
            }

            $where['order_channel_id'] = ['in',array_merge($this->wx_arr,$this->ali_arr)];
            if(isset($get['pay_type'])){
                $payType = (int) $get['pay_type'];
                switch ($payType){
                    case 1:$where['order_channel_id'] = ['in',$this->ali_arr];break;
                    case 2:$where['order_channel_id'] = ['in',$this->wx_arr];break;
                }
            }

            $order_list = Db::name('order')->where($where)
                ->field('order_id,order_num,order_channel_id,order_addtime,order_status,order_pay_time,order_total_amount,order_channel_id,order_pay_realprice')
                ->paginate(10)->toArray();

            $order_data = $this->doOrderDetail($order_list['data'],$get['user_id']);

            return json(['code'=>200,'msg'=>'请求成功','data'=>$order_data]);
        }catch (\Exception $e){
            return json(['code'=>400,'msg'=>$e->getMessage(),'data'=>null]);
        }
    }

    /**
     * 处理订单信息
     * @author wzs
     * @param $order_data
     * @param $user_id
     * @return mixed
     */
    public function doOrderDetail($order_data,$user_id)
    {
        $user_info = $this->getCache($user_id);
        $store = Db::name('store')->where(['store_id'=>$user_info['store_id']])->field('store_name')->find();

        $payType = model('pay')->payChannel(1);
        array_walk($order_data,function (&$v) use(&$user_info,&$store,&$payType){
            $v['order_pay_time'] = $v['order_pay_time'] ? date('Y-m-d H:i:s',$v['order_pay_time']) : '';
            $v['order_addtime'] = date('Y-m-d H:i:s',$v['order_addtime']);
            switch ($v['order_status']){
                case 0:$v['order_status'] = '待付款';$v['order_pay_realprice'] = $v['order_total_amount'];break;
                case 100:$v['order_status'] = '支付成功';break;
                case 200:$v['order_status'] = '退款成功';break;
            }
            switch ($v['order_channel_id']){
                case 0:$v['order_status'] = '待付款';break;
                case 100:$v['order_status'] = '支付成功';break;
                case 200:$v['order_status'] = '退款成功';break;
            }

            $v['pay_channel_type'] = $v['order_channel_id'] ? $payType[$v['order_channel_id']] : '';

            $v['pay_type'] = 1;
            if(in_array($v['order_channel_id'],$this->wx_arr)){
                $v['pay_type'] = 2;
            }
            $v['user_username'] = $user_info['user_real_name'];
            $v['store_name'] = $store['store_name'];
        });

        return $order_data;
    }

    /**
     * 获取店铺电话
     * @author wzs
     * @return \think\response\Json
     */
    public function getPhoneNum()
    {
        try{
            if(!$this->request->isGet()){
                throw new \Exception('请求方式错误');
            }
            $get = $this->request->get();

            if(!isset($get['user_id']) || empty($get['user_id'])){
                throw new \Exception('请求参数错误');
            }
            if (!is_numeric($get["user_id"])) {
                throw new \Exception('参数格式错误');
            }

            $user_info = $this->getCache($get['user_id']);
            $store = Db::name('store')->where(['store_id'=>$user_info['store_id']])->field('store_mobile')->find();

            return json(['code'=>200,'msg'=>'请求成功','data'=>$store]);
        }catch (\Exception $e){
            return json(['code'=>400,'msg'=>$e->getMessage(),'data'=>null]);
        }
    }

    /**
     * 获取收银员缓存信息
     * @author wzs
     * @param $user_id
     * @return array|mixed
     */
    public function getCache($user_id)
    {
        $user_shop_info = Cache::get('user_info_'.$user_id);
        if(!$user_shop_info){
            $user_shop_info = $this->getShopStoreInfo($user_id);
            Cache::set('user_info_'.$user_id,$user_shop_info);
        }

        return $user_shop_info;
    }

    /**
     * 查询店铺等信息
     * @author wzs
     * @param $user_id
     * @return array
     */
    public function getShopStoreInfo($user_id)
    {
        $user_info = Db::name('user')->where(['user_id'=>$user_id])->field('user_realname,user_store_id')->find();
        if(empty($user_info)){
            return [];
        }
        $store_info = Db::name('store')->where(['store_id'=>$user_info['user_store_id']])->field('store_shop_id')->find();
        if(empty($store_info)){
            return [];
        }
        $data_res = ['user_id'=>$user_id,'store_id'=>$user_info['user_store_id'],'shop_id'=>$store_info['store_shop_id'],'user_real_name'=>$user_info['user_realname']];

        return $data_res;
    }

    /**
     * 订单号获取订单详情
     * @author wzs
     * @return \think\response\Json
     */
    public function getOrderDetail()
    {
        try{
            if(!$this->request->isGet()){
                throw new \Exception('请求方式错误');
            }
            $get = $this->request->get();

            if(!isset($get['user_id']) || empty($get['user_id']) || !isset($get['order_num']) || empty($get['order_num'])){
                throw new \Exception('请求参数错误');
            }
            if (!is_numeric($get["user_id"])) {
                throw new \Exception('参数格式错误');
            }

            $user_info = $this->getCache($get['user_id']);
            $order_data = Db::name('order')->where(['order_store_id'=>$user_info['store_id'],'order_num'=>$get['order_num']])
                ->field('order_id,order_num,order_addtime,order_status,order_pay_time,order_total_amount,order_channel_id,order_pay_realprice')
                ->find();
            if(empty($order_data)){
                throw new \Exception('无效订单号');
            }
            $data[0] = $order_data;
            $order_data = $this->doOrderDetail($data,$get['user_id']);

            return json(['code'=>200,'msg'=>'请求成功','data'=>$order_data[0]]);
        }catch (\Exception $e){
            return json(['code'=>400,'msg'=>$e->getMessage(),'data'=>null]);
        }
    }
}