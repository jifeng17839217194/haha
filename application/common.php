<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------
// 应用公共文件
//全部都是自定义函数，系统会自动加载此文件
/**
 * RBAC 控制,助手函数
 * 仅仅后台使用
 */
function checkActionAuth($pointId = "", $action = "delete", $admin_id = "")
{
    if (input("session." . config("database")["database"] . "admin_id") == 10003) {
        return true;
    }
    if ($admin_id == "") {
        $admin_id = request()->session(config("database")["database"] . "admin_id");
    }
    $rs = model("AdminRole")->isHavePowder($pointId, $action, $admin_id);
    if ($rs["code"] == 0) {
        return false;
    } else {
        return true;
    }
}

function jsonencode($dataArray=[])
{
    return json_encode($dataArray);
}

function createDir($path)
{
    if (!file_exists($path)) {
        createDir(dirname($path));
        mkdir($path, 0777);
    }
}
//随机字符串
function getRandChar($length)
{
    $str    = null;
    $strPol = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz";
    $max    = strlen($strPol) - 1;
    for ($i = 0; $i < $length; $i++) {
        $str .= $strPol[rand(0, $max)]; //rand($min,$max)生成介于min和max两个数之间的一个随机整数
    }
    return $str;
}
//本地地址转远程地址
//作用于文章内部的链接、图片、附件等
function relativeToAbsolute($gethtm, $surl = "")
{
    if ($surl == "") {
        $surl = request()->domain();
    }
    if (preg_match_all("/(<img[^>]+src=\"([^\"]+)\"[^>]*>)|(<a[^>]+href=\"([^\"]+)\"[^>]*>)|(<img[^>]+src='([^']+)'[^>]*>)|(<a[^>]+href='([^']+)'[^>]*>)/i", $gethtm, $regs)) {
        foreach ($regs[2] as $url) {
            $gethtm = str_replace($url, lIIIIl($url, $surl), $gethtm);
        }
    }
    return $gethtm;
}
//多文本的转换
function r2a($gethtm, $surl = "")
{
    return relativeToAbsolute($gethtm, $surl);
}
//单行转换
function r2astr($fileurl, $surl = "")
{
    if ($surl == "") {
        $surl = request()->domain();
    }
    return strpos($fileurl, "http:") !== false ? $fileurl : $surl . toAbsUrl($fileurl);
}
//PC端图片展示URL修正
function toAbsUrl($fileurl)
{
    return SCRIPT_DIR . str_replace("./", "/", $fileurl);
}
//将一文字里的图片，变成<img data-src="xxx.jpg" class="lazy" />
function img2lazy($content)
{
    if ($content) {
        preg_match_all("/<img[^>]+?>/is", $content, $imgarray);
        $imgarray = $imgarray[0];
        if (count($imgarray) > 0) {
            foreach ($imgarray as $key => $val) {
                //if($key>=2)//第一张，第二张就直接加载吧
                {
                    $oneimg = $val;
                    if (strpos($oneimg, "class=")) {
                        $oneimg = str_replace(" class=", " class=\"lazy ", $oneimg);
                    } else {
                        $oneimg = str_replace(" src=", " class=\"lazy\" src=", $oneimg);
                    }
                    $oneimg = str_replace(" src=", " data-echo=", $oneimg);
                    //dump($oneimg);
                    $content = str_replace($val, $oneimg, $content);
                }
            }
        }
    }
    return $content;
}
function lIIIIl($l1, $l2)
{
    if ($l1) {
        //echo $l1;
        $I1 = preg_split("//", $l1, -1, PREG_SPLIT_NO_EMPTY);
    }
    //dump($I1);
    //判断类型
    if (preg_match("/^(http|https|ftp):(\/\/|\\\\)(([\w\/\\\+\-~`@:%])+\.)+([\w\/\\\.\=\?\+\-~`@\':!%#]|(&)|&)+/i", $l1)) {
        return $I1;
    } //http开头的url类型要跳过
    elseif ($I1[0] == "/") {
        return $l1 = $l2 . $l1;
    } //绝对路径
    elseif (substr($l1, 0, 3) == "../") {
//相对路径
        while (substr($l1, 0, 3) == "../") {
            $l1 = substr($l1, strlen($l1) - (strlen($l1) - 3), strlen($l1) - 3);
            if (strlen($path) > 0) {
                $path = dirname($path);
            }
        }
        $l1 = $l2 . $path . "/" . $l1;
    } elseif (substr($l1, 0, 2) == "./") {
        $l1 = $l2 . $path . substr($l1, strlen($l1) - (strlen($l1) - 1), strlen($l1) - 1);
    } elseif (strtolower(substr($l1, 0, 7)) == "mailto:" || strtolower(substr($l1, 0, 11)) == "javascript:") {
        return $l1;
    } else {
        $l1 = $l2 . "/" . $l1;
    }
    $rsstring = str_replace($l2, "\"$I1\"", $l1);
    return $rsstring;
}
/**
 * 字符串截取，支持中文和其他编码
 * @static
 * @access public
 * @param string $str 需要转换的字符串
 * @param string $start 开始位置
 * @param string $length 截取长度
 * @param string $charset 编码格式
 * @param string $suffix 截断显示字符
 * @return string
 */
