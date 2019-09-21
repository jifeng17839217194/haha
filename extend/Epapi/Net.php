<?php
namespace Epapi;
use Epapi\Config;

/**
 *
 */
class Net
{
    //加密数据
    public function dosign($param)
    {
        //加上固定的参数
        $param["timestamp"] = time();
        $param["signature"] = $this->getsignvalue($param);
        //dump($param);
        return $param;
    }

    //公共签名,防止内容被篡改
    //// stringSignTemp=stringA+"644e7b96967640bfb1bf70ff3f17afcd" //注：直接拼接应用密钥AppSecret
    // sign=MD5(stringSignTemp).toUpperCase()="4182B5A4B1421A15B398183248D6828E" //注：MD5签名方式
    //         "]
    public function getsignvalue($dataArray)
    {
        $stringtosign = "";
        if ($dataArray) {
            unset($dataArray["signature"]);
            //$stringtosignArray = [];
            $requestPublicData = $dataArray;
            //foreach ($requestPublicData as $key => $value) {
            //    $v                   = (((is_array($value) || is_object($value)) ? json_encode($value,JSON_UNESCAPED_UNICODE) : $value));
            //    $stringtosignArray[] = $v;
            //}
            //print_r($requestPublicData);
            foreach ($requestPublicData as $key => $value) {
                $v                   = (((is_array($value) || is_object($value)) ? json_encode($value,JSON_UNESCAPED_UNICODE) : $value));
                //$stringtosignArray[] = $v;
                $requestPublicData[$key]=$v;
            }
            asort($requestPublicData, SORT_STRING); //以数组值排序
            $stringtosign = implode("", $requestPublicData); //进行累加

            //计算出密钥
            $secret = substr($requestPublicData["timestamp"], 0, 1) . substr($requestPublicData["timestamp"], 2, 1) . substr($requestPublicData["timestamp"], 4, 1) . substr($requestPublicData["timestamp"], 6, 1) . substr($requestPublicData["timestamp"], 8, 1) . substr($requestPublicData["timestamp"], 9, 1);

            //echo $secret."<br />";
            //echo $stringtosign;
        }
        //生成签名
        $Signature = bin2hex(hash_hmac('sha256', $stringtosign, $secret, true));
        //echo $Signature."<br />";
        return $Signature;
    }

    public function post($data, $apiuri)
    {
        $rsArray = [];
        $url     = (Config::DEBUG ? Config::URI_TEST : Config::URI_ONLINE) . $apiuri;
        $data    = $this->dosign($data);
        $url     = $url . "?data=" . urlencode(json_encode($data, JSON_UNESCAPED_UNICODE));
        //trace($url, "debug");
        //echo $url . "<br />";
        //dump($data);
        $rs = $this->httpsGet($url);
        //dump($rs);
        if ($rs["code"] == 1) //通信正常
        {
            $rs["data"] = str_replace("<html><body>", "", $rs["data"]);
            $rs["data"] = str_replace("</body></html>", "", $rs["data"]);
            $rs["data"] = str_replace("Re_Code","ret_code",$rs["data"]);//易泊错误名称的接口
            $rs["data"] = str_replace("ErrorDes","error_des",$rs["data"]);//易泊错误名称的接口
            if ($rs["data"]) {$rs["data"] = json_decode($rs["data"], 1);};

            $rs["message"] = $rs["data"]["error_des"];
            if (intval($rs["data"]["ret_code"]) !== 1) {
                $rs["code"]    = 0;
            }
        }
        //提取有效的数据
        return $rs;
    }

    public function httpsGet($url)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); //这个是重点。
        $data = curl_exec($curl);
        //$httpInfo = curl_getinfo($curl);
        //$info = array_merge(array("body"=>$data), array("header"=>$httpInfo));
        //dump($info);
        $result   = $data;
        $headinfo = curl_getinfo($curl);
        if ($this_error = curl_errno($curl) || $headinfo["http_code"] != 200) {
            $rs = ["code" => 0, "message" => $this_error, "data" => $result]; //通信失败
        } else {
            $rs = ["code" => 1, "message" => "", "data" => $result]; //通信成功
        }
        curl_close($curl);
        return $rs;
    }

}
