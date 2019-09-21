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
use think\Exception;
use think\Request;

class GoodsManage extends Controller
{
    public $shop_id;
    public $store_id;
    public $user_id;

    public function _initialize()
    {
//        if(!isset($_COOKIE['user_store_id']) || !isset($_COOKIE['user_id'])){
//            return json(['code'=>400,'msg'=>'请重新登陆','data'=>null]);
//        }

        $this->store_id = $_COOKIE['user_store_id'];
        $store_info = Db::name('store')->where(['store_id'=>$this->store_id])->field('store_shop_id')->find();
        $this->shop_id = $store_info['store_shop_id'];
        $this->user_id = $_COOKIE['user_id'];
    }

    /**
     * 商品展示
     * @author wzs
     * @return mixed
     */
    public function index()
    {
        $params = $this -> request -> param();
        $goods_list = Db::name('Goods')->alias('a')
            ->where(['a.shop_id'=>$this->shop_id,'a.store_id'=>$this->store_id,'a.is_delete'=>0])
            ->join('category','a.cate_id = category.category_id','left')
            ->field('a.goods_id,a.goods_name,a.price,a.goods_unit,a.sort,a.remark,a.img,category.category_name')
            ->order('a.sort,a.create_time desc')->paginate(10);
        $goods = [];
        if(!empty($goods_list)){
            $goods = $goods_list->toArray();
            foreach($goods['data'] as &$v){
                $v['price'] = $v['price']/100;
            }
        }

        // 在 render 前，使用appends方法保持分页条件
        $goods_list->appends($params);
        $this->assign('page', $goods_list->render());//单独提取分页出来
        $this->assign('goods_list',$goods);

        return $this->fetch();
    }

    /**
     * 添加商品
     * @author wzs
     * @return \think\response\Json
     */
    public function addGoods()
    {
        try{
            $request = Request::instance();

            $is_exsit = Db::name('Goods')->where(['shop_id'=>$this->shop_id,'store_id'=>$this->store_id,'goods_name'=>$request->post('goods_name'),'is_delete'=>0])->count();
            if($is_exsit){
                throw new Exception('已存在此商品,不能够重新添加');
            }

            $insert_data = ['cate_id'=>$request->post('cate_id'),
                'shop_id'=>$this->shop_id,
                'store_id'=>$this->store_id,
                'goods_name'=>$request->post('goods_name'),
                'price'=>$request->post('price') * 100,
                'goods_unit'=>$request->post('goods_unit'),
                'sort'=>$request->post('sort'),
                'img'=>$request->post('img'),
                'remark'=>$request->post('remark'),
                'create_time'=>time()];

            $insert_res = Db::name('Goods')
                ->insert($insert_data);

            if(!$insert_res){
                throw new Exception('添加失败');
            }

            return json(['code'=>200,'msg'=>'添加成功','url'=>'/user/goods_manage/index']);
        }catch (\Exception $e){
            return json(['code'=>400,'msg'=>$e->getMessage()]);
        }

    }

    /**
     * 单个商品信息查询
     * @author wzs
     * @param int $goods_id
     * @return \think\response\Json
     */
    public function getGoodsInfo(int $goods_id)
    {
        try{
            $res = Db::name('Goods')->where(['goods_id'=>$goods_id])->find();

            if(!$res){
                throw new Exception('请求失败');
            }
            $res['price'] = $res['price']/100;

            return json(['code'=>200,'msg'=>'请求成功','data'=>$res]);
        }catch (\Exception $e){
            return json(['code'=>400,'msg'=>$e->getMessage()]);
        }
    }

    /**
     * 修改商品
     * @author wzs
     * @return \think\response\Json
     */
    public function saveGoods()
    {
        try{
            $request = Request::instance();
            if(empty($request->post('goods_id'))){
                throw new Exception('参数错误');
            }

            $save_data = [
                'goods_name'=>$request->post('goods_name'),
                'price'=>$request->post('price') * 100,
                'cate_id'=>$request->post('cate_id'),
                'goods_unit'=>$request->post('goods_unit'),
                'sort'=>$request->post('sort'),
                'img'=>$request->post('img'),
                'remark'=>$request->post('remark')];
            $exist_goods = Db::name('Goods')->where(['cate_id'=>$request->post('cate_id'),'goods_name'=>$request->post('goods_name')])
                ->field('goods_id')->find();
            if(!empty($exist_goods) && $exist_goods['goods_id'] != $request->post('goods_id')){
                throw new Exception('此分类下商品已存在');
            }

            $del_res = Db::name('Goods')->where(['goods_id'=>$request->post('goods_id')])
                ->update($save_data);

            if($del_res === false){
                throw new Exception('修改失败');
            }

            return json(['code'=>200,'msg'=>'修改成功','url'=>'/user/goods_manage/index']);
        }catch (\Exception $e){
            return json(['code'=>400,'msg'=>$e->getMessage()]);
        }
    }