function msubstr($str, $start = 0, $length, $charset = "utf-8", $suffix = true)
{
    $str  = strip_tags($str);
    $str  = trim($str);
    $qian = array("　", "\t", "\n", "\r");
    $hou  = array("", "", "", "", "");
    $str  = str_replace($qian, $hou, $str);
    $str = str_replace('&nbsp;', '', $str);
    //去除全角空格
    if (function_exists("mb_substr")) {
        $slice = mb_substr($str, $start, $length, $charset);
    } elseif (function_exists('iconv_substr')) {
        $slice = iconv_substr($str, $start, $length, $charset);
        if (false === $slice) {
            $slice = '';
        }
    } else {
        $re['utf-8']  = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
        $re['gb2312'] = "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/";
        $re['gbk']    = "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";
        $re['big5']   = "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/";
        preg_match_all($re[$charset], $str, $match);
        $slice = join("", array_slice($match[0], $start, $length));
    }
    return $suffix ? ($slice == $str ? $slice : $slice . '…') : $slice;
}
/**
+----------------------------------------------------------
 * 输出安全的html，用于过滤危险代码
+----------------------------------------------------------
 * @access public
+----------------------------------------------------------
 * @param string $text 要处理的字符串
 * @param mixed $allowTags 允许的标签列表，如 table|td|th|td
+----------------------------------------------------------
 * @return string
+----------------------------------------------------------
 */
function safeHtml($text, $allowTags = null)
{
    $htmlTags = array('allow' => 'table|td|th|tr|i|b|u|strong|img|p|br|div|strong|em|ul|ol|li|dl|dd|dt|a', 'ban' => 'html|head|meta|link|base|basefont|body|bgsound|title|style|script|form|iframe|frame|frameset|applet|id|ilayer|layer|name|script|style|xml');
    $text     = trim($text);
    //完全过滤注释
    $text = preg_replace('/<!--?.*-->/', '', $text);
    //完全过滤动态代码
    $text = preg_replace('/<\?|\?' . '>/', '', $text);
    //完全过滤js
    $text = preg_replace('/<script?.*\/script>/', '', $text);
    $text = str_replace('[', '&#091;', $text);
    $text = str_replace(']', '&#093;', $text);
    $text = str_replace('|', '&#124;', $text);
    //过滤换行符
    $text = preg_replace('/\r?\n/', '', $text);
    //br
    $text = preg_replace('/<br(\s\/)?' . '>/i', '[br]', $text);
    $text = preg_replace('/(\[br\]\s*){10,}/i', '[br]', $text);
    //过滤危险的属性，如：过滤on事件lang js
    while (preg_match('/(<[^><]+)(lang|on|action|background|codebase|dynsrc|lowsrc)[^><]+/i', $text, $mat)) {
        $text = str_replace($mat[0], $mat[1], $text);
    }
    while (preg_match('/(<[^><]+)(window\.|javascript:|js:|about:|file:|document\.|vbs:|cookie)([^><]*)/i', $text, $mat)) {
        $text = str_replace($mat[0], $mat[1] . $mat[3], $text);
    }
    if (empty($allowTags)) {
        $allowTags = $htmlTags['allow'];
    }
    //允许的HTML标签
    $text = preg_replace('/<(' . $allowTags . ')( [^><\[\]]*)>/i', '[\1\2]', $text);
    //过滤多余html
    if (empty($banTag)) {
        $banTag = $htmlTags['ban'];
    }
    $text = preg_replace('/<\/?(' . $banTag . ')[^><]*>/i', '', $text);
    //过滤合法的html标签
    while (preg_match('/<([a-z]+)[^><\[\]]*>[^><]*<\/\1>/i', $text, $mat)) {
        $text = str_replace($mat[0], str_replace('>', ']', str_replace('<', '[', $mat[0])), $text);
    }
    //转换引号
    while (preg_match('/(\[[^\[\]]*=\s*)(\"|\')([^\2=\[\]]+)\2([^\[\]]*\])/i', $text, $mat)) {
        $text = str_replace($mat[0], $mat[1] . '|' . $mat[3] . '|' . $mat[4], $text);
    }
    //空属性转换
    $text = str_replace('\'\'', '||', $text);
    $text = str_replace('""', '||', $text);
    //过滤错误的单个引号
    while (preg_match('/\[[^\[\]]*(\"|\')[^\[\]]*\]/i', $text, $mat)) {
        $text = str_replace($mat[0], str_replace($mat[1], '', $mat[0]), $text);
    }
    //转换其它所有不合法的 < >
    //$text =  str_replace('<','&lt;',$text);
    //$text = str_replace('>','&gt;',$text);
    //$text = str_replace('"','&quot;',$text);
    //反转换
    $text = str_replace('[', '<', $text);
    $text = str_replace(']', '>', $text);
    $text = str_replace('|', '"', $text);
    //过滤多余空格
    $text = str_replace('  ', ' ', $text);
    return $text;
}
/**
+-----------------------------------------------------------------------------------------
 * 删除目录及目录下所有文件或删除指定文件
+-----------------------------------------------------------------------------------------
 * @param str $path   待删除目录路径
 * @param int $delDir 是否删除目录，1或true删除目录，0或false则只删除文件保留目录（包含子目录）
+-----------------------------------------------------------------------------------------
 * @return bool 返回删除状态
+-----------------------------------------------------------------------------------------
 */
