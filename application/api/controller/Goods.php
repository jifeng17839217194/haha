<?php
/**
 * Created by PhpStorm.
 * User: ZGL
 * Date: 2019/5/16
 * Time: 14:20
 */

namespace app\api\controller;

use think\Cache;
use think\Controller;
use think\Db;
use think\Exception;
use think\Log;
use think\Request;
use think\response\Json;

class Goods extends Controller
{

    /**
     * 商品展示
     * @author wzs
     * @return Json
     */
    public function index()
    {
        $get = Request::instance()->get();
        try{

            if(!isset($get['store_id']) || empty($get['store_id'])){
                throw new \Exception('参数错误');
            }

            $store_id = $get['store_id'];

            $where = ['store_id'=>$store_id,'is_delete'=>0];
            $cate_list = Db::name('Category')->where($where)->field('category_id,category_name,img as category_img')->select();

            array_walk($cate_list,function (&$v){
                $goods_list = Db::name('Goods')
                    ->where(['cate_id'=>$v['category_id'],'is_delete'=>0])->select();
                array_walk($goods_list,function(&$vv){
                    $vv['price'] = $vv['price']/100;
                });
                $v['goods'] = $goods_list;
            });


            return json(['code'=>200,'msg'=>'请求成功','data'=>$cate_list]);
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
        $post = Request::instance()->post();

        Db::startTrans();
        try{
            if(!isset($post['user_id']) || empty($post['user_id']) || !isset($post['shop_id']) || empty($post['shop_id'])
                || !isset($post['store_id']) || empty($post['store_id']) || !isset($post['good_ids']) || empty($post['good_ids'])){
                throw new \Exception('参数错误');
            }

            $user_id = $post['user_id'];
            $shop_id = $post['shop_id'];
            $store_id = $post['store_id'];
            $good_ids = $post['good_ids'];

            $good_id_arr = explode(',',$good_ids);
            $goods_ids = [];$goods_nums = [];$goods_prices = [];$goods_names=[];
            foreach ($good_id_arr as $k=>$v){
                if(!in_array($v,$goods_ids)){
                    $goods_ids[$v] = $v;
                    $goods_nums[$v] = 1;
                }else{
                    $goods_nums[$v] += 1;
                }
            }

            $goodIds = array_unique($good_id_arr);
            $good_arr = Db::name('goods')->where('goods_id','in',$goodIds)->field('goods_id,goods_name,price')->select();

            foreach($good_arr as $k=>$v){
                $goods_prices[$v['goods_id']] = $v['price'];
                $goods_names[$v['goods_id']] = $v['goods_name'];
            }
            $total_amount = 0;
            foreach ($goodIds as $k=>$v){
                $total_amount += $goods_prices[$v] * $goods_nums[$v];
            }
            $total_amount = config('sx_site') == 1 ? 1 : $total_amount;
            //生成订单记录
            $order_num = $this->getOrderNum();

            $insert_order_data = ['order_num'=>$order_num,
                'order_user_id'=>$user_id,
                'order_addtime'=>time(),
                'order_total_amount'=>$total_amount/100,
                'order_status'=>0,
                'order_shop_id'=>$shop_id,
                'order_store_id'=>$store_id,
                'order_create_where'=>'android'];

            $insert_order_id = Db::name('order')->insertGetId($insert_order_data);
            $insert_data = [];

            if($insert_order_id){
                array_walk($goodIds,function($v) use ($insert_order_id,&$insert_data,&$goods_names,&$goods_nums,&$goods_prices){
                    $insert_data[] = ['order_id'=>$insert_order_id,
                        'goods_id'=>$v,
                        'goods_name'=>$goods_names[$v],
                        'goods_num'=>$goods_nums[$v],
                        'goods_price'=>$goods_prices[$v],
                        'total_price'=>$goods_nums[$v]*$goods_prices[$v],
                        'create_time'=>time()];
                });
            }

            $insert_order_detail_res = Db::name('order_detail')->insertAll($insert_data);
            if(!$insert_order_detail_res){
                throw new \Exception('订单详情添加失败');
            }

            Db::commit();
            return json(['code'=>200,'msg'=>'订单创建成功','data'=>['order_id'=>$insert_order_id]]);
        }catch (\Exception $e){
            Db::rollback();
            return json(['code'=>400,'msg'=>$e->getMessage(),'data'=>null]);
        }
    }

    /**
     * 订单号生成
     * @author wzs
     * @return string
     */
    function getOrderNum(){
//        $order_number = date('Ymd').substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
        $order_number = date('Ymd').substr(time(),3,7).rand(1000,9999);
        $is_exsit_num = Db::name('order')->where(['order_num'=>$order_number])->count();
        if($is_exsit_num){
            $order_number = $this->getOrderNum();
        }

        return $order_number;
    }

    /**
     * 初始化刷脸
     * @author wzs
     * @return Json
     */
    public function initSmile()
    {

        try{
            $post = Request::instance()->post();

            if(!isset($post['biz_content']) || empty($post['biz_content'])){
                throw new \Exception('初始化参数错误');
            }

            $content = json_encode($post['biz_content'],JSON_UNESCAPED_UNICODE);

            $initRes = SmilePay::initSmile($content);

            if(!$initRes){
                throw new \Exception('刷脸初始化失败');
            }

            return json(['code'=>200,'msg'=>'刷脸初始化成功','data'=>$initRes]);
        }catch (\Exception $e){
            return json(['code'=>400,'msg'=>$e->getMessage(),'data'=>null]);
        }
    }

    /**
     * 刷脸付款
     * @author wzs
     * @return Json
     */
    public function paySmile()
    {
        $post = Request::instance()->post();
        try{
            if(!isset($post['order_id']) || empty($post['order_id']) || !isset($post['ftoken']) || empty($post['ftoken'])){
                throw new \Exception('请求参数错误');
            }
            $order_info = Db::name('order')->where(['order_id'=>$post['order_id']])->field('order_num,order_total_amount')->find();
            $total_amount = $order_info['order_total_amount'];
            $content = json_encode(['out_trade_no'=>$order_info['order_num'],
                'scene'=>'security_code',
                'auth_code'=>$post['ftoken'],
                'subject'=>'刷脸支付商品',
                'total_amount'=>$total_amount],JSON_UNESCAPED_UNICODE);
            $payRes = SmilePay::paySmile($content);

            if(!$payRes){
                throw new \Exception('支付失败,请重新支付');
            }
            Db::name('order')->where(['order_id'=>$post['order_id']])->update(['order_status'=>100,'order_channel_id'=>1007,'order_subject'=>'刷脸支付','order_pay_time'=>time(),
                'order_trade_no'=>$payRes['trade_no'],'order_pay_realprice'=>$payRes['pay_amount']]);

            return json(['code'=>200,'msg'=>'支付成功','data'=>null]);
        }catch (\Exception $e){
            return json(['code'=>400,'msg'=>$e->getMessage(),'data'=>null]);
        }
    }

    /**
     * 安卓机登陆
     * @author wzs
     * @return Json
     */
    public function userLogin()
    {
        try{
            $post = Request::instance()->post();
            if(!isset($post['user_name']) || empty($post['user_name']) || !isset($post['password']) || empty($post['password'])){
                throw new \Exception('参数错误');
            }
            $user_name = $post['user_name'];
            $password = $post['password'];
            $user_info = Db::name('user')->where(['user_mobile'=>$user_name,'user_password'=>md5(md5(md5($password)))])->field('user_id,user_realname,user_store_id')->find();
            if(empty($user_info)){
                throw new \Exception('账号或者密码错误');
            }

            $store_info = Db::name('store')->where(['store_id'=>$user_info['user_store_id']])->field('store_shop_id')->find();
            if(empty($store_info)){
                throw new \Exception('店铺不存在');
            }
            $data_res = ['user_id'=>$user_info['user_id'],'store_id'=>$user_info['user_store_id'],'shop_id'=>$store_info['store_shop_id'],'user_real_name'=>$user_info['user_realname']];

            Cache::set('user_info_'.$user_info['user_id'],$data_res);
            return json(['code'=>200,'msg'=>'登陆成功','data'=>$data_res]);
        }catch (\Exception $e){
            return json(['code'=>400,'msg'=>$e->getMessage(),'data'=>null]);
        }
    }

    /**
     * 退款
     * @author wzs
     * @return Json
     */
    public function refund()
    {
        try{
            $post = Request::instance()->post();
            if(!isset($post['order_id']) || empty($post['order_id'])){
                throw new \Exception('参数错误');
            }
            $order_id = $post['order_id'];
            $order_info = Db::name('order')->where(['order_id'=>$order_id])->field('order_user_id,order_num,order_pay_realprice as order_amount')->find();
            $order_info['reason'] = '正常退款';

            $user_shop_info = Cache::get('user_info_'.$order_info['order_user_id']);
            if(!$user_shop_info){
                $user_shop_info = $this->getShopStoreInfo($order_info['order_user_id']);
                Cache::set('user_info_'.$order_info['order_user_id'],$user_shop_info);
            }

            $shop_info = Db::name('shop')->where(['shop_id'=>$user_shop_info['shop_id']])->field('shop_alipay_app_auth_token')->find();
            Log::write(json_encode($user_shop_info,JSON_UNESCAPED_UNICODE),'log');

            $shop_alipay_app_auth_token = $shop_info['shop_alipay_app_auth_token'];
            if(empty($shop_alipay_app_auth_token)){
                throw new \Exception('授权错误');
            }

            $refund_res = SmilePay::refund($order_info,$shop_alipay_app_auth_token);

            if(empty($refund_res)){
                throw new \Exception('退款失败');
            }
            $change_status_res = Db::name('order')->where(['order_id'=>$order_id])->update(['order_status'=>200]);
            if(!$change_status_res){
                throw new \Exception('订单状态改变失败');
            }

            return json(['code'=>200,'msg'=>'退款成功','data'=>null]);
        }catch (\Exception $e){
            return json(['code'=>400,'msg'=>$e->getMessage(),'data'=>null]);
        }
    }

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
     * 支付
     * @author wzs
     * @return Json
     */
    public function doPay()
    {

        $post = Request::instance()->post();
        try{
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
            $user_shop_info = Cache::get('user_info_'.$order_info['order_user_id']);
            $shop_info = Db::name('shop')->where(['shop_id'=>$user_shop_info['shop_id']])->field('shop_wxpay_sub_mch_id,shop_alipay_app_auth_token')->find();
            Log::write(json_encode($user_shop_info,JSON_UNESCAPED_UNICODE),'log');

            $shop_alipay_app_auth_token = $shop_info['shop_alipay_app_auth_token'];
            $shop_wxpay_sub_mch_id = $shop_info['shop_wxpay_sub_mch_id'];
            if(empty($shop_alipay_app_auth_token) && empty($shop_wxpay_sub_mch_id)){
                throw new \Exception('授权错误');
            }

            switch ($post['type']){
                case 1:$pay_res = ['order_pay_time'=>time(),'order_trade_no'=>'','order_channel_id'=>1008,'total_amount'=>$total_amount];$order_subject = '现金支付';break;
                case 2:$pay_res = $this->sweepCodePay($order_num,$total_amount,$auth_code,$shop_alipay_app_auth_token,$shop_wxpay_sub_mch_id);$order_subject = '扫码支付';break;
            }

            if($pay_res === false){
                throw new Exception('扫码支付失败');
            }

            $up_res = Db::name('order')->where(['order_id'=>$post['order_id']])->update(['order_status'=>100,'order_channel_id'=>$pay_res['order_channel_id'],
                'order_subject'=>$order_subject,
                'order_pay_time'=>$pay_res['order_pay_time'],
                'order_trade_no'=>$pay_res['order_trade_no'],'order_pay_realprice'=>$pay_res['total_amount']]);
            Log::write(json_encode($pay_res,JSON_UNESCAPED_UNICODE),'log');

            if(!$up_res){
                throw new Exception('更新支付状态失败');
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
                'product_code'=>'FACE_TO_FACE_PAYMENT','subject'=>'扫码支付','total_amount'=>$total_amount];
            $content = json_encode($content,JSON_UNESCAPED_UNICODE);

            $pay_result = SmilePay::barCodePay($content,$shop_alipay_app_auth_token);
        }else{
            $content = ['auth_code'=>$auth_code,'order_num'=>$order_num,
                'subject'=>'扫码支付','total_amount'=>$total_amount];
            $pay_result = WxPay::barcodePay($content,$shop_wxpay_sub_mch_id);
        }

        if(empty($pay_result)){
            return false;
        }

        return $pay_result;
    }


    /**
     * 保存商米刷脸支付结果
     * @author wzs
     * @return Json
     */
    public function saveFaceResult()
    {
        $post = Request::instance()->post();
        try{
            if(!isset($post['order_id']) || empty($post['order_id']) || !isset($post['pay_amount']) || empty($post['pay_amount'])
                || !isset($post['order_trade_no']) || empty($post['order_trade_no'])){
                throw new \Exception('请求参数错误');
            }

            $up_res = Db::name('order')->where(['order_id'=>$post['order_id']])->update(['order_status'=>100,'order_channel_id'=>1007,'order_subject'=>'刷脸支付','order_pay_time'=>time(),
                'order_num'=>$post['order_trade_no'],'order_pay_realprice'=>$post['pay_amount']]);
            if(!$up_res){
                throw new Exception('更新支付状态失败');
            }

            return json(['code'=>200,'msg'=>'请求成功','data'=>null]);
        }catch (\Exception $e){
            return json(['code'=>400,'msg'=>$e->getMessage(),'data'=>null]);
        }
    }
}