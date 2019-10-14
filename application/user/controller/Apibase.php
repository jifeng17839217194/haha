<?php
namespace app\user\controller;
use think\Controller;

class Apibase extends Controller
{
    public function _initialize()
    {
        // 指定允许其他域名访问  允许ajax 跨域
        header('Access-Control-Allow-Origin:*');
        // 响应类型
        header('Access-Control-Allow-Methods:POST');
        // 响应头设置
        header('Access-Control-Allow-Headers:x-requested-with,content-type');
        //接口授权检测

        //登陆接口不验证
    }

}
