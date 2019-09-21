<?php
//易泊 http接口的回调地址
ini_set('max_execution_time', '10');

function httpPostJson($url, $jsonStr)
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
//{"data":[{"access_id":"5765936","access_time":"2019-01-16 18:06:48","cario_id":"9634","imagestream":"","park_id":"25404241023598608","park_name":"杭州专注科技停车场","parkingName":"智能车牌识别停车场","parking_spaceNum":"1525","plate_color":"蓝","plate_id":"浙AD750C","plate_state":"正常","plate_subtype":"","plate_type":"临时车","port_id":"1","port_name_in":"入口","signature":"7cb9b59fc3fdc21b49f61e8cb3988c664d4329d1ec403738c94e00e84cf81724","timestamp":"1547633207"}],"fnName":"inParkEnd","fnType":"2","guid":"PARK4EA723D421094193A411CEB4EFA5C55F","msgType":"1","sentryId":null}
$postdata = file_get_contents("php://input");
if (!$postdata) {echo "没有POST数据";die;}
$postdataArray = json_decode($postdata, 1);

$url                                     = "http://" . $_SERVER["HTTP_HOST"] . "/api/carpark/callbacknotice";
$param                                   = [];
$param["from_compay"]                    = "epapi";
$param["signtype"]                       = "epapihttp"; //签名验证类型
$param["apiname"]                        = $postdataArray["fnName"];

$this_data = $postdataArray["data"];
if(isset($this_data[0]))$this_data=$this_data[0];//兼容的原来的数据格式
$this_data["imagestream"] = "";//清除掉图片数据，太占thinkpp 日志了
$param["param"]                          = json_encode($this_data, JSON_UNESCAPED_UNICODE);

//file_put_contents("./" . time() . ".txt", $postdata);
//print_r($postdataArray);
httpPostJson($url, json_encode($param, JSON_UNESCAPED_UNICODE)); //转发到统一的接口处理