function delDirAndFile($path, $delDir = false)
{
    if (is_array($path)) {
        foreach ($path as $subPath) {
            delDirAndFile($subPath, $delDir);
        }
    }
    if (is_dir($path)) {
        $handle = opendir($path);
        if ($handle) {
            while (false !== ($item = readdir($handle))) {
                if ($item != "." && $item != "..") {
                    is_dir("$path/$item") ? delDirAndFile("$path/$item", $delDir) : unlink("$path/$item");
                }
            }
            closedir($handle);
            if ($delDir) {
                return rmdir($path);
            }
        }
    } else {
        if (file_exists($path)) {
            return unlink($path);
        } else {
            return false;
        }
    }
    clearstatcache();
}
function sendemail($sendto, $subjet, $body)
{
    import('Common.Email', APP_PATH, '.class.php');
    //导入自定义类
    $smtpserver = C('emailsmtp');
    //SMTP服务器
    //$smtpserverport = 25;
    $smtpserverport = 80; //godaddy服务器要设置为80的端口
    //SMTP服务器端口
    $smtpusermail = C('emailuser');
    //SMTP服务器的用户邮箱
    //$fromname = "=?UTF-8?B?".base64_encode("租车")."?=";  //解决标题乱码;发送者名称(自定义)
    $fromname    = C('website_title');
    $smtpemailto = $sendto;
    //发送给谁
    $smtpuser = C('emailuser');
    //SMTP服务器的用户帐号
    $smtppass = C('emailpassword');
    //SMTP服务器的用户密码
    $mailsubject = "=?UTF-8?B?" . base64_encode($subjet) . "?=";
    //解决标题乱码
    //$mailsubject = $subjet;
    $mailbody = $body;
    //邮件内容
    $mailtype = "HTML";
    //邮件格式（HTML/TXT）,TXT为文本邮件
    $smtp        = new smtp($smtpserver, $smtpserverport, true, $smtpuser, $smtppass);
    $smtp->debug = false;
    //是否显示发送的调试信息
    return $smtp->sendmail($smtpemailto, $fromname, $smtpusermail, $mailsubject, $mailbody, $mailtype);
}
// function sendsms($mobile, $message, $from = "0", $sign = "")
// {
//     $sign = C("smssign");
//     import('', APP_PATH . "Common/YiMeiSMS/nusoaplib/", 'nusoap.php');
//     //导入nusoap
//     import('', APP_PATH . "Common/YiMeiSMS/include/", 'Client.php');
//     //导入Client
//     //D("News")->add(array("cid"=>30,"naddtime"=>time(),"ntitle"=>"No2.".$out_trade_no."Client".date("s",time())."；止于L275","ncontent"=>$message)); //不能正常返回干净的success，调试用
//     //return false;
//     /**
//      * 网关地址
//      */
//     $gwUrl = 'http://sdkhttp.eucp.b2m.cn/sdk/SDKService'; //适合3sdk
//     //$gwUrl = 'http://sdk4report.eucp.b2m.cn:8080/sdk/SDKService'; //适合6sdk
//     /**
//      * 序列号,请通过亿美销售人员获取
//      */
//     $serialNumber = C("YiMei_xlh");
//     /**
//      * 密码,请通过亿美销售人员获取
//      */
//     $password = C("YiMei_pwd");
//     /**
//      * 登录后所持有的SESSION KEY，即可通过login方法时创建
//      */
//     $sessionKey = C("YiMei_key");
//     /**
//      * 连接超时时间，单位为秒
//      */
//     $connectTimeOut = 2;
//     /**
//      * 远程信息读取超时时间，单位为秒
//      */
//     $readTimeOut = 10;
//     /**
//     $proxyhost        可选，代理服务器地址，默认为 false ,则不使用代理服务器
//     $proxyport        可选，代理服务器端口，默认为 false
//     $proxyusername    可选，代理服务器用户名，默认为 false
//     $proxypassword    可选，代理服务器密码，默认为 false
//      */
//     $proxyhost = false;
//     $proxyport = false;
//     $proxyusername = false;
//     $proxypassword = false;
//     $client = new Client($gwUrl, $serialNumber, $password, $sessionKey, $proxyhost, $proxyport, $proxyusername, $proxypassword, $connectTimeOut, $readTimeOut);
//     /**
//      * 发送向服务端的编码，如果本页面的编码为utf-8，请使用utf-8
//      */
//     $client->setOutgoingEncoding("utf-8");
//     $contentsms = $message;
//     if ($sign != "") {
//         $contentsms = "【" . $sign . "】" . $contentsms;
//     }
//     $statusCode = $client->sendSMS(array($mobile), $contentsms);
//     //$statusCode = $client->sendSMS(array('159xxxxxxxx','159xxxxxxxx'),"test2测试");
//     //echo "处理状态码:".$statusCode;
//     //echo "处理状态码:".$statusCode;
//     if ($statusCode == 0) {
//         $statusCodeinfo = "成功";
//     } else {
//         $statusCodeinfo = "失败(" . $statusCode . ")";
//     }
//     //保存在数据库
//     $data["suidfrom"] = $from;
//     $data["smobile"] = $mobile;
//     $data["saddtime"] = time();
//     $data["sstatus"] = $statusCodeinfo;
//     $data["scontent"] = $contentsms;
//     M("Sms")->add($data);
//     //__保存在数据库
//     return $statusCode;
// }
/*function sendsms($mobile, $message,$from="0") {
$app_key = C("smsappkey");
$app_secret = C("smsappsecret");
$request_paras = array(
'ParamString' => '{"name":"'.$message.'"}',
'RecNum' => ''.$mobile,
'SignName' =>C("smssign"),
'TemplateCode' => 'SMS_34525073'
);
$request_host = "http://sms.market.alicloudapi.com";
$request_uri = "/singleSendSms";
$request_method = "GET";
$info = "";
$statusCodeinfo = doGetForAli($app_key, $app_secret, $request_host, $request_uri, $request_method, $request_paras, $info);
$statusCodeObj=json_decode($statusCodeinfo,1);
//保存在数据库
$data["suidfrom"]=$from;
$data["smobile"]=$mobile;
$data["saddtime"]=time();
$data["sstatus"]=($statusCodeObj["success"]?"成功":"失败").$statusCodeObj["message"];
$data["scontent"]=$message;
M("Sms")->add($data);
//__保存在数据库
return array("status"=>$statusCodeObj["success"]?1:0,"info"=>$statusCodeObj["message"]);
}
 */
