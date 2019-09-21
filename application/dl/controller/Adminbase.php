<?php
/**
 * 管理员后台默认基类
 * 2017-2-8 09:26:40
 */
namespace app\dl\controller;
use think\Controller;
use think\Cookie;
use think\Session;

class Adminbase extends Controller
{
    public function _initialize()
    {
        $this->checkIsLogin();
        $this->leftTree();
    }

    //基本是否登录检测
    public function checkIsLogin()
    {
        $request = request();
        $Adminbase = model("Adminbase");
        $rs = $Adminbase->isLogin();
        if ($rs == false) {
        //没有登录
            if (!($request->controller() == "Index" && $request->action() == "login")) {
                //不在登录页
                Cookie::set('HTTP_REFERER', $request->url(true), 3600);
                echo ("<script>top.window.location.href='" . url("index/login") . "'</script>");die();
            }

        } else {
            if (($request->controller() == "Index" && $request->action() == "login")) {
//在登录页上
                echo ("<script>top.window.location.href='" . url("index/index") . "'</script>");die();
            }

        }

    }

    //权限操作认证,没有权限就直接停止
    //Adminbase::checkActionAuth();//以当前controller/action配对url，检测"view"；url需要与菜单栏目一致
    //Adminbase::checkActionAuth("admin/index");//指定“controller/action”配对url，检测"view"；url需要与菜单栏目一致；推荐!!!
    //Adminbase::checkActionAuth(123);//指定菜单节点ID配对，检测"view"；
    public function checkActionAuth($pointId = "", $action = "", $admin_id = "")
    {
        if (input("session." . config("database")["database"] . "admin_id") == 10003) {
            return true;
        }
        if ($admin_id == "") {
            $admin_id = request()->session(config("database")["database"] . "admin_id");
        }

        $rs = model("AdminRole")->isHavePowder($pointId, $action, $admin_id);
        if ($rs["code"] == 0) {
            $this->error($rs["message"]);
        }
    }

    //左侧菜单树
    public function leftTree()
    {
        $this->assign('baseLeftMenuTree', model("Menu")->getTreeForLeftMenu(request()->session(config("database")["database"] . "admin_id")));
    }

    //文件上传（单个）
    public function uploadfile()
    {
        
        set_time_limit(0);
        $file = $_FILES['Filedata']; //得到传输的数据

        //得到文件名称
        $name = $file['name'];
        $type = strtolower(substr($name, strrpos($name, '.') + 1)); //得到文件类型，并且都转化成小写
        $allow_type = array('jpg', 'jpeg', 'gif', 'png', 'csv', 'xls', 'xlsx'); //定义允许上传的类型
        //判断文件类型是否被允许上传
        if (!in_array($type, $allow_type)) {
            //如果不被允许，则直接停止程序运行
            echo json_encode(["code"=>0,"message"=>$type."类型不允许上传"]);
            die();
        }
        //判断是否是通过HTTP POST上传的
        if (!is_uploaded_file($file['tmp_name'])) {
            //如果不是通过HTTP POST上传的
            echo json_encode(["code"=>0,"message"=>"不是通过HTTP POST上传的"]);
            die();
        }

        $imgPath = ROOT_PATH . 'public' . DS . 'uploads'. DS . date("Ymd") . DS;

        if (!file_exists($imgPath)) {
            mkdir($imgPath);
        };
        $newFileName = md5(getMillisecond() . rand(111111, 9999999)) . "." . $type;
        $fullpath = $imgPath . $newFileName;
        $thisPath = SCRIPT_DIR .'/uploads/'. date("Ymd") . "/" .$newFileName;

        //开始移动文件到相应的文件夹
        if (move_uploaded_file($file['tmp_name'], $fullpath)) {
            echo(json_encode(["code"=>1,"data"=>$thisPath]));
            die();
        } else {
            echo(json_encode(["code"=>0,"message"=>"上传失败"]));
            die();
        }
    }
}
