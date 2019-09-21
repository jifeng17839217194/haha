<?php
namespace app\common\model;

use think\Model;

//百度类的接口
class Baidu extends Model
{
    protected $type = [
        //'sysconfig' => 'object',
        //'user_last_logintime' => 'timestamp',
    ];
    protected $autoWriteTimestamp = false;

    // 合成人民币的语音，返回如下
    // array(3) {
    //   ["code"] => int(1)
    //   ["message"] => string(0) ""
    //   ["data"] => array(1) {
    //     ["filepath"] => string(51) "/uploads/sound/e10adc3949ba59abbe56e057f20f883e.mp3"
    //   }
    // }
    // https://cloud.baidu.com/product/speech/tts
    public function getcashsoundfile($text = '请传入语音文件')
    {

        import('BaiduAipSpeech.AipSpeech');

        // 你的 APPID AK SK
        $APP_ID     = config("baidu_ApiSpeech_APP_ID");
        $API_KEY    = config("baidu_ApiSpeech_API_KEY");
        $SECRET_KEY = config("baidu_ApiSpeech_SECRET_KEY");
        $client = new \AipSpeech($APP_ID, $API_KEY, $SECRET_KEY);

        $filereleasepath = "".DS."uploads".DS."sound".DS."".md5($text).".mp3"; //百度返回的也是mp3格式
        $filepath = ROOT_PATH ."public".$filereleasepath;
        if(!is_file($filepath))
        {
            $result = $client->synthesis(is_numeric($text)?$this->numToRmb($text):$text, 'zh', 1, array(
            'vol' => 9, 'spd' => 7,
            ));
            if (!is_array($result)) {
                file_put_contents($filepath, $result);
            }
            else
            {
                return ["code"=>0,"message"=>"生成音频文件错误","data"=>json_encode($result)];
            }
        }

        
        return ["code"=>1,"message"=>"","data"=>["filepath"=>$filereleasepath]];
        
    }

    /**
     *数字金额转换成中文大写金额的函数
     *String Int $num 要转换的小写数字或小写字符串
     *return 大写字母
     *小数位为两位
     **/
    public function numToRmb($num)
    {
        $c1 = "零一二三肆伍六柒捌玖";
        $c2 = "分角元拾佰仟万拾佰仟亿";
        //精确到分后面就不要了，所以只留两个小数位
        $num = round($num, 2);
        //将数字转化为整数
        $num = $num * 100;
        if (strlen($num) > 10) {
            return "金额太大，请检查";
        }
        $i = 0;
        $c = "";
        while (1) {
            if ($i == 0) {
                //获取最后一位数字
                $n = substr($num, strlen($num) - 1, 1);
            } else {
                $n = $num % 10;
            }
            //每次将最后一位数字转化为中文
            $p1 = substr($c1, 3 * $n, 3);
            $p2 = substr($c2, 3 * $i, 3);
            if ($n != '0' || ($n == '0' && ($p2 == '亿' || $p2 == '万' || $p2 == '元'))) {
                $c = $p1 . $p2 . $c;
            } else {
                $c = $p1 . $c;
            }
            $i = $i + 1;
            //去掉数字最后一位了
            $num = $num / 10;
            $num = (int) $num;
            //结束循环
            if ($num == 0) {
                break;
            }
        }
        $j    = 0;
        $slen = strlen($c);
        while ($j < $slen) {
            //utf8一个汉字相当3个字符
            $m = substr($c, $j, 6);
            //处理数字中很多0的情况,每次循环去掉一个汉字“零”
            if ($m == '零元' || $m == '零万' || $m == '零亿' || $m == '零零') {
                $left  = substr($c, 0, $j);
                $right = substr($c, $j + 3);
                $c     = $left . $right;
                $j     = $j - 3;
                $slen  = $slen - 3;
            }
            $j = $j + 3;
        }
        //这个是为了去掉类似23.0中最后一个“零”字
        if (substr($c, strlen($c) - 3, 3) == '零') {
            $c = substr($c, 0, strlen($c) - 3);
        }
        //将处理的汉字加上“整”
        if (empty($c)) {
            return "零元";
        } else {
            return $c . "";
        }
    }

}
