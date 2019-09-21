<?php
namespace app\h5\controller;
use think\Controller;
class Index extends Controller
{
    // public function index()
    // {
    //     //传入收款码user_id
    //     return view();
    // }
    //页面初始化
    public function jsapipay()
    {
        //浏览器限制
        $userAgent = strtolower(request()->header("user-agent"));
        //判断微信or支付宝
        if (preg_match("/micromessenger/", $userAgent)) //微信
        {
            $channel = "wxpay";
        } else if (preg_match("/alipayclient/", $userAgent)) //支付宝
        {
            $channel = "alipay";
        } else //未知
        {
            echo "<script>alert('请用支付宝或微信扫描');</script>";die();
        }
        //__浏览器限制
        //判断是否已经授权
        if (!cookie("buyer_open_id")) {
            $request = request();
            cookie("oauthreturnurlfromurl", $request->url()); //临时保存来源页面
            $backurl = $request->domain() . "/index.php?s=" . $request->module() . "/" . $request->controller() . "/oauthreturnurl/channel/" . $channel;
            switch ($channel) {
                case 'wxpay': //微信授权 (统一授权目录，URL地址使用兼容模式(方便多加参数))
                    $redirect_uri = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=' . config("wxpay_app_id") . '&redirect_uri=' . urlencode($backurl) . '&response_type=code&scope=snsapi_base#wechat_redirect';
                    $this->redirect($redirect_uri);
                    break;
                case 'alipay': //支付宝授权 (统一授权目录，URL地址使用兼容模式(方便多加参数))
                    $redirect_uri = 'https://openauth.alipay.com/oauth2/publicAppAuthorize.htm?app_id=' . config("alipay_app_id") . '&scope=auth_base&redirect_uri=' . urlencode($backurl);
                    $this->redirect($redirect_uri);
                default:
                    echo "未知的支付通道" . $channel;die();
                    break;
            }
        }
        //__判断是否已经授权
        //echo cookie("buyer_open_id");die();
        //传入收款码user_id
        $user_id = input("user_id");
        if (!$user_id) {
            exit("付款二维码信息不完整，缺少user_id参数");
        }
        $rscheckuser = model("user")->checkuseractive($user_id);
        if ($rscheckuser["code"] == 1) {
            $userOne = $rscheckuser["data"]["userOne"];
        } else {
            exit($rscheckuser["message"]);
        }
        //store店铺的基本信息
        $storeOne                           = db("store")->where(["store_id" => $userOne->store_id])->field("store_name,store_id,store_pay_after_ad_active,store_address,store_open_reward")->find();
        $store["store_name"]                = $storeOne["store_name"];
        $store["user_id"]                   = $user_id;
        $store["store_address"]             = $storeOne["store_address"];
        $store["store_pay_after_ad_active"] = $storeOne["store_pay_after_ad_active"];
        $store["store_id"]                  = $storeOne["store_id"];
        $this->assign("buyer_open_id", (cookie("buyer_open_id")?:""));
        $this->assign("store", $store);
        //检测是不是打赏码
        $reward = input("reward", 0);
        switch ($reward) {
            case 1: //是打赏码
                //获取打赏数据
                if (!$storeOne["store_open_reward"]) {
                    echo "<script>alert('打赏功能未开启');</script>";die();
                }
                $rewardList = db("reward")->where(["reward_store_id" => $userOne->store_id])->order(["reward_cash asc"])->select();
                if (!$rewardList) {
                    echo "<script>alert('没有找到打赏方案');</script>";die();
                }
                $this->assign("realname", db("user")->where(["user_id" => $user_id])->value("user_realname"));
                $this->assign("rewardList", $rewardList);
                //直接转向 打赏页面
                return view("reward");
                break;

            case 2: //停车场;无车牌入场页面
                $auto_car_number = "无牌车".substr(hexdec(cookie("buyer_open_id")),0,14);//易泊支持17位的
                cookie("auto_car_number",$auto_car_number);
                $go_url = url("h5/carpark/parking_no_number_in_view?user_id=".$user_id);
                $this->redirect($go_url);die();
                break;
            case 3: //停车场;场内预付页面
                $go_url = url("h5/carpark/parkingfeeinput?user_id=".$user_id);
                //echo $go_url;die();
                $this->redirect($go_url);die();
                break;
            case 4: //停车场;出口立付 
                //获取当前通道等待支付的牌照
                $car_number=db("parking_channel")->where(["parking_channel_user_id"=>$user_id])->value("parking_channel_car_number");
                $go_url = url("h5/carpark/parkingfee?user_id=".$user_id."&car_number=".$car_number);
                //echo $go_url;die();
                $this->redirect($go_url);die();
                break;
            default:
                return view(); //普通付款码
                break;
        }
        //__检测是不是打赏码
    }

