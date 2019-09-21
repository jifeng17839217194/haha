<?php
namespace app\api\controller;
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

        $this->isFromApp();
    }

    /**
     * [checkAndUploadApp APP升级版本检测]
     * @return [type] [description]
     */
    public function checkAndUploadApp()
    {
        //版本控制
        $appversion  = input("appversion", 0);
        $skipversion = input("skipversion", 0); //跳过版本检测
        $appos       = input("appos");
        //if($appversion&&$appos=="android"&&!$skipversion)
        if ($appversion && !$skipversion) {

            $Appupdate = model("appupdate")->getOne()->toArray();
            if ($Appupdate) {
                /*echo $Appupdate[$appos."_version"]."<br>";
                echo $appversion;
                die();*/
                $comparesult = version_compare($Appupdate["appupdate_" . $appos . "_version"], $appversion);
                //echo($comparesult);die();
                header('Content-type:application/json');
                if ($comparesult == 1) {
                    $rsdata = array("version" => $Appupdate["appupdate_" . $appos . "_version"], "updateTip" => $Appupdate["appupdate_" . $appos . "_updatetip"], "time" => $Appupdate["appupdate_" . $appos . "_time"], "source" => $Appupdate["appupdate_" . $appos . "_source"], "absupdate" => 0);

                    echo json_encode(["code" => 1, "data" => $rsdata, "message" => "版本过低"], JSON_UNESCAPED_UNICODE);die(); //
                } else {
                    echo json_encode(["code" => 0, "data" => "", "message" => "没有新版本"], JSON_UNESCAPED_UNICODE);die(); //
                }
            }
        }
    }


    //接口数据验证
    public function verifyPostData($token="")
    {
        $postdata = isset($_POST)?$_POST:[];
        if(isset($postdata["sign"]))
        {
            $guest_sign = strtolower($postdata["sign"]);
            $server_sign = strtolower(publicRequestjiami($postdata,$token));

            if($guest_sign!=$server_sign)
            {
                return ["code"=>0,"message"=>"未授权或登入已过期，尝试重新登入","data"=>$server_sign];
            }
            else
            {
                return ["code"=>1,"message"=>"","data"=>""];
            }
        }
        else
        {
            return ["code"=>0,"message"=>"参数必需含有sign(加密签名)","data"=>""];
        }
    }

    //接口数据验证_助手(直接终止程序)
    public function verifyPostDataHelper($user_id="")
    {
        if(!$user_id)
        {
            exit(json_encode(["code"=>0,"message"=>"user_id 不允许为空","data"=>""],JSON_UNESCAPED_UNICODE));   
        }
        $rscheckuser= model("user")->checkuseractive($user_id);
        if($rscheckuser["code"]==1)
        {
            $userOne = $rscheckuser["data"]["userOne"];
        }
        else
        {
            exit(json_encode($rscheckuser,JSON_UNESCAPED_UNICODE));   
        }
        
        //签名验证

        $token = $userOne->user_token;
        //exit(json_encode(["code"=>0,"message"=>$userOne->user_token,"data"=>""]));
        $rs=$this->verifyPostData($token);
        if($rs["code"]==0)
        {
            exit(json_encode($rs,JSON_UNESCAPED_UNICODE));
        }
        else
        {
            return $userOne;//数据共享给其它用下，避免二次重复数据库查询
        }
    }

    /**
     * [接口数据验证_助手(直接终止程序)]
     * 用于停车场接口
     * @return [type] [description]
     */
    /*public function verifyPostDataHelperByCarpark()
    {
        //签名验证
        $token = config("carpark_token");
        $rs=$this->verifyPostData($token);
        if($rs["code"]==0)
        {
            //exit(json_encode($rs,JSON_UNESCAPED_UNICODE));
        }
        else
        {
            return $userOne;//数据共享给其它用下，避免二次重复数据库查询
        }
    }*/

    //图片压缩
    // public function imgautosize()
    // {
    //     $imgsrc=$_GET["imgsrc"];
    //     $w=$_GET["w"];
    //     $h=$_GET["h"];
    //     $type=$_GET["type"];
    //     $imgArray = explode("/Uploads/",$imgsrc);
    //     $srcFile = "Uploads/".$imgArray[1];

    //     $toFileArray=explode(".",$srcFile);
    //     $toFile = $toFileArray[0]."_".$w."_".$w.".".$toFileArray[1];
    //     $toFile = str_replace("attached","autocreate",$toFile);

    //     if(!is_file($toFile))
    //     {
    //     createDir(dirname($toFile));
    //     }
    //     $toFile= geterrpicsrconfig($srcFile, $w , $h , $type);
    //     redirect($toFile);
         

    // }

    public function isFromApp()
    {
        $requestFrom = request()->header("requestFrom");
        switch ($requestFrom) {
            case 'inner_http_worker': //内部通信,都是post过来的
                //采用其他的加密方式
                $signature = request()->header("signature");
                if ($signature != publicRequestjiami($_POST)) {
                    echo "数据加密与客户端请求不一致" . $signature;
                    die();
                }

                break;

            default: //来自APP
                // $getToken = input("app_token");
                // $getTimes = input("times", "0");
                // if (abs(substr($getTimes, 0, 10) - time()) > 60 * 10) {

                //     //$tipsmsg="请求过期或手机时间不正确\r\n请求".(date("Y-m-d H:i:s",$getTimes/1000))."\r\n响应".date("Y-m-d H:i:s");
                //     $tipsmsg = "请把手机时间设置为北京时间!\r\n请参考" . date("Y-m-d H:i:s");
                //     if (strpos($_SERVER["SERVER_NAME"], ".168.") <= 0 && $_SERVER["SERVER_NAME"] != "127.0.0.1") //本地开发不要检测
                //     {
                //         header('Content-type:application/json');
                //         echo json_encode(["code" => -1001, "data" => "", "message" => $tipsmsg], JSON_UNESCAPED_UNICODE);die(); //保证单条请求url只有30秒的生命期
                //         //采用阻断的方式返回
                //     }

                // }
                // $getRnds = input("rnds");

                // //验证Token合法性
                // $serverToken = md5(config("app_token") . ($getTimes . $getRnds . config("app_token")));
                // if (strpos($_SERVER["SERVER_NAME"], ".168.") <= 0 && $_SERVER["SERVER_NAME"] != "127.0.0.1") //本地开发不要检测
                // {
                //     if ($getToken !== $serverToken) {
                //         header('Content-type:application/json');
                //         echo json_encode(["code" => -1002, "data" => "", "message" => "app_token错误-1002"], JSON_UNESCAPED_UNICODE);die(); //保证单条请求url只有30秒的生命期
                //         //采用阻断的方式返回
                //     }

                // }
                
                break;
        }

    }


    
    // public function isUser()
    // {
    //     $currentUserToken = input("currentUserToken");
    //     if (empty($currentUserToken)) {
    //         header('Content-type:application/json');
    //         echo json_encode(["code" => -1002, "data" => array("error" => 100), "message" => "请登陆"], JSON_UNESCAPED_UNICODE);die(); //采用阻断的方式返回
    //     }
    //     $whereisUser["user_token"] = $currentUserToken;
    //     if (input("currentUserId", 0) > 0) {
    //         $whereisUser["user_id"] = input("currentUserId", 0);
    //     }
    //     //多一
    //     $one = model("User")->where($whereisUser)->find();
    //     if ($one) {
    //         if ($one["user_active"] == 0) {
    //             header('Content-type:application/json');
    //             echo json_encode(["code" => -1002, "data" => "", "message" => "账号已禁用"], JSON_UNESCAPED_UNICODE);die(); //采用阻断的方式返回
    //         }
    //         return $one;
    //     } else {
    //         header('Content-type:application/json');
    //         echo json_encode(["code" => -1002, "data" => "", "message" => "身份认证错误"], JSON_UNESCAPED_UNICODE);die(); //采用阻断的方式返回
    //     }
    // }

    //只是sellor端判断普通用户是否登录了; 2017-3-30 14:23:18
    // public function isSeller()
    // {
    //     $currentUserToken = input("currentUserToken");
    //     if (empty($currentUserToken)) {
    //         header('Content-type:application/json');
    //         echo json_encode(["code" => -1002, "data" => array("error" => 100), "message" => "请登陆"], JSON_UNESCAPED_UNICODE);die(); //采用阻断的方式返回
    //     }
    //     $whereisUser["user_token_seller"] = $currentUserToken;
    //     if (input("currentUserId", 0) > 0) {
    //         $whereisUser["user_id"] = input("currentUserId", 0);
    //     }
    //     //多一
    //     $one = model("User")->where($whereisUser)->find();
    //     if ($one) {
    //         if ($one["user_active"] == 0) {
    //             header('Content-type:application/json');
    //             echo json_encode(["code" => -1002, "data" => "", "message" => "账号已禁用"], JSON_UNESCAPED_UNICODE);die(); //采用阻断的方式返回
    //         }
    //         return $one;
    //     } else {
    //         header('Content-type:application/json');
    //         echo json_encode(["code" => -1002, "data" => "", "message" => "身份认证错误"], JSON_UNESCAPED_UNICODE);die(); //采用阻断的方式返回
    //     }
    // }

    //上传图片
    public function upload()
    {
        /*import('ORG.Net.UploadFile');
        //Log::record('调试的SQL：'.$SQL, Log::SQL);
        //Log::save();
        $upload = new UploadFile(); // 实例化上传类
        $upload->maxSize = 314572800; // 设置附件上传大小
        $upload->thumb = true;
        $upload->thumbMaxWidth = 150;
        $upload->thumbMaxHeight = 150;
        $upload->allowExts = array('gif', 'jpg', 'jpeg', 'png', 'bmp'); // 设置附件上传类型
        $upload->savePath = './Uploads/attached/' . date("Ymd", time()) . "/"; // 设置附件上传目录
        createDir('./Uploads/attached/' . date("Ymd", time()) . "/");
        if (!$upload->upload()) {
        // 上传错误提示错误信息
        $this->ajaxReturn("", $upload->getErrorMsg(), 0);
        } else {
        // 上传成功 获取上传文件信息
        $info = $upload->getUploadFileInfo();
        $this->ajaxReturn($info[0]["savepath"] . $info[0]["savename"], "ok", 1);
         */

        $file = request()->file('file');
        $info = $file->validate(['size' => 314572800, 'ext' => 'jpg,png,gif,bmp,amr,mp3'])->move(ROOT_PATH . 'public' . DS . 'uploads');
        if ($info) {
            // 成功上传后 获取上传信息
            // 输出 jpg
            //echo $info->getExtension();
            // 输出 20160820/42a79759f284b767dfcb2a0197904287.jpg
            //echo $info->getSaveName();
            // 输出 42a79759f284b767dfcb2a0197904287.jpg
            //echo $info->getFilename();
            return json(["code" => 1, "message" => "", "data" => "./uploads/" . str_replace("\\", "/", $info->getSaveName())]);
        } else {
            // 上传失败获取错误信息
            //echo $file->getError();
            return json(["code" => 0, "message" => $file->getError(), "data" => ""]);
        }

    }

    public function _empty($name)
    {
        return json(["code" => 0, "message" => $name . "方法，不存在"]);
    }
}
