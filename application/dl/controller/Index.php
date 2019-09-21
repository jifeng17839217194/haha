<?php
namespace app\dl\controller;
use app\dl\controller\Adminbase;
use think\Cookie;
use think\Cache;
class Index extends Adminbase
{
    public $needcheckcodename = "nccn_dl";
    public function _initialize()
    {
        parent::_initialize();
        $this->needcheckcodename .= request()->ip();
    }

    public function index()
    {

        $this->assign("serverip", $_SERVER['REMOTE_ADDR']);
        $this->assign("softwave", $_SERVER['SERVER_SOFTWARE'] . "；<br>PHP版本：" . PHP_VERSION . "（系统安装最低要求>=5.6）");
        $this->assign("uploadinfo", "表单总限制：" . ini_get("post_max_size") . "；单文件限制：" . ini_get("upload_max_filesize"));
        return view();
    }

    public function logout()
    {
        $Admin = model("Admin");
        $Admin->doLogout();
        $this->success();
    }

    public function login()
    {
        $formaction=input("post.formaction");
        switch ($formaction) {
            case 'dologin':
                $username=input("post.username","");
                $password=input("post.password","",null);
                $checkcode=input("post.checkcode","",null);

                if(!$username)
                {
                    $this->error("用户名不可为空");
                }

                if(!$password)
                {
                    $this->error("密码不可为空");
                }

                if(!$checkcode&&cache($this->needcheckcodename)>3)
                {
                    $this->error("验证码不可为空");
                }

                if(!captcha_check($checkcode)&&cache($this->needcheckcodename)>3){   //验证失败
                    $this->error("验证码错误");
                };
                $Admin = model("Admin");
                $rs=$Admin->checkAndLogin($username,$password);
                if($rs["code"]==1)
                {
                    $Admin->afterBaseLogin($rs["data"]);
                    $this->success("",Cookie::get('HTTP_REFERER')?Cookie::get('HTTP_REFERER'):url("index"));
                }
                else
                {
                    if($cachevalue = cache($this->needcheckcodename))
                    {
                        cache($this->needcheckcodename, round($cachevalue + 1),300);
                    }
                    else
                    {
                        cache($this->needcheckcodename,1,300);
                    }

                    if(cache($this->needcheckcodename)>3)
                    {
                        return json(["code"=>0,"message"=>$rs["message"],"data"=>"","url"=>url('login')]);
                    }
                    else
                    {
                        return json(["code"=>0,"message"=>$rs["message"],"data"=>"","url"=>'#']);
                    }
                }

                break;
            
            default:
                $this->assign("needcheckcodecount",cache($this->needcheckcodename));
                break;
        }
    	return view();
    }

}
