<?php
namespace app\api\controller;
use think\Controller;
/**
* 
*/
class Coupon extends Apibase
{
	public function coupon_create()
	{
		$short_url = model("short_url");

        $createcount = input("coupon_num", 1000);
        if (!is_numeric($createcount)) {
            return ["code" => 0, "message" => "请输入正确的数字", "data" => ""];
        }
        if ($createcount > 5000) {
            return ["code" => 0, "message" => "单次不能超过5000", "data" => ""]; //性能限制（后面要生成二维码图片的）
        }

        if ($createcount < 1) {
            return ["code" => 0, "message" => "必需大于0", "data" => ""]; //性能限制
        }

        $thistime = time();
        $newData  = [];
        for ($i = 1; $i <= $createcount; $i++) {
            $newData[] = [
                "short_url_key"     => $short_url->getkeyval(6),
                "short_url_action"  => "couponqrcode", //支付码
                "short_url_addtime" => $thistime,
            ];
        }

        $short_url->insertAll($newData);
        return ["code" => 1, "message" => "生成成功"];
	}	
	public function getcouponlist()
	{
		$short_url = model("short_url");
		$where['short_url_action']='couponqrcode';		
		$lists = $short_url->where($where)->group("short_url_addtime")->field("short_url_addtime,count(*) as counts")->order("short_url_addtime desc")->paginate(150);
		$data_list=array();
		$data_count=0;
		foreach ($lists as $v) {
			$v['short_url_adddate']=date('Y-m-d H:i:s',$v->short_url_addtime);
			$data_count=$data_count+$v->counts;
			$data_list[]=$v;
		}
		return ["code" => 1, "message" => "",'data'=>['data'=>$data_list,'data_count'=>$data_count]];
	}
	public function search_data()
	{
		$short_url = model("short_url");
		$where['short_url_action']='couponqrcode';
		$coupon_addtime_e=input('coupon_addtime_e');
		$coupon_addtime_s=input('coupon_addtime_s');
		if(!empty($coupon_addtime_s)&&!empty($coupon_addtime_e)){
			$start_time=strtotime($coupon_addtime_s.' 00:00:00');
			$end_time=strtotime($coupon_addtime_e.' 23:59:59');
			$where['short_url_addtime']=['between',"$start_time,$end_time"];
		}elseif(!empty($coupon_addtime_s)){
			$where['short_url_addtime']=['egt',strtotime($coupon_addtime_s.' 00:00:00')];
		}elseif(!empty($coupon_addtime_e)){
			$where['short_url_addtime']=['elt',strtotime($coupon_addtime_s.' 23:59:59')];
		}
		$lists = $short_url->where($where)->group("short_url_addtime")->field("short_url_addtime,count(*) as counts")->order("short_url_addtime desc")->paginate(150);
		$data_list=array();
		$data_count=0;
		foreach ($lists as $v) {
			$v['short_url_adddate']=date('Y-m-d H:i:s',$v->short_url_addtime);
			$data_count=$data_count+$v->counts;
			$data_list[]=$v;
		}
		return ["code" => 1, "message" => "",'data'=>['data'=>$data_list,'data_count'=>$data_count]];
	}
	//生成二维码的数据
    public function downloadqrcode()
    {
        set_time_limit(0);
        $short_url                  = model("short_url");
        $thisaddtime                = input("thisaddtime");
        $where["short_url_addtime"] = $thisaddtime;
       	$where['short_url_action']='couponqrcode';
        $lists                      = $short_url->where($where)->field("short_url_key")->select();
        $qrhtml                     = [];
        if ($lists) {
            import('phpqrcode', EXTEND_PATH);
            $thisFloder = TEMP_PATH . "qrocde" . DS . $thisaddtime . DS;
            createDir($thisFloder);
            $allFilesPath = [];
            foreach ($lists as $listsOne) {
                /*$thisurl             = $short_url->geturl($listsOne->short_url_key);*/
                $thisurl=request()->domain()."/user/index/coupon_show";
                $qrhtml[]            = $thisurl;
                $qrhtml_old[]= $short_url->geturl($listsOne->short_url_key);
                $pngAbsoluteFilePath = $thisFloder . $listsOne->short_url_key . ".png";
                $allFilesPath[]      = $pngAbsoluteFilePath;
                if (!file_exists($pngAbsoluteFilePath)) {
                    \QRcode::png($thisurl, $pngAbsoluteFilePath, QR_ECLEVEL_L, 30, 1);
                }
            }
            $listFiles = $thisFloder . "二维码的内容.txt";
            file_put_contents($listFiles, implode("\r\n", $qrhtml_old));
            $allFilesPath[] = $listFiles;
            $zip            = new \ZipArchive();
            $zipname        = date("Y_m_d_H_I_S", $thisaddtime) . ".zip";
            $zip->open($thisFloder . $zipname, \ZipArchive::CREATE); //打开压缩包
            foreach ($allFilesPath as $listFilesOne) {
                $zip->addFile($listFilesOne, basename($listFilesOne)); //向压缩包中添加文件
            }
            $zip->close(); //关闭压缩包
        }

        $outzipfile = TEMP_PATH . "qrocde" . DS . $thisaddtime . DS . $zipname;
        cache("outzipfile", $outzipfile, 5); //采用无对外地址的方式下载
        return ["code" => 1, "message" => "下载的还没做好", "data" => ""];
    }

    //下载zip包
    //路径是cache传递的，不会出现其它文件被下载的安全漏洞
    public function downloadzip()
    {
        $filePath = cache("outzipfile");
        //echo $filePath;die();
        if (!$filePath) {
            return;
        }
        $file = fopen($filePath, "r");
        header("Content-Type: application/octet-stream");
        header("Accept-Ranges: bytes");
        header("Accept-Length: " . filesize($filePath));
        header("Content-Disposition: attachment; filename=优惠券" . explode(DS, $filePath)[count(explode(DS, $filePath)) - 1]);
        echo fread($file, filesize($filePath));
        fclose($file);
    }
}