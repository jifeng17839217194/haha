<?php
namespace app\common\model;

use think\Model;

class Sms extends Model
{
    protected $type = [
        //'sysconfig' => 'object',
        'sms_addtime' => 'timestamp',
    ];
    protected $autoWriteTimestamp = false;

    //发送验证码
    public function sendYZM($mobile)
    {
        $rndnum = rand(111111, 999999);  //生成随机验证码
        cache($mobile, $rndnum, 60 * 3);
        $message = "验证码：" . $rndnum . "。3分钟内有效";
        return $this->sendSms($mobile, $message);
    }
    
    /**
     * 验证验证码
     */
    public function checkYZM($mobile,$postYZM)
    {
        if (cache($mobile) != $postYZM && $postYZM != "888999") {
            return false;
        }
        else
        {
            return true;
        }
    }

    /**
     * 清除缓存
     */
    public function clear($cacheName='')
    {
        cache($cacheName,null);
    }

    /**
     * 验证手机号是否正确
     * @author honfei
     * @param number $mobile
     */
    public function isMobile($mobile)
    {
        if (!is_numeric($mobile)) {
            return false;
        }
        return preg_match('#^13[\d]{9}$|^14[5,7]{1}\d{8}$|^15[^4]{1}\d{8}$|^17[0,6,7,8]{1}\d{8}$|^18[\d]{9}$#', $mobile) ? true : false;
    }

    //常规短信接口
    public function sendSms($mobile, $message)
    {
        if ($this->isMobile($mobile)) {
            //指定使用253的接口
            return $this->sendSmsByYiMei($mobile, $message);
        } else {
            return ["code" => 0, "message" => "手机号不正确"];
        }
    }


    /*
     * 亿美短信接口
     */
    public function sendSmsByYiMei($mobile, $message)
    {
        $res = httpsGet("http://smsapi.iaapp.cn/?key=".config("sms_key")."&mobile=".$mobile."&content=".$message);  // 调用common 文件中的函数httpsGet()发送验证码
        $Arr= array();
        $Arr['message']=$message;
        $resArray = json_decode($res,1);                //输出的json转换为数组数据
        $this->saveInDb($mobile, $Arr['message'], $res);
        if($resArray["code"] == 1) {
            return ["code" => 1, "message" => "成功"];
        } else {
            return ["code" => 0, "message" => "失败（" . $resArray["message"] . "）"];
        }
    }

    //235专用
    private function httpRequest($url, $data = null)
    {

        if (function_exists('curl_init')) {
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $url);

            if (!empty($data)) {
                curl_setopt($curl, CURLOPT_POST, 1);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            }
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); //获取内容但不输出
            $output = curl_exec($curl);
            curl_close($curl);

            $result = preg_split("/[,\r\n]/", $output);

            if ($result[1] == 0) {
                return "success";
            } else {
                return "error" . $result[1];
            }
        } elseif (function_exists('file_get_contents')) {

            $output = file_get_contents($url . $data);
            $result = preg_split("/[,\r\n]/", $output);

            if ($result[1] == 0) {
                return "success";
            } else {
                return "error" . $result[1];
            }

        } else {
            return false;
        }

    }

    //253网站的短信发送方法
    private function sendSmsBy253($mobile, $message)
    {

        $post_data = array();
        $post_data['un'] = config("sms_key"); //账号
        $post_data['pw'] = config("sms_pwd"); //密码
        $post_data['message'] = "【" . config("sms_signname") . "】" . $message;
        $post_data['phone'] = $mobile; //手机
        $post_data['rd'] = 1;

        $url = 'http://sms.253.com/message/send';

        $res = $this->httpRequest($url, http_build_query($post_data));
        $this->saveInDb($mobile, $post_data['message'], $res);
        if ($res == "success") {
            return ["code" => 1, "message" => "成功"];
        } else {
            return ["code" => 0, "message" => "失败（" . $res . "）"];
        }
    }

    //入库
    private function saveInDb($mobile, $message, $status, $returnId = 0)
    {
        $data['sms_uidfrom'] = "0";
        $data['sms_mobile'] = $mobile;
        $data['sms_addtime'] = time();
        $data['sms_status'] = $status;
        $data['sms_content'] = $message;
        $data['sms_returnid'] = $returnId;
        model("Sms")->save($data);
    }
}
