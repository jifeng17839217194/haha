<?php
//header('Access-Control-Allow-Origin: http://www.baidu.com'); //设置http://www.baidu.com允许跨域访问
//header('Access-Control-Allow-Headers: X-Requested-With,X_Requested_With'); //设置允许的跨域header
date_default_timezone_set("Asia/chongqing");
session_start();
error_reporting(E_ERROR);
header("Content-Type: text/html; charset=utf-8");

if(!$_SESSION["think"]["allow_upload_use_ueditor"]) //程剑虎，2017-3-8
{
    echo json_encode(array('state'=> '用户未登陆'),JSON_UNESCAPED_UNICODE);die();
}

$CONFIG = json_decode(preg_replace("/\/\*[\s\S]+?\*\//", "", file_get_contents("config.json")), true);

$realpathcjh = str_replace("/static/common/js/ueditor/php","",dirname($_SERVER['SCRIPT_NAME']));
$CONFIG["imagePathFormat"] = $realpathcjh. $CONFIG["imagePathFormat"];//程剑虎，2017年1月14日15:31:00
$CONFIG["scrawlPathFormat"] = $realpathcjh. $CONFIG["scrawlPathFormat"];//程剑虎，2017年1月14日15:31:00
$CONFIG["snapscreenPathFormat"] = $realpathcjh. $CONFIG["snapscreenPathFormat"];//程剑虎，2017年1月14日15:31:00
$CONFIG["catcherPathFormat"] = $realpathcjh. $CONFIG["catcherPathFormat"];//程剑虎，2017年1月14日15:31:00
$CONFIG["videoPathFormat"] = $realpathcjh. $CONFIG["videoPathFormat"];//程剑虎，2017年1月14日15:31:00
$CONFIG["filePathFormat"] = $realpathcjh. $CONFIG["filePathFormat"];//程剑虎，2017年1月14日15:31:00
$CONFIG["imageManagerListPath"] = $realpathcjh. $CONFIG["imageManagerListPath"];//程剑虎，2017年1月14日15:31:00
$CONFIG["fileManagerListPath"] = $realpathcjh. $CONFIG["fileManagerListPath"];//程剑虎，2017年1月14日15:31:00





$action = $_GET['action'];
//print_r($CONFIG);
//die();

switch ($action) {
    case 'config':
        $result =  json_encode($CONFIG);
        break;

    /* 上传图片 */
    case 'uploadimage':
    /* 上传涂鸦 */
    case 'uploadscrawl':
    /* 上传视频 */
    case 'uploadvideo':
    /* 上传文件 */
    case 'uploadfile':
        $result = include("action_upload.php");
        break;

    /* 列出图片 */
    case 'listimage':
        $result = include("action_list.php");
        break;
    /* 列出文件 */
    case 'listfile':
        $result = include("action_list.php");
        break;

    /* 抓取远程文件 */
    case 'catchimage':
        $result = include("action_crawler.php");
        break;

    default:
        $result = json_encode(array(
            'state'=> '请求地址出错'
        ));
        break;
}

/* 输出结果 */
if (isset($_GET["callback"])) {
    if (preg_match("/^[\w_]+$/", $_GET["callback"])) {
        echo htmlspecialchars($_GET["callback"]) . '(' . $result . ')';
    } else {
        echo json_encode(array(
            'state'=> 'callback参数不合法'
        ));
    }
} else {
    echo $result;
}