function doGetForAli($app_key, $app_secret, $request_host, $request_uri, $request_method, $request_paras, &$info)
{
    ksort($request_paras);
    $request_header_accept = "application/json;charset=utf-8";
    $content_type          = "";
    $headers               = array(
        'X-Ca-Key' => $app_key,
        'Accept'   => $request_header_accept,
    );
    ksort($headers);
    $header_str         = "";
    $header_ignore_list = array('X-CA-SIGNATURE', 'X-CA-SIGNATURE-HEADERS', 'ACCEPT', 'CONTENT-MD5', 'CONTENT-TYPE', 'DATE');
    $sig_header         = array();
    foreach ($headers as $k => $v) {
        if (in_array(strtoupper($k), $header_ignore_list)) {
            continue;
        }
        $header_str .= $k . ':' . $v . "\n";
        array_push($sig_header, $k);
    }
    $url_str    = $request_uri;
    $para_array = array();
    foreach ($request_paras as $k => $v) {
        array_push($para_array, $k . '=' . $v);
    }
    if (!empty($para_array)) {
        $url_str .= '?' . join('&', $para_array);
    }
    $content_md5 = "";
    $date        = "";
    $sign_str    = "";
    $sign_str .= $request_method . "\n";
    $sign_str .= $request_header_accept . "\n";
    $sign_str .= $content_md5 . "\n";
    $sign_str .= "\n";
    $sign_str .= $date . "\n";
    $sign_str .= $header_str;
    $sign_str .= $url_str;
    $sign                              = base64_encode(hash_hmac('sha256', $sign_str, $app_secret, true));
    $headers['X-Ca-Signature']         = $sign;
    $headers['X-Ca-Signature-Headers'] = join(',', $sig_header);
    $request_header                    = array();
    foreach ($headers as $k => $v) {
        array_push($request_header, $k . ': ' . $v);
    }
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $request_host . $url_str);
    //curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLINFO_HEADER_OUT, true);
    curl_setopt($ch, CURLOPT_VERBOSE, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $request_header);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $ret  = curl_exec($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    return $ret;
}
/*
 *
 *函数功能：计算两个以YYYY-MM-DD为格式的日期，相差几天
 *
 */
