<?php
namespace app\cmd\controller;

use think\Controller;

//统一授权转向模块
class Oauth extends Controller
{
    public function _initialize()
    {
        // if (time() > 1501383645) {
        //     parent::_initialize();
        // }
    }
    //组合出阿里的授权链接
    //https://docs.open.alipay.com/common/105193
    public function alipayset()
    {
        //浏览器限制
        $userAgent = strtolower(request()->header("user-agent"));
        //判断微信or支付宝
        if (preg_match("/micromessenger/", $userAgent)) //微信
        {
            $channel = "wxpay";
            echo "<script>alert('请用支付宝APP扫一扫');</script>";die();
        }
        //__浏览器限制
        //
        $uid = input("uid");
        switch (input("app", "")) {
            case 'auto_pay': //无感支付，调用生活号（的当面付授权）
                $returnUrl    = request()->domain() . "?s=/cmd/oauth/alipayapptoappauth/uid/" . $uid . "/app/auto_pay";
                $redirect_uri = "https://openauth.alipay.com/oauth2/appToAppAuth.htm?app_id=" . config('alipay_shh_app_id') . "&redirect_uri=" . urlencode($returnUrl);
                $shopOne      = model("shop")->where(["shop_id_token" => $uid])->field(["shop_alipay_app_auth_token_auto_pay", "shop_name", "shop_alipay_account"])->find();
                if ($shopOne->shop_alipay_app_auth_token_auto_pay) {
                    $this->assign("status", 0);
                    $this->assign("message", $shopOne->shop_name . "已经授权过支付宝，不可重复操作");
                    return view("h5@index/alipayoauthresult");
                }
                break;
            default:
                $returnUrl    = request()->domain() . "?s=/cmd/oauth/alipayapptoappauth/uid/" . $uid;
                $redirect_uri = "https://openauth.alipay.com/oauth2/appToAppAuth.htm?app_id=" . config('alipay_app_id') . "&redirect_uri=" . urlencode($returnUrl);
                $shopOne      = model("shop")->where(["shop_id_token" => $uid])->field(["shop_alipay_app_auth_token", "shop_name", "shop_alipay_account"])->find();
                if ($shopOne->shop_alipay_app_auth_token) {
                    $this->assign("status", 0);
                    $this->assign("message", $shopOne->shop_name . "已经授权过支付宝，不可重复操作");
                    return view("h5@index/alipayoauthresult");
                }
                break;
        }
        echo "<script>alert('请确认当前是使用“" . $shopOne->shop_name . "的" . $shopOne->shop_alipay_account . "”支付宝账号授权');location.href='" . $redirect_uri . "';</script>";die();
        //$this->redirect($redirect_uri); //转向支付宝
    }
    /**
     * 支付宝 第三方应用授权(单面付) 回调地址
     * https://docs.open.alipay.com/common/105193
     * @return [type] [description]
     */
    public function alipayapptoappauth()
    {
        $app_auth_code = input("app_auth_code", "");
        $app_id        = input("app_id", "");
        $shop_id_token = input("uid", "");
        $app           = input("app", "");
        if (!$app_auth_code || !$app_id || !$shop_id_token) {
            $this->assign("status", 0);
            $this->assign("message", "支付宝回调参数错误");
            return view("h5@index/alipayoauthresult");
        }
        $shopOne = model("shop")->where(["shop_id_token" => $shop_id_token])->field(["shop_id", "shop_alipay_app_auth_token", "shop_alipay_app_auth_token_auto_pay", "shop_alipay_auth_app_id", "shop_alipay_auth_app_id_auto_pay"])->find();
        if (!$shopOne) {
            $this->assign("status", 0);
            $this->assign("message", "商户不存在");
            return view("h5@index/alipayoauthresult");
        }

        $BizContentArray = [
            "grant_type" => "authorization_code",
            "code"       => $app_auth_code,
        ];

        switch ($app) {
            case 'auto_pay':
                if ($shopOne->shop_alipay_app_auth_token_auto_pay) {
                    $this->assign("status", 0);
                    $this->assign("message", "当前停车商户已经授权，不重复操作");
                    return view("h5@index/alipayoauthresult");
                }
                $rs = model("alipay")->requestSHH("AlipayOpenAuthTokenAppRequest", $BizContentArray);
                break;
            default:
                if ($shopOne->shop_alipay_app_auth_token) {
                    $this->assign("status", 0);
                    $this->assign("message", "当前商户已经授权，不重复操作");
                    return view("h5@index/alipayoauthresult");
                }
                $rs = model("alipay")->request("AlipayOpenAuthTokenAppRequest", $BizContentArray);
                break;
        }

        if ($rs["code"] != 10000) {
            $this->error($rs["msg"]);
        } else {
            //授权成功
            switch ($app) {
                case 'auto_pay'://停车行业，已经停用 ，2018-12-17 14:51:29
                    $shopOne->shop_alipay_app_auth_token_auto_pay = $rs["app_auth_token"];
                    $shopOne->shop_alipay_auth_app_id_auto_pay    = $rs["auth_app_id"];
                    $shopOne->shop_alipay_seller_id               = $rs["user_id"]; //商户的pid
                    $shopOne->save();
                    $shop_attr                                                  = db("shop_attr");
                    $newData["shop_attr_shop_id"]                               = $shopOne->shop_id;
                    $newData["shop_attr_app_auth_token_expires_in_auto_pay"]    = $rs["expires_in"] + time();
                    $newData["shop_attr_app_auth_token_re_expires_in_auto_pay"] = $rs["re_expires_in"] + time();
                    $newData["shop_attr_app_refresh_token_auto_pay"]            = $rs["app_auth_token"];
                    $newData["shop_attr_app_token_update_time_auto_pay"]        = time();
                    break;
                default:
                    $shopOne->shop_alipay_app_auth_token = $rs["app_auth_token"];
                    $shopOne->shop_alipay_auth_app_id    = $rs["auth_app_id"];
                    //兼容的一个支付宝APP应用的做法，，2018-11-26 14:55:05
                    $shopOne->shop_alipay_app_auth_token_auto_pay = $rs["app_auth_token"];
                    $shopOne->shop_alipay_auth_app_id_auto_pay    = $rs["auth_app_id"];

                    $shopOne->shop_alipay_seller_id      = $rs["user_id"]; //商户的pid
                    $shopOne->save();
                    $shop_attr                                         = db("shop_attr");
                    $newData["shop_attr_shop_id"]                      = $shopOne->shop_id;
                    $newData["shop_attr_app_auth_token_expires_in"]    = $rs["expires_in"] + time();
                    $newData["shop_attr_app_auth_token_re_expires_in"] = $rs["re_expires_in"] + time();
                    $newData["shop_attr_app_refresh_token"]            = $rs["app_auth_token"];
                    //兼容的一个支付宝APP应用的做法，，2018-11-26 14:55:05
                    
                    $newData["shop_attr_app_auth_token_expires_in_auto_pay"]    = $rs["expires_in"] + time();
                    $newData["shop_attr_app_auth_token_re_expires_in_auto_pay"] = $rs["re_expires_in"] + time();
                    $newData["shop_attr_app_refresh_token_auto_pay"]            = $rs["app_auth_token"];
                    $newData["shop_attr_app_token_update_time_auto_pay"]        = time();


                    $newData["shop_attr_app_token_update_time"]        = time();
                    break;
            }
            if ($shop_attr->where(["shop_attr_shop_id" => $shopOne->shop_id])->find()) {
                $shop_attr->where(["shop_attr_shop_id" => $shopOne->shop_id])->update($newData);
            } else {
                $shop_attr->insert($newData);
            }
            $this->assign("status", 1);
            $this->assign("message", "授权成功");
            return view("h5@index/alipayoauthresult");
        }
    }
    //http://ipay.iaapp.cn/cmd/oauth/qrcode/uid/1234 这个地址做成活码
    //http://ipay.iaapp.cn/cmd/oauth/qrcode/reward/1/uid/1234 打赏码
    //二码合一统一（收款码）入口
    //含固定金额（打赏）入口
    public function qrcode()
    {
        //收银员ID
        $uid       = input("uid");
        $reward    = input("reward", 0);
        $param     = url("h5/index/jsapipay?user_id=" . $uid . "&reward=" . $reward);
        $returnUrl = request()->domain() . "/index.php?s=" . $param; //因为微信要写固定的授权目录，所以用兼容模式，参数写成变动的,可增加删除参数
        //echo $returnUrl;die();
        $this->redirect($returnUrl); //转向统一页面
    }
    /**
     * 短地址转跳到真实的地址
     * short_url_key 参数路由过来的
     * @return [type] [description]
     */
    public function shorturl()
    {
        $short_url_key          = input("short_url_key");
        $short_url              = model("short_url");
        $where["short_url_key"] = $short_url_key;
        $shortOne               = $short_url->where($where)->find();
        if (!$shortOne) {
            echo "短链接不存在";
            header("HTTP/1.0 404 Not Found");
        } else {
            switch ($shortOne->short_url_action) {
                case 'payqrcode': //聚合码的数据
                    if (!$shortOne->short_url_active_addtime) {
                        echo "<h1>该聚合码未绑定</h1>";die();
                    }
                    $this->redirect(url("/cmd/oauth/qrcode?uid=" . $shortOne->short_url_data));
                    break;
                default:
                    echo "未知的短链接类型";
                    break;
            }
        }
    }
}
