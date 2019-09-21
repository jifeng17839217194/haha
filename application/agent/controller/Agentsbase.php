<?php
/**
 * 代理商后台默认基类
 *  2017-3-27 09:13:14
 */
namespace app\agent\controller;
use think\Controller;
use think\Cookie;
use think\Session;

class Agentbase extends Controller
{
    public function _initialize()
    {
        $this->checkIsLogin();
    }

    public function getMyAgentsId()
    {
        return session("agents_id");
    }


    //基本是否登录检测
    public function checkIsLogin()
    {
        $request = request();
        $Agentsbase = model("Agentsbase");
        $rs = $Agentsbase->isLogin();
        if ($rs == false) {
        //没有登录
            if (!($request->controller() == "Index" && $request->action() == "login")) {
                //不在登录页
                Cookie::set('HTTP_REFERER_Manage', $request->url(true), 3600);
                echo ("<script>top.window.location.href='" . url("index/login") . "'</script>");die();
            }

        } else {
            if (($request->controller() == "Index" && $request->action() == "login")) {
//在登录页上
                echo ("<script>top.window.location.href='" . url("index/index") . "'</script>");die();
            }

        }

    }


    //文件上传（单个）
    public function uploadfile()
    {
        
        set_time_limit(0);
        $file = $_FILES['Filedata']; //得到传输的数据

        //得到文件名称
        $name = $file['name'];
        $type = strtolower(substr($name, strrpos($name, '.') + 1)); //得到文件类型，并且都转化成小写
        $allow_type = array('jpg', 'jpeg', 'gif', 'png'); //定义允许上传的类型
        //判断文件类型是否被允许上传
        if (!in_array($type, $allow_type)) {
            //如果不被允许，则直接停止程序运行
            echo json_encode(["code"=>0,"msg"=>$type."类型不允许上传"]);
            die();
        }
        //判断是否是通过HTTP POST上传的
        if (!is_uploaded_file($file['tmp_name'])) {
            //如果不是通过HTTP POST上传的
            echo json_encode(["code"=>0,"msg"=>"不是通过HTTP POST上传的"]);
            die();
        }

        $imgPath = ROOT_PATH . 'public' . DS . 'uploads'. DS . date("Ymd") . DS;

        if (!file_exists($imgPath)) {
            mkdir($imgPath);
        };
        $newFileName = (time()-1484396433) . rand(1, 999) . "." . $type;
        $fullpath = $imgPath . $newFileName;
        $thisPath = SCRIPT_DIR .'/uploads/'. date("Ymd") . "/" .$newFileName;

        //开始移动文件到相应的文件夹
        if (move_uploaded_file($file['tmp_name'], $fullpath)) {
            echo(json_encode(["code"=>1,"data"=>$thisPath]));
            die();
        } else {
            echo(json_encode(["code"=>0,"msg"=>"上传失败"]));
            die();
        }
    }
}