function getChaBetweenTwoDate($date1, $date2)
{
    $Date_List_a1 = explode("-", $date1);
    $Date_List_a2 = explode("-", $date2);
    $d1 = mktime(0, 0, 0, $Date_List_a1[1], $Date_List_a1[2], $Date_List_a1[0]);
    $d2 = mktime(0, 0, 0, $Date_List_a2[1], $Date_List_a2[2], $Date_List_a2[0]);
    $Days = round(($d1 - $d2) / 3600 / 24);
    return $Days;
}
function num2cn($num)
{
    $numarry = array("零", "壹", "贰", "叁", "肆", "伍", "陆", "柒", "捌", "玖", "拾");
    return $numarry[$num];
}
function keepsearchkey($keys, $vals = "")
{
    if (isset($_GET)) {
        foreach ($_GET as $key => $val) {
            if ($val != "") {
                $url[$key] = $val;
            }
        }
    }
    $url[$keys] = $vals;
    if ($vals == "") {
        unset($url[$keys]);
    }
    return U("", $url);
}
/**
 * PHP获取字符串中英文混合长度
 * @param $str string 字符串
 * @param $$charset string 编码
 * @return 返回长度，1中文=1位，2英文=1位
 */
function strLength($str, $charset = 'utf-8')
{
    if ($charset == 'utf-8') {
        $str = iconv('utf-8', 'gb2312', $str);
    }
    $num   = strlen($str);
    $cnNum = 0;
    for ($i = 0; $i < $num; $i++) {
        if (ord(substr($str, $i + 1, 1)) > 127) {
            $cnNum++;
            $i++;
        }
    }
    $enNum  = $num - ($cnNum * 2);
    $number = ($enNum / 2) + $cnNum;
    return ceil($number);
}
function returnAscDesc($key, $default = "asc")
{
    $val = input("session.returnAscDesc" . $key, 0);
    session("returnAscDesc" . $key, intval($val) + 1);
    if ($val & 1) //奇数
    {
        if ($default == "asc") {
            return "asc";
        } else {
            return "desc";
        }
    } else {
        if ($default == "asc") {
            return "desc";
        } else {
            return "asc";
        }
    }
}
/**
 * 友好的时间显示
 *
 * @param int    $sTime 待显示的时间
 * @param string $type  类型. normal | mohu | full | ymd | other
 * @param string $alt   已失效
 * @return string
 */