    /**
     * 删除商品
     * @author wzs
     * @param int $goods_id
     * @return \think\response\Json
     */
    public function delGoods(int $goods_id)
    {
        try{
            $del_res = Db::name('Goods')->where(['goods_id'=>$goods_id])
                ->update(['is_delete'=>1]);

            if(!$del_res){
                throw new Exception('删除失败');
            }

            return json(['code'=>200,'msg'=>'删除成功','url'=>'index']);
        }catch (\Exception $e){

            return json(['code'=>400,'msg'=>$e->getMessage(),'data'=>null]);
        }
    }

    /**
     * 分类展示
     * @author wzs
     * @return mixed
     */
    public function cateList()
    {
        $params = $this -> request -> param();
        $cate_list = Db::name('Category')
            ->where(['shop_id'=>$this->shop_id,'store_id'=>$this->store_id,'is_delete'=>0])->order('sort,create_time desc')->paginate(10);

        $this->assign('cate_list',$cate_list);
        $cate_list->appends($params);
        $this->assign('page', $cate_list->render());//单独提取分页出来
        $this->assign('cate_list',$cate_list->toArray());

        return $this->fetch();
    }


    /**
     * 添加分类
     * @author wzs
     * @return \think\response\Json
     */
    public function addCate()
    {
        try{
            $request = Request::instance();
            $is_exsit = Db::name('Category')->where(['shop_id'=>$this->shop_id,'store_id'=>$this->store_id,'category_name'=>$request->post('category_name'),'is_delete'=>0])->count();
            if($is_exsit){
                throw new Exception('已存在此分类,不能够重新添加');
            }

            $insert_data = [
                'category_name'=>$request->post('category_name'),
                'shop_id'=>$this->shop_id,
                'store_id'=>$this->store_id,
                'img'=>$request->post('img'),
                'sort'=>$request->post('sort'),
                'create_time'=>time()];
            $insert_res = Db::name('Category')
                ->insert($insert_data);

            if(!$insert_res){
                throw new Exception('添加失败');
            }

            return json(['code'=>200,'msg'=>'添加成功','url'=>'/user/goods_manage/catelist']);
        }catch (\Exception $e){
            return json(['code'=>400,'msg'=>$e->getMessage()]);
        }
    }

    /**
     * 单个分类信息查询
     * @author wzs
     * @param int $cate_id
     * @return \think\response\Json
     */
    public function getCateInfo(int $cate_id)
    {
        try{
            $res = Db::name('Category')->where(['category_id'=>$cate_id])->find();

            if(!$res){
                throw new Exception('请求失败');
            }

            return json(['code'=>200,'msg'=>'请求成功','data'=>$res]);
        }catch (\Exception $e){
            return json(['code'=>400,'msg'=>$e->getMessage()]);
        }
    }

    /**
     * 修改分类
     * @author wzs
     * @return \think\response\Json
     */
    public function saveCate()
    {
        try{
            $request = Request::instance();
            $cate_id = $request->post('category_id');
            if(empty($cate_id)){
                throw new Exception('参数错误');
            }

            $category_id = Db::name('Category')
                ->where(['shop_id'=>$this->shop_id,'store_id'=>$this->store_id,'category_name'=>$request->post('category_name'),'is_delete'=>0])
                ->value('category_id');
            if(!empty($category_id) && $category_id != $cate_id){
                throw new Exception('已存在此分类,不能够重新添加');
            }

            $save_data = [
                'category_name'=>$request->post('category_name'),
                'img'=>$request->post('img'),
                'sort'=>$request->post('sort')];

            $del_res = Db::name('Category')->where(['category_id'=>$cate_id])
                ->update($save_data);

            if($del_res === false){
                throw new Exception('修改失败');
            }

            return json(['code'=>200,'msg'=>'修改成功','url'=>'/user/goods_manage/catelist']);
        }catch (\Exception $e){
            return json(['code'=>400,'msg'=>$e->getMessage(),'data'=>null]);
        }
    }

