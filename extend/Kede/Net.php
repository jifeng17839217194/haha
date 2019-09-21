<?php
namespace Kede;

use Kede\Config;

/**
 * 科德外挂版本
 */
class Net
{
    //加密数据
    public function dosign($param)
    {
        //加上固定的参数
        $param["version"] = Config::VERSION;
        $param["appid"]   = Config::APPID;
        $param["rand"]    = mt_rand(0, 999999999) . time();
        $param["sign"]    = $this->getsignvalue($param);
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
            $stringtosignArray = "";
            $requestPublicData = $dataArray;
            ksort($requestPublicData);
            foreach ($requestPublicData as $key => $value) {
                if ($value) {
                    $stringtosign .= $key . "=" . ((is_array($value) || is_object($value)) ? json_encode($value) : $value) . "&";
                }

            }
            $stringtosign = $stringtosign . Config::SECRET;
        }
        $Signature = strtoupper(md5($stringtosign));
        return $Signature;
    }

    public function post($data, $apiuri)
    {
        $rsArray = [];
        $url     = (Config::DEBUG ? Config::URI_Test : Config::URI_Online) . $apiuri;
        //echo $url;
        $rs = $this->httpPostJson($url, json_encode($this->dosign($data),JSON_UNESCAPED_UNICODE));
        return json_decode($rs[1],1);
    }

    public function httpPostJson($url, $jsonStr)
    {
        //echo $jsonStr;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonStr);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json; charset=utf-8',
            'Content-Length: ' . strlen($jsonStr),
        )
        );
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return array($httpCode, $response);
    }
}