function friendlyDate($time)
{
    if (empty($time)) {
        return "";
    }
    $now   = time();
    $day   = date('Y-m-d', $time);
    $today = date('Y-m-d');
    $dayArr   = explode('-', $day);
    $todayArr = explode('-', $today);
    //距离的天数，这种方法超过30天则不一定准确，但是30天内是准确的，因为一个月可能是30天也可能是31天
    $days = ($todayArr[0] - $dayArr[0]) * 365 + (($todayArr[1] - $dayArr[1]) * 30) + ($todayArr[2] - $dayArr[2]);
    //距离的秒数
    $secs = $now - $time;
    if ($todayArr[0] - $dayArr[0] > 0 && $days > 3) {
//跨年且超过3天
        return date('Y-m-d', $time);
    } else {
        if ($days < 1) {
//今天
            if ($secs < 60) {
                return $secs . '秒前';
            } elseif ($secs < 3600) {
                return floor($secs / 60) . "分钟前";
            } else {
                return floor($secs / 3600) . "小时前";
            }
        } else if ($days < 2) {
//昨天
            $hour = date('H', $time);
            return "昨天" . $hour . '点';
        } elseif ($days < 3) {
//前天
            $hour = date('H', $time);
            return "前天" . $hour . '点';
        } else {
//三天前
            return date('Y-m-d H:i:s', $time);
        }
    }
}
function httpsPost($url, $data,$async=false)
{
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    //curl_setopt($curl, CURLOPT_SSLVERSION, 3);//兼容https,去掉ssl 版本验证  2018-2-8 10:14:22
    curl_setopt($curl, CURLOPT_POST, 1);
    //curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    if($async)//使用异步,异步是没有执行结果的
    {
        curl_setopt($curl, CURLOPT_NOSIGNAL, 1);
        curl_setopt($curl, CURLOPT_TIMEOUT_MS, 1);//1毫秒超时
    }
    $result = curl_exec($curl);


        // $headinfo = curl_getinfo($curl);
        // if ($this_error = curl_errno($curl) || $headinfo["http_code"]!=200 )

    if (curl_errno($curl)) {
        trace('CJH Curl Error:' . curl_error($curl),"debug");
        trace('CJH Curl Url:'.$url,"debug");
        trace('CJH Curl Data:'.json_encode($data,JSON_UNESCAPED_UNICODE),"debug");
        trace('CJH Curl Async:'.$async,"debug");
        return 'Errno:' . curl_error($curl);
    }
    curl_close($curl);
    return $result;
}
/**
 * 内部curl异步post提交
 * @param  string $url   [description]
 * @param  [type] $param [description]
 * @return [type]        [没有返回值，异步的]
 */
function innerHttpsPost($url="index/index/index",$param=[])
{
    $param["innerHttpsPost"]=true;
    $param["timestamp"]=time();
    //加密
    $param["sign"]=publicRequestjiami($param,config("inner_post_secret"));
    //httpsPost(request()->domain().url($url),$param,true);
    return httpsPost(config("inner_post_domain").url($url),$param,true);//
}
/**
 * //根据签名，验证传递过来的数据是否一致
 * @param  [type] $key   [本地保存的签名]
 * @param  [type] $param [传递过来的数据]
 * @return [type]        [boole]
 */
function checkParamEncryption($key,$param)
{
    if(isset($param["sign"]))
    {
        return $param["sign"]==publicRequestjiami($param,$key);
    }
    else
    {
        trace("sign参数未提交","error");
        return false;
    }
}
/**
 * POST/GET参数加密
 * @param  [type] $key   [秘钥]
 * @param  [type] $param [参数]
 * @return [type]        [sign签名值，md5]
 */
function parameterEncryption($key,$param)
{
    // if(isset($param["sign"]))
    // {
    //     unset($param["sign"]);
    // }
    // $param["signsecret"]=$key;//signsecret是系统加密的关键词,业务参数避免相同
    // //按key进行排序
    // ksort($param);
    // //转换成url格式
    // $url="";
    // foreach ($param as $key => $value) {
    //     $url[]=$key."=".is_string($value)?$value:json_encode($value,JSON_UNESCAPED_UNICODE);
    // }
    // $url = strtolower(join("&",$url));
    // return md5($url);
}
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
    curl_close($curl);
    return $data;
}
function httpsGetAll($url)
{
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HEADER, 0);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); //这个是重点。
    $data     = curl_exec($curl);
    $httpInfo = curl_getinfo($curl);
    $info = array_merge(array("body" => $data), array("header" => $httpInfo));
    //dump($info);
    curl_close($curl);
    return $info;
}
//将资源写入文件
function saveFile($path, $fileContent)
{
    $fp = fopen($path, 'w');
    if (false !== $path) {
        if (false !== fwrite($fp, $fileContent)) {
            fclose($fp);
            return true;
        }
    }
    return false;
}
//生成wx短链接/短地址/短网址
function getwxshorturl($longurl)
{
    require_once "./Site/Extend/wxjssdk/jssdk.php";
    $jssdk       = new JSSDK(C("WX_APPID"), C("WX_APPSECRET"));
    $signPackage = $jssdk->GetSignPackage();
    $data["action"]   = "long2short";
    $data["long_url"] = $longurl;
    $url              = "https://api.weixin.qq.com/cgi-bin/shorturl?access_token=" . $jssdk->AccessToken();
    return https_post($url, json_encode($data));
}
//生成新浪短链接/短地址/短网址
function getdsinashorturl($longurl)
{
    //echo($longurl);
    //$data["url"] = $longurl;
    $url = "http://api.t.sina.com.cn/short_url/shorten.json?source=" . C("sinaappkey") . "&url_long=" . urlencode($longurl);
    $rs  = httpsGet($url, ($data));
    $rs  = json_decode($rs, 1);
    //dump($rs);die();
    return $rs[0]["type"] != 0 ? $longurl : $rs[0]["url_short"];
}
/**
 * php 获取2个日期间 日期
prDates(time(),time());
 */