    /**
     * 删除分类
     * @author wzs
     * @param $cate_id
     * @return \think\response\Json
     */
    public function delCate($cate_id)
    {
        try{
            $is_exist = Db::name('Goods')->where(['cate_id'=>$cate_id,'is_delete'=>0])
                ->count();
            if($is_exist){
                throw new Exception('分类下有商品,不能删除');
            }

            $del_res = Db::name('Category')->where(['category_id'=>$cate_id])
                ->update(['is_delete'=>1]);

            if(!$del_res){
                throw new Exception('删除失败');
            }

            return json(['code'=>200,'msg'=>'删除成功','data'=>null,'url'=>'catelist']);
        }catch (\Exception $e){
            return json(['code'=>400,'msg'=>$e->getMessage(),'data'=>null]);
        }

    }

    /**
     * 上传文件
     * @author wzs
     * @return \think\response\Json
     */
    public function uploadImage()
    {
        try{
            set_time_limit(0);
            $file = $_FILES['img']; //得到传输的数据

            //得到文件名称
            $name = $file['name'];
            $type = strtolower(substr($name, strrpos($name, '.') + 1)); //得到文件类型，并且都转化成小写
            $allow_type = array('jpg', 'jpeg', 'gif', 'png'); //定义允许上传的类型
            //判断文件类型是否被允许上传
            if (!in_array($type, $allow_type)) {
                //如果不被允许，则直接停止程序运行
                throw new Exception("类型不允许上传");
            }
            //判断是否是通过HTTP POST上传的
            if (!is_uploaded_file($file['tmp_name'])) {
                //如果不是通过HTTP POST上传的
                throw new Exception("不是通过HTTP POST上传的");
            }

            $imgPath = ROOT_PATH . 'public' . DS . 'uploads'. DS . date("Ymd") . DS;

            if (!file_exists($imgPath)) {
                mkdir($imgPath,0777,true);
            };
            $newFileName = md5(getMillisecond() . rand(111111, 9999999)) . "." . $type;
            $fullpath = $imgPath . $newFileName;
            $thisPath = config('site_url') .'/uploads/'. date("Ymd") . "/" .$newFileName;
            //开始移动文件到相应的文件夹
            if (!move_uploaded_file($file['tmp_name'], $fullpath)) {
                throw new Exception("上传失败");
            }
            return json(['code'=>200,'msg'=>'上传成功','data'=>$thisPath]);

        }catch (\Exception $e){
            return json(['code'=>400,'msg'=>$e->getMessage(),'data'=>null]);
        }
    }

    /**
     * 获取分类下拉列表
     * @author wzs
     * @return \think\response\Json
     */
    public function getCateList()
    {
        $cate_list = Db::name('Category')->alias('a')
            ->where(['a.shop_id'=>$this->shop_id,'a.store_id'=>$this->store_id,'is_delete'=>0])->field('category_id,category_name')->select();

        if ($cate_list) {
            return json(['code'=>200,'msg'=>'请求成功','data'=>$cate_list]);
        }else {
            return json(['code'=>400,'msg'=>'请求失败','data'=>null]);
        }
    }

    public function add()
    {
        $params = $this -> request -> param();
        $cate_list = Db::name('Category')->alias('a')
            ->where(['a.shop_id'=>$this->shop_id,'a.store_id'=>$this->store_id,'is_delete'=>0])->order('sort,create_time desc')->paginate(10);

        $this->assign('cate_list',$cate_list);

        return $this->fetch();
    }

    public function addsort()
    {
        return $this->fetch();
    }


    public function edit(int $goods_id)
    {
        $cate_list = Db::name('Category')->alias('a')
            ->where(['a.shop_id'=>$this->shop_id,'a.store_id'=>$this->store_id,'is_delete'=>0])->order('sort,create_time desc')->paginate(10);

        $this->assign('cate_list',$cate_list);

        try{
            $res = Db::name('Goods')->where(['goods_id'=>$goods_id])->find();

            if(!$res){
                throw new Exception('请求失败');
            }
            $res['price'] = $res['price']/100;

            $this->assign('good',$res);
            return $this->fetch();
        }catch (\Exception $e){
            return json(['code'=>400,'msg'=>$e->getMessage()]);
        }
    }



    public function editsort(int $category_id)
    {
        try{
            if(empty($category_id)){
                throw new Exception('参数错误');
            }
            $res = Db::name('Category')->where(['category_id'=>$category_id])->find();

            if(!$res){
                throw new Exception('请求失败');
            }

            $this->assign('cate_info',$res);
            return $this->fetch();
        }catch (\Exception $e){
            return json(['code'=>400,'msg'=>$e->getMessage()]);
        }
    }

}