<?php
namespace app\cmd\controller;

use EasyWeChat\Foundation\Application;
use think\Controller;

class Carparkstatus
{
    public function online()
    {
        $sendData                = [];
        $sendData["uuid"]        = "rnd123456"; //随便弄个假的
        $sendData["from_compay"] = "epapi";
        $Parkcommon              = new \Parkcommon\Apiget();
        $rsArray                 = $Parkcommon->getPort($sendData);
        print_r($rsArray);
    }

    public function getopenid() //微信扫这个地址  http://ipay.iaapp.cn/api/carparkstatus/getopenid

    {
        $config = [
            'oauth' => [
                'scopes'   => ['snsapi_userinfo'],
                'callback' => url('oauthcallback'),
            ],
        ];
        $app   = new Application(array_merge(model("wxpay")->configOptions(), $config));
        $oauth = $app->oauth;

        // // 未登录
        if (empty($_SESSION['wechat_user'])) {

            $_SESSION['target_url'] = url('oauthcallback');
            //echo $oauth->redirect();
            //return $oauth->redirect();
            // 这里不一定是return，如果你的框架action不是返回内容的话你就得使用
            $oauth->redirect()->send();
        }
        // print_r($oauth);
    }

    public function oauthcallback()
    {
        $config = [
            'oauth' => [
                'scopes'   => ['snsapi_userinfo'],
                'callback' => url('oauthcallback'),
            ],
        ];
        $app   = new Application(array_merge(model("wxpay")->configOptions(), $config));
        $oauth = $app->oauth;
        // 获取 OAuth 授权结果用户信息
        $user = $oauth->user();
        cache("ybnoticewxid", $user->getId());
        echo "通知id采集完成";
        // $_SESSION['wechat_user'] = $user->toArray();

        // $targetUrl = empty($_SESSION['target_url']) ? '/' : $_SESSION['target_url'];

        // header('location:' . $targetUrl); // 跳转到 user/profile
    }

    private function wxnotice()
    {
        $app    = new Application(model("wxpay")->configOptions());
        $notice = $app->notice;

        $messageId = $notice->send([
            'touser'      => cache("ybnoticewxid"),
            'template_id' => 'VHVsLoueGQaVTyNQraKsARFXXoPjyApE-xnZEBYRCZ4',
            'url'         => 'http://ipay.iaapp.cn/cmd/Carparkstatus/notice',
            'data'        => [
                "first"    => "易泊SDK状态通知",
                "keyword1" => "SDK挂了，去看看",
                "keyword2" => date("Y-m-d H:i:s", time()),
                "remark"   => "",
            ],
        ]);
    }

    /**
     * 通知机制检测
     * 远程请求该地址，根据该地址返回的数据判断易泊SDK是否正常
     * @return [type] [description]
     */
    public function notice()
    {
        header("Content-type:text/html;charset=utf-8");

        $rs = $this->httpsPost(request()->domain() . '/cmd/Carparkstatus/online');
        if ($rs["code"] == 1) {
            $rs_html = $rs["data"];
            if (stripos($rs_html, "没有在本系统里找到") !== false) {
                return ["code" => 1, "message" => "易泊SDK正常systemrunok" . date("Y-m-d H:i:s", time()), "data" => ""];
            } else {

                //$notice = $app->notice;
                $this->wxnotice();
                trace("易泊SDK挂了systemdown", "error");
                return ["code" => 0, "message" => "易泊SDK挂了systemdown" . date("Y-m-d H:i:s", time()), "data" => $rs_html];
            }
        } else {
            trace("易泊SDK挂了systemdown", "error");
            $this->wxnotice();
            return ["code" => 0, "message" => "易泊SDK挂了systemdown" . date("Y-m-d H:i:s", time()), "data" => $rs_html];
        }

        die;
    }

    public function httpsPost($url, $data = [], $action_name = "", $is_json = false)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_POST, 1);
        if ($is_json) {
            $data_string = is_array($data) ? json_encode($data, JSON_UNESCAPED_UNICODE) : $data;
            trace($data_string, "debug");
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);
            curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data_string),
            ));
        } else {
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($curl);

        if ($this_error = curl_errno($curl)) {
            $rs = ["code" => 0, "message" => $this_error, "data" => ""]; //通信成功
        } else {
            $rs = ["code" => 1, "message" => "", "data" => $result]; //通信失败
        }
        curl_close($curl);
        return $rs;

    }
}