function prDates($dt_start, $dt_end)
{
    //$dt_start = strtotime($dt_start);
    //$dt_end = strtotime($dt_end);
    $result = array();
    while ($dt_start <= $dt_end) {
        $result[] = date('Y-m-d', $dt_start);
        //$result[]= "'".date('Y-m-d',$dt_start)."'";
        $dt_start = strtotime('+1 day', $dt_start);
    }
    return $result;
}
/**
 * 数据补全
 * @return [type] [description]
 */
function arraydatafix($dataitemarray, $data, $defaultval = "")
{
    foreach ($dataitemarray as $key => $value) {
        if (!isset($data[$value])) {
            $dataval[] = $defaultval;
        } else {
            $dataval[] = $data[$value];
        }
    }
    return $dataval;
}
//公共加密库,防止内容被篡改
function publicRequestjiami($dataArray,$token="")
{
    $stringtosign = "";
    if ($dataArray) {
        $stringtosignArray = [];
        $requestPublicData = $dataArray;
        $requestPublicData["signsecret"]=($token?$token:config("app_token"));
        unset($requestPublicData["sign"]);
        ksort($requestPublicData);
        foreach ($requestPublicData as $key => $value) {
            $stringtosignArray[] = $key . "=" . ((is_array($value)||is_object($value))?json_encode($value):$value);
        }
        $stringtosign = implode("&", $stringtosignArray);
        //trace($stringtosign,"debug");
    }
    $Signature = md5(strtolower($stringtosign));
    //trace($Signature,"debug");
    return $Signature;
}
/**
 * 是否移动端访问访问
 *
 * @return bool
 */
function isMobile()
{
// 如果有HTTP_X_WAP_PROFILE则一定是移动设备
    if (isset($_SERVER['HTTP_X_WAP_PROFILE'])) {
        return true;
    }
// 如果via信息含有wap则一定是移动设备,部分服务商会屏蔽该信息
    if (isset($_SERVER['HTTP_VIA'])) {
// 找不到为flase,否则为true
        return stristr($_SERVER['HTTP_VIA'], "wap") ? true : false;
    }
// 脑残法，判断手机发送的客户端标志,兼容性有待提高
    if (isset($_SERVER['HTTP_USER_AGENT'])) {
        $clientkeywords = array('nokia',
            'sony',
            'ericsson',
            'mot',
            'samsung',
            'htc',
            'sgh',
            'lg',
            'sharp',
            'sie-',
            'philips',
            'panasonic',
            'alcatel',
            'lenovo',
            'iphone',
            'ipod',
            'blackberry',
            'meizu',
            'android',
            'netfront',
            'symbian',
            'ucweb',
            'windowsce',
            'palm',
            'operamini',
            'operamobi',
            'openwave',
            'nexusone',
            'cldc',
            'midp',
            'wap',
            'mobile',
        );
// 从HTTP_USER_AGENT中查找手机浏览器的关键字
        if (preg_match("/(" . implode('|', $clientkeywords) . ")/i", strtolower($_SERVER['HTTP_USER_AGENT']))) {
            return true;
        }
    }
// 协议法，因为有可能不准确，放到最后判断
    if (isset($_SERVER['HTTP_ACCEPT'])) {
// 如果只支持wml并且不支持html那一定是移动设备
        // 如果支持wml和html但是wml在html之前则是移动设备
        if ((strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') !== false) && (strpos($_SERVER['HTTP_ACCEPT'], 'text/html') === false || (strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') < strpos($_SERVER['HTTP_ACCEPT'], 'text/html')))) {
            return true;
        }
    }
    return false;
}
//毫秒
function getMillisecond()
{
    list($t1, $t2) = explode(' ', microtime());
    return (float) sprintf('%.0f', (floatval($t1) + floatval($t2)) * 1000);
}
//以KM为单位的距离计算
function NicegetDistance($num)
{
    if ($num > 500) {
        return round($num / 1000, 2) . "km";
    } else {
        return round($num, 0) . "m";
    }
}
/**
 *  @desc 根据两点间的经纬度计算距离
 *  @param float $lat 纬度值
 *  @param float $lng 经度值
 */
