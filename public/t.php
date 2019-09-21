<?php
// echo round((1+7),0);
// echo "\r\n";
// echo 0.1/0.14;
// echo "\r\n";
// echo bcdiv(0.1,0.14,4);
// echo "\r\n";
// echo bcdiv(0.1*100,0.14,2);

//var_dump(round(0.2+0.7,3) == 0.9); // 输出：bool(true) 
//$s= substr(md5("order_num=ZZ1520232655969&return_printhtml=0&time=1520232651&user_id=10000103&version=1abd".time()), 8, 16);
//echo base_convert($s,16,10);
function httpsGet($url)
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
        $result = $data;
        $headinfo = curl_getinfo($curl);
        if ($this_error = curl_errno($curl) || $headinfo["http_code"]!=200 ) {
            $rs = ["code" => 0, "message" => $this_error, "data" => $result]; //通信失败
        } else {
            $rs = ["code" => 1, "message" => "", "data" => $result]; //通信成功
        }
        curl_close($curl);
        return $rs;
    }
    $url ='http://epay.eparking.com.cn:9801/payok?data='.('{"park_id":"25404241023598608","port_id":"","order_id":"1340","cario_id":"9589","pay_time":"2019-01-10 15:55:52","pay_amount":"0.01","pay_id":"1340","accept_account_id":"","pay_finish_type":0,"pay_type":2,"timestamp":1547106952,"signature":"ae4d18d2cb4b3587aae0d71ebaaebf0d005600fd4265e6b84b306756d49abf63"}');

    //初始化
    $curl = curl_init();
    //设置抓取的url
    curl_setopt($curl, CURLOPT_URL, $url);
    //设置头文件的信息作为数据流输出
    curl_setopt($curl, CURLOPT_HEADER, 1);
    //设置获取的信息以文件流的形式返回，而不是直接输出。
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    //设置post方式提交
    curl_setopt($curl, CURLOPT_POST, 1);
    //设置post数据
    $post_data = array(
        "username" => "coder",
        "password" => "12345"
        );
    curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
    //执行命令
    $data = curl_exec($curl);
    //关闭URL请求
    curl_close($curl);
    //显示获得的数据
    print_r($data);

//print_r(httpsGet());
 ?>
