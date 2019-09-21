<?php
namespace app\dl\controller;

use app\dl\controller\Adminbase;

class ShortUrl extends Adminbase
{
    public function _initialize()
    {
        parent::_initialize();
        if (config("saleversion") <= 2) {
            $this->error(saleversionname()."无此功能");
        }
    }

    public function index()
    {
        Adminbase::checkActionAuth();
        $short_url = model("short_url");
        $where     = [];
        $keyword   = input("get.keyword", "");
        if ($keyword) {
            //$where["short_url_title"] = ["like", "%" . $keyword . "%"];
        }
        $lists = $short_url->where($where)->group("short_url_addtime")->field("short_url_addtime,count(*) as counts")->order("short_url_addtime desc")->paginate(15);

        if ($lists) {
            /*$cityData=model("City")->column("city_id,city_name");
        foreach ($lists as $listsOne) {
        $listsOne->cityname= implode(",",self::idGetVal($listsOne->agents_city_id,$cityData));
        }*/
        }

        $this->assign('lists', $lists);
        return view();
    }

    //保存或新增
    //short_url_key是区分大小写的，且在数据库做了唯一索引
    public function save()
    {
        Adminbase::checkActionAuth("short_url/index", "add");
        $short_url = model("short_url");

        $createcount = input("promptvalue", 1000);
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
                "short_url_action"  => "payqrcode", //支付码
                "short_url_addtime" => $thistime,
            ];
        }

        $short_url->insertAll($newData);
        return ["code" => 1, "message" => "生成成功", "wait" => -1, "url" => url('index')];
    }

    //生成二维码的数据
    public function downloadqrcode()
    {
        set_time_limit(0);
        Adminbase::checkActionAuth("short_url/index", "add");
        $short_url                  = model("short_url");
        $thisaddtime                = input("thisaddtime");
        $where["short_url_addtime"] = $thisaddtime;
        $lists                      = $short_url->where($where)->field("short_url_key")->select();
        $qrhtml                     = [];
        if ($lists) {
            import('phpqrcode', EXTEND_PATH);
            $thisFloder = TEMP_PATH . "qrocde" . DS . $thisaddtime . DS;
            createDir($thisFloder);
            $allFilesPath = [];
            foreach ($lists as $listsOne) {
                $thisurl             = $short_url->geturl($listsOne->short_url_key);
                $qrhtml[]            = $thisurl;
                $pngAbsoluteFilePath = $thisFloder . $listsOne->short_url_key . ".png";
                $allFilesPath[]      = $pngAbsoluteFilePath;
                if (!file_exists($pngAbsoluteFilePath)) {
                    \QRcode::png($thisurl, $pngAbsoluteFilePath, QR_ECLEVEL_L, 30, 1);
                }
            }
            $listFiles = $thisFloder . "二维码的内容.txt";
            file_put_contents($listFiles, implode("\r\n", $qrhtml));
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
        Adminbase::checkActionAuth("short_url/index", "add");
        $filePath = cache("outzipfile");
        //echo $filePath;die();
        if (!$filePath) {
            return;
        }
        $file = fopen($filePath, "r");
        header("Content-Type: application/octet-stream");
        header("Accept-Ranges: bytes");
        header("Accept-Length: " . filesize($filePath));
        header("Content-Disposition: attachment; filename=聚合收款码" . explode(DS, $filePath)[count(explode(DS, $filePath)) - 1]);
        echo fread($file, filesize($filePath));
        fclose($file);
    }

}