    //页面初始化
    public function appjsapipay()
    {
        //浏览器限制
        $userAgent = strtolower(request()->header("user-agent"));
        //判断微信or支付宝
        if (preg_match("/micromessenger/", $userAgent)) //微信
        {
            $channel = "wxpay";
        } else if (preg_match("/alipayclient/", $userAgent)) //支付宝
        {
            $channel = "alipay";
        } else //未知
        {
            echo "<script>alert('请用支付宝或微信扫描');</script>";die();
        }
        //__浏览器限制
        //判断是否已经授权
        if (!cookie("buyer_open_id")) {
            $request = request();
            cookie("oauthreturnurlfromurl", $request->url()); //临时保存来源页面
            $backurl = $request->domain() . "/index.php?s=" . $request->module() . "/" . $request->controller() . "/oauthreturnurl/channel/" . $channel;
            switch ($channel) {
                case 'wxpay': //微信授权 (统一授权目录，URL地址使用兼容模式(方便多加参数))
                    $redirect_uri = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=' . config("wxpay_app_id") . '&redirect_uri=' . urlencode($backurl) . '&response_type=code&scope=snsapi_base#wechat_redirect';
                    $this->redirect($redirect_uri);
                    break;
                case 'alipay': //支付宝授权 (统一授权目录，URL地址使用兼容模式(方便多加参数))
                    $redirect_uri = 'https://openauth.alipay.com/oauth2/publicAppAuthorize.htm?app_id=' . config("alipay_app_id") . '&scope=auth_base&redirect_uri=' . urlencode($backurl);
                    $this->redirect($redirect_uri);
                default:
                    echo "未知的支付通道" . $channel;die();
                    break;
            }
        }
        //__判断是否已经授权
        //echo cookie("buyer_open_id");die();
        //传入收款码user_id
        $user_id = input("user_id");
        if (!$user_id) {
            exit("付款二维码信息不完整，缺少user_id参数");
        }
        $rscheckuser = model("user")->checkuseractive($user_id);
        if ($rscheckuser["code"] == 1) {
            $userOne = $rscheckuser["data"]["userOne"];
        } else {
            exit($rscheckuser["message"]);
        }
        //store店铺的基本信息
        $storeOne                           = db("store")->where(["store_id" => $userOne->store_id])->field("store_name,store_id,store_pay_after_ad_active,store_address,store_open_reward")->find();
        $store["store_name"]                = $storeOne["store_name"];
        $store["user_id"]                   = $user_id;
        $store["store_address"]             = $storeOne["store_address"];
        $store["store_pay_after_ad_active"] = $storeOne["store_pay_after_ad_active"];
        $store["store_id"]                  = $storeOne["store_id"];
        $this->assign("buyer_open_id", (cookie("buyer_open_id")?:""));
        $this->assign("store", $store);

        return view();
    }

    //支付宝\微信授权回调地址
    public function oauthreturnurl()
    {
        $channel = input("channel");
        switch ($channel) {
            case 'alipay':
                $auth_code = input("auth_code");
                if (!$auth_code) {
                    echo "<script>alert('code不可为空');</script>";die();
                }
                $alipay  = model("alipay");
                $aop     = $alipay->requestBase();
                $request = new \AlipaySystemOauthTokenRequest();
                $request->setGrantType("authorization_code");
                $request->setCode($auth_code);
                $aopresult = $aop->execute($request);
                if (isset($aopresult->error_response)) {
                    $error_response = json_decode(json_encode($aopresult->error_response, JSON_UNESCAPED_UNICODE), 1);
                    echo "<script>alert('" . $error_response["sub_msg"] . $error_response["sub_code"] . "');</script>";die();
                } else {
                    //DEMO:{"code":1,"message":"","data":{"access_token":"authbseB738b7e96e7264aecac6e9490f98d8X23","alipay_user_id":"20881084931823017065082282315523","expires_in":31536000,"re_expires_in":31536000,"refresh_token":"authbseB1aba4c2eeb7a4e5ab5302d83bab51X23","user_id":"2088002258467231"}}
                    $rsJsonArray = json_decode(json_encode($aopresult->alipay_system_oauth_token_response, JSON_UNESCAPED_UNICODE), 1);
                    cookie("buyer_open_id", $rsJsonArray["user_id"]);
                    $this->redirect(cookie("oauthreturnurlfromurl"));
                }
                break;
            case 'wxpay':
                $auth_code = input("code");
                if (!$auth_code) {
                    echo "<script>alert('code不可为空');</script>";die();
                }
                $rsJsonString = httpsGet("https://api.weixin.qq.com/sns/oauth2/access_token?appid=" . config("wxpay_app_id") . "&secret=" . config("wxpay_app_secret") . "&code=" . $auth_code . "&grant_type=authorization_code");
                $rsJsonArray  = json_decode($rsJsonString, 1);
                if (isset($rsJsonArray["errcode"])) {
                    echo "<script>alert('" . $rsJsonArray["errmsg"] . "');</script>";die();
                } else {
                    cookie("buyer_open_id", $rsJsonArray["openid"]);
                    $this->redirect(cookie("oauthreturnurlfromurl"));
                }
                break;
            default:
                echo "<script>alert('未处理的授权通道" . $channel . "');</script>";die();
                break;
        }
    }
    public function payresult()
    {
        $storeid = input("storeid/d", 0);
        $cash    = input("cash", 0);
        if ($storeid > 0) {
            $storeone = db("store")->where(["store_id" => $storeid])->field("store_name,store_pay_after_ad")->find();
            $this->assign("storeone", $storeone);
        }
        $this->assign("cash", $cash);
        return view();
    }
}
