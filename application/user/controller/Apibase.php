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

    //接口数据验证_助手(直接终止程序)
    public function verifyPostDataHelper($user_id="")
    {
        if (!$user_id) {
            exit(json_encode(["code" => 0, "message" => "user_id 不允许为空", "data" => ""], JSON_UNESCAPED_UNICODE));
        }
        $rscheckuser = model("user")->checkuseractive($user_id);
        if ($rscheckuser["code"] == 1) {
            $userOne = $rscheckuser["data"]["userOne"];
        } else {
            exit(json_encode($rscheckuser, JSON_UNESCAPED_UNICODE));
        }
    }
}
