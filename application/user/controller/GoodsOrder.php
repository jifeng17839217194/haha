<?php
/**
 * Created by PhpStorm.
 * User: ZGL
 * Date: 2019/5/15
 * Time: 16:19
 */

namespace app\user\controller;


use think\Controller;
use think\Db;
use think\Request;

class GoodsOrder extends Controller
{
    public $shop_id = 10041;
    public $store_id = 1062;

    /**
     * 商品展示
     */
    public function index()
    {
        $merchant_list = Db::name('Goods')
            ->where(['is_delete'=>0])->paginate(10)
            ->all();
//        var_dump($merchant_list);exit;
        return $this->fetch();
    }

    /**
     * 添加商品
     */
    public function addGoods()
    {
        $request = Request::instance();
        var_dump(1);exit;
        $insert_data = ['cate_id'=>$request['cate_id'],
                        'goods_name'=>$request['goods_name'],
                        'price'=>$request['price'] * 100,
                        'goods_unit'=>$request['goods_unit'],
                        'sort'=>$request['sort'],
                        'img'=>$request['img'],
                        'remark'=>$request['remark'],
                        'create_time'=>time()];
        $insert_res = Db::name('Goods')
            ->insert($insert_data);

        if($insert_res){
            return json(['code'=>200,'msg'=>'添加成功']);
        }

        return json(['code'=>400,'msg'=>'添加失败']);
    }

    /**
     * 删除商品
     */
    public function delMerchant()
    {
        $request = Request::instance();

        $del_res = Db::name('Goods')->where(['goods_id'=>$request['goods_id']])
            ->update(['is_delete'=>1]);

        if($del_res){
            return json(['code'=>200,'msg'=>'删除成功']);
        }

        return json(['code'=>400,'msg'=>'删除失败']);
    }

    /**
     * 分类展示
     */
    public function cateList()
    {
        $cate_list = Db::name('Category')
            ->where(['is_delete'=>0])->paginate(10)
            ->all();
        $this->assign('cate_list',$cate_list);

        return $this->fetch();
    }

    /**
     * 添加分类
     */
    public function addCate()
    {
        $request = Request::instance();

        $insert_data = [
                            'category_name'=>'零食',
                            'shop_id'=>$this->shop_id,
                            'store_id'=>$this->store_id,
                            'img'=>'http://www.baidu.com',
                            'sort'=>1,
                            'create_time'=>time()];
        $insert_res = Db::name('Category')
            ->insert($insert_data);

        if($insert_res){
            return json(['code'=>200,'msg'=>'添加成功']);
        }

        return json(['code'=>400,'msg'=>'添加失败']);
    }

    /**
     * 删除分类
     */
    public function delCate()
    {
        $request = Request::instance();
        $del_res = Db::name('Category')->where(['category_id'=>$request['category_id']])
            ->update(['is_delete'=>1]);

        if($del_res){
            return json(['code'=>200,'msg'=>'删除成功']);
        }

        return json(['code'=>400,'msg'=>'删除失败']);
    }
}