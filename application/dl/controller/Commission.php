<?php
namespace app\dl\controller;

use app\dl\controller\Adminbase;
use PHPExcel_IOFactory;

class Commission extends Adminbase
{
    public function _initialize()
    {
        parent::_initialize();
    }

    public function index()
    {
        Adminbase::checkActionAuth();
        //全部商户列表
        $shoplist = model("shop")->field("shop_id,shop_name")->order("shop_name asc")->select();
        $this->assign("shoplist", $shoplist);
        return view();
    }

    public function getsearchdata()
    {
        Adminbase::checkActionAuth(request()->controller() . "/index", "view");
        $order_month      = input("order_month") ?: date("Y-m", time());
        $order_channel_id = input("order_channel_id", "");
        $order_shop_id    = input("order_shop_id", "");
        $shop_attr_rates  = input("shop_attr_rates", 0);
        if ($order_shop_id) {
            $whereseardata["shop_id"] = $order_shop_id;
        }

        if ($shop_attr_rates) {
            $whereseardata["shop_attr_wxpay_rates|shop_attr_alipay_rates"] = ["gt", 0];
        }

        $order_month                    = strtotime($order_month . "-1");
        $whereseardata["commission_ym"] = $order_month;
        if ($order_channel_id) {
            $whereseardata["commission_site"] = $order_channel_id;
        }

        $commission = model("commission");

        $rsdata = $commission->join("__SHOP__", "shop_id=commission_shop_id", "left")->join("__SHOP_ATTR__", "shop_id=shop_attr_shop_id", "left")->where($whereseardata)->field("qs_commission.*,shop_name,shop_attr_wxpay_rates,shop_attr_alipay_rates")->order("commission_id desc")->select();
        if ($rsdata) {
            foreach ($rsdata as $rsdataOne) {
                //计算返佣的金额
                $shop_attr_rates = 0;
                if ($rsdataOne->commission_site == "alipay") {
                    $shop_attr_rates = $rsdataOne->shop_attr_alipay_rates;
                } elseif ($rsdataOne->commission_site == "wxpay") {
                    $shop_attr_rates = $rsdataOne->shop_attr_wxpay_rates;
                }

                $rsdataOne->shop_attr_rates_cash = round($shop_attr_rates * $rsdataOne->commission_settle_amount / $rsdataOne->commission_feilv, 2);
                //__计算返佣的金额
                
            }
        }

        return ["code" => 1, "message" => "", "data" => ["list" => $rsdata]];
    }

    /**
     * 上传界面
     * @return [type] [description]
     */
    public function loadindata()
    {
        Adminbase::checkActionAuth(request()->controller() . "/index", "view");
        return view();
    }

    /**
     * 保存数据
     * @return [type] [description]
     */
    public function savedata()
    {
        set_time_limit(0);
        Adminbase::checkActionAuth(request()->controller() . "/index", "add");
        $uploadfilepath = $_POST["uploadfilepath"];
        $exts           = strtolower(pathinfo($uploadfilepath, PATHINFO_EXTENSION));
        if (!in_array($exts, ["xls", "csv"])) {
            @unlink($uploadfilepath);
            return ["code" => 0, "message" => "请上传扩展名为.xls或.csv的表格", "data" => ""];
        }

        //转绝对路径
        $absoluteuploadfilepath = APP_PATH . $uploadfilepath;
        $absoluteuploadfilepath = str_replace("../application//", "", $absoluteuploadfilepath);
        //__转绝对路径

        //创建PHPExcel对象
        if ($exts == 'xls') {
            $PHPExcel = PHPExcel_IOFactory::load($absoluteuploadfilepath);
        } else if ($exts == 'csv') {
            $objReader = PHPExcel_IOFactory::createReader('CSV')
                ->setDelimiter(',')
                ->setInputEncoding('GBK') //不设置将导致中文列内容返回boolean(false)或乱码
                ->setEnclosure('"')
            //->setLineEnding("\r\n")
                ->setSheetIndex(0);
            $PHPExcel = $objReader->load($absoluteuploadfilepath);
        }
        //__创建PHPExcel对象

        $tableData    = [];
        $currentSheet = $PHPExcel->getSheet(0); //第一个表
        //获取总列数
        $allColumn = $currentSheet->getHighestColumn();
        //获取总行数
        $allRow = $currentSheet->getHighestRow();
        //循环获取表中的数据，$currentRow表示当前行，从哪行开始读取数据，索引值从0开始
        for ($currentRow = 1; $currentRow <= $allRow; $currentRow++) {
            //从哪列开始，A表示第一列
            for ($currentColumn = 'A'; $currentColumn <= $allColumn; $currentColumn++) {
                //数据坐标
                $address = $currentColumn . $currentRow;
                //读取到的数据，保存到数组$arr中
                $tableData[$currentRow][$currentColumn] = $currentSheet->getCell($address)->getValue();
            }
        }

        //检测是微信，还是支付宝的月报
        $datafrom = "";
        if ($tableData[1]["A"] == "#服务商名称" && $tableData[2]["A"] == "#服务商PID" && $tableData[3]["A"] == "#业务月份") {
            $datafrom = "alipay";
        } else if ($tableData[1]["A"] == "服务商奖励金明细" && $tableData[2]["B"] == "服务商商户号") {
            $datafrom = "wxpay";
        }
        if ($datafrom == "") {
            return ["code" => 0, "message" => "该表不是官方标准报表", "data" => ""];
        }
        //__检测是微信，还是支付宝的月报

        $datalist = [];
        switch ($datafrom) {
            case 'alipay':
                if ($tableData[2]["B"] != config("sys_service_provider_id")) {
                    return ["code" => 0, "message" => "服务商PID与系统不一致", "data" => ""];
                }
                //提取中间的数据列表
                $datalist      = [];
                $iscanreaddata = false;
                foreach ($tableData as $tableDataOne) {
                    if ($tableDataOne["A"] == null) {
                        $iscanreaddata = false;
                    }

                    if ($iscanreaddata == true && trim($tableDataOne["U"]) > 0) {
//结算金额大于零
                        $datalist[] = [
                            "commission_site"          => "alipay",
                            "commission_ym"            => strtotime(trim(($tableData[3]["B"] . "01"))),
                            "commission_pin_mch_id"    => trim($tableDataOne["B"]),
                            "commission_shop_name"     => trim($tableDataOne["C"]),
                            "commission_addtime"       => time(),
                            "commission_total_amount"  => str_replace(",", "", trim($tableDataOne["R"])),
                            "commission_count_amount"  => trim($tableDataOne["K"]),
                            "commission_feilv"         => trim($tableDataOne["T"]),
                            "commission_settle_amount" => trim($tableDataOne["U"]),
                        ];
                    }
                    if ($tableDataOne["A"] == "业务周期") {
                        $iscanreaddata = true;
                    }
                }
                break;

            case 'wxpay':

                if ($tableData[3]["B"] != config("wxpay_mch_id")) {
                    return ["code" => 0, "message" => "服务商商户号与系统不一致", "data" => ""];
                }
                //提取中间的数据列表
                $datalist      = [];
                $iscanreaddata = false;
                foreach ($tableData as $tableDataOne) {
                    if ($tableDataOne["A"] == "总计") {
                        $iscanreaddata = false;
                    }

                    if ($iscanreaddata == true && trim($tableDataOne["J"]) > 0) {
                        //结算金额大于零
                        $datalist[] = [
                            "commission_site"          => "wxpay",
                            "commission_ym"            => strtotime((str_replace("年", "-", (str_replace("月", "-", $tableData[3]["C"])))) . "1"),
                            "commission_pin_mch_id"    => trim($tableDataOne["A"]),
                            "commission_shop_name"     => trim($tableDataOne["B"]),
                            "commission_addtime"       => time(),
                            "commission_total_amount"  => str_replace(",", "", trim($tableDataOne["G"])),
                            "commission_count_amount"  => trim($tableDataOne["D"]),
                            "commission_feilv"         => round(trim($tableDataOne["I"]) / 100, 4),
                            "commission_settle_amount" => trim($tableDataOne["J"]),
                        ];
                    }
                    if ($tableDataOne["A"] == "特约商户号") {
                        $iscanreaddata = true;
                    }
                }

                break;

            default:
                # code...
                break;
        }
        $tableData = null;
        unset($tableData); //释放内存

        if (!$datalist) {
            return ["code" => 0, "message" => "没有可用数据", "data" => ""];
        }

        $shop = model("shop");

        $mch_id2shop_id    = $shop->column("shop_wxpay_sub_mch_id,shop_id");
        $alipay_id2shop_id = $shop->column("shop_alipay_seller_id,shop_id");

        $commission = model("commission");

        //判断数据库是不是存在记录
        $insertData = [];
        foreach ($datalist as $datalistOne) {
            if (
                !$commission->where(["commission_ym" => $datalistOne["commission_ym"], "commission_pin_mch_id" => $datalistOne["commission_pin_mch_id"]])->field("commission_id")->find() //记录存在不要导进来
                //&&
                //不属于该系统的记录不要导进来(由于)
            ) {
                $datalistOne["commission_shop_id"] = isset($mch_id2shop_id[$datalistOne["commission_pin_mch_id"]]) ? $mch_id2shop_id[$datalistOne["commission_pin_mch_id"]] : (isset($alipay_id2shop_id[$datalistOne["commission_pin_mch_id"]]) ? $alipay_id2shop_id[$datalistOne["commission_pin_mch_id"]] : 0);
                $insertData[]                      = $datalistOne;
            }
        }
        //__判断数据库是不是存在记录

        if (!$insertData) {
            return ["code" => 0, "message" => count($datalist) . "条数据已经存在<br />没有数据插入", "data" => ""];
        }

        $commission->saveAll($insertData);

        return ["code" => 1, "message" => count($datalist) . "条数据插入成功", "data" => ""];
    }

}
