<?php
//代理商管理后台
namespace app\agent\controller;
use app\agent\controller\Agentbase;
use think\Cookie;
use think\Cache;

class Index extends Agentbase
{
    public $needcheckcodename = "nccn_agent";
	public function _initialize()
    {
        parent::_initialize();
        $this->needcheckcodename .= request()->ip();
    }

    public function index()
    {
        /*$this->assign("serverip", $_SERVER['REMOTE_ADDR']);
        $this->assign("softwave", $_SERVER['SERVER_SOFTWARE'] . "；<br>PHP版本：" . PHP_VERSION . "（系统安装最低要求>=5.4）");
        $this->assign("uploadinfo", "表单总限制：" . ini_get("post_max_size") . "；单文件限制：" . ini_get("upload_max_filesize"));*/

        $this->assign("one",model("Sysconfig")->getConfig());
        return view();
    }

    public function updatemyselfpwd()
    {
        $password=input("promptvalue","",null);
        if(strlen($password)<6)
        {
            return ["code"=>0,"message"=>"密码长度必需长于6位","data"=>""];
        }
        $agent = model("agent");
        $saveData["agent_password"] = model("user")->passwordSetMd5($password);
        model("agent")->save($saveData, ['agent_id' => request()->session(config("database")["database"]."agent_id")]);
        return ["code" => 1, "message" => "更新成功", "wait" => 1, "url" =>""];
    }

    public function logout()
    {
        $Agent = model("agent");
        $Agent->doAgentLogout();
        $this->success();
    }

    public function login()
    {
        $formaction=input("post.formaction");
        switch ($formaction) {
            case 'dologin':
                $username=input("post.agentname","");
                $password=input("post.agentpassword","",null);
                $checkcode=input("post.checkcode","",null);

                if(!$username)
                {
                    return ["code"=>0,"message"=>"用户名不可为空","data"=>""];
                }

                if(!$password)
                {
                    return ["code"=>0,"message"=>"密码不可为空","data"=>""];
                }

                if(!$checkcode&&cache($this->needcheckcodename)>3)
                {
                    return ["code"=>0,"message"=>"验证码不可为空","data"=>""];
                }

                if(!captcha_check($checkcode)&&cache($this->needcheckcodename)>3){   //验证失败
                    return ["code"=>0,"message"=>"验证码错误","data"=>""];
                };

                $Agent = model("agent");
                $rs=$Agent->agentcheckAndLogin($username,$password);
                if($rs["code"]==1)
                {
                    //cache($this->needcheckcodename,null);//不要清楚，避免形成无验证码的漏洞 2017年3月27日
                    $Agent->afterAgentLoginPC($rs["data"]);
                    $this->success("",Cookie::get('HTTP_REFERER_Agent')?Cookie::get('HTTP_REFERER_Agent'):url("index"));
                }
                else
                {
                    if(cache($this->needcheckcodename))
                    {
                        Cache::inc($this->needcheckcodename);
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