function getDistance($lat1, $lng1, $lat2, $lng2)
{
    $earthRadius = 6367000; //approximate radius of earth in meters
    /*
    Convert these degrees to radians
    to work with the formula
     */
    $lat1 = ($lat1 * pi()) / 180;
    $lng1 = ($lng1 * pi()) / 180;
    $lat2 = ($lat2 * pi()) / 180;
    $lng2 = ($lng2 * pi()) / 180;
    /*
    Using the
    Haversine formula
    http://en.wikipedia.org/wiki/Haversine_formula
    calculate the distance
     */
    $calcLongitude      = $lng2 - $lng1;
    $calcLatitude       = $lat2 - $lat1;
    $stepOne            = pow(sin($calcLatitude / 2), 2) + cos($lat1) * cos($lat2) * pow(sin($calcLongitude / 2), 2);
    $stepTwo            = 2 * asin(min(1, sqrt($stepOne)));
    $calculatedDistance = $earthRadius * $stepTwo;
    return round($calculatedDistance);
}
//获取本周第几天的日期(星期一，1，日期日7)的时间戳
function getDayFromThisWeek($first = 1)
{
    $sdefaultDate = date("Y-m-d");
    //$first =1 表示每周星期一为开始日期 0表示每周日为开始日期
    //获取当前周的第几天 周日是 0 周一到周六是 1 - 6
    $w = date('w', strtotime($sdefaultDate));
    //获取本周开始日期，如果$w是0，则表示周日，减去 6 天
    $week_start = date('Y-m-d', strtotime("$sdefaultDate -" . ($w ? $w - $first : 6) . ' days'));
    return strtotime($week_start);
}
/**
 * 生成二维码
 * http://phpqrcode.sourceforge.net/examples/index.php?example=007
 * @param  string  $value        [二维码的数据]
 * @param  integer $size         [尺寸]1~4
 * @param  boolean $downloadname [生成下载文件，同时指定文件名称]
 * @return [type]                [description]
 */
function qrcode($value='',$size=5,$downloadname=false)
{
    import('phpqrcode', EXTEND_PATH);
    if($downloadname==false)
    {
        $imgfile = \QRcode::png($value);
        return $imgfile;
    }
    else
    {
        $filepath = CACHE_PATH ."_tmp_qrcode.png";
        \QRcode::png($value,$filepath,QR_ECLEVEL_H,$size,1);
        //设置头信息
        header('Content-Disposition:attachment;filename=' . $downloadname.".png");
        header('Content-Length:' . filesize($filepath));
        //读取文件并写入到输出缓冲
        readfile($filepath);
    }
}

function time2second($seconds){
    $seconds = (int)$seconds;
    if( $seconds<86400 ){//如果不到一天
        $format_time = gmstrftime('0天%H小时%M分', ($seconds+60) );
        $format_time = str_replace("00","0",$format_time);
    }else{
        $time = explode(' ', gmstrftime('%j %H %M %S', $seconds));//Array ( [0] => 04 [1] => 14 [2] => 14 [3] => 35 ) 
        $format_time = ($time[0]-1).'天'.$time[1].'时'.(intval($time[2])+1).'分';
    }
    return $format_time;
}

function saleversionname()
{
    switch (config("saleversion")) {
        case 1:
            return "基础版";
            break;
        case 2:
            return "无感支付版";
            break;
        case 3:
            return "旗舰版";
            break;
        
        default:
            # code...
            break;
    }
}

/**
 * 隐藏车牌号中间的数据值
 * @return [type] [description]
 */
function safe_car_number($car_number="")
{
    if(!empty($car_number))
    {
        $strLen = mb_strlen($car_number);
        return strtoupper(mb_substr($car_number,0,2,'utf-8').(["","*","**","***","****","*****"][$strLen-5]).mb_substr($car_number,$strLen-3,3,'utf-8'));
    }
    else
    {
        return $car_number;
    }
}

//自动获取匹配的 alipay id
//2018-12 更新为 生活号 了
//为老的商户兼容老的appid
function get_alipay_id($param=[])
{
    //$param["uid"]
    //$param[""]=$shop_id;
    return config('alipay_app_id');
}

function wLog($msg,$file_dir='')
{
    $common_dir_path = '..'.DS.'runtime'.DS.'log';
    if(empty($file_dir)){
        $file_dir = $common_dir_path.DS.date('Ym').DS.date('Y-m-d').'.log';
    }else{
        $file_dir = $common_dir_path.$file_dir;
    }

    $dir = dirname($file_dir);
    if(!is_dir($dir)){
        mkdir($dir,0777,true);
    }

    $now_time = date('Y-m-d H:i:s',time());
    file_put_contents($file_dir,'['.$now_time.']'."\n".'log_msg:'.$msg."\n",FILE_APPEND);
}