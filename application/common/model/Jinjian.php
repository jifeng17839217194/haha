<?php
namespace app\common\model;
use Jindian\Jindianapi;
use think\Model;

class Jinjian extends Model
{
    protected $type = [
        //'sysconfig' => 'object',
        'jinjian_addtime'    => 'timestamp',
        'jinjian_updatetime' => 'timestamp',
    ];
    protected $autoWriteTimestamp = false;

    //关联
    public function profile()
    {
        //return $this->belongsTo('Province','city_province_id')->field('province_name');
    }

    //更新 进件的 状态
    //轮询的方式
    public function updateStatus()
    {
        //Adminbase::checkActionAuth(request()->controller() . "/index", "add"); //使用内置的机制循环查询，去除权限判断
        $Jinjian    = model("Jinjian");
        $Jindianapi = new Jindianapi();
        $lists      = $Jinjian->where(["jinjian_status" => 2])->limit(10)->select(); //每次检测10家，避免网络卡段
        if ($lists) {
            foreach ($lists as $listsOne) {
                $rs = $Jindianapi->queryStatus($listsOne->jinjian_seller_account);
                //print_r($rs);
                $update_data = [];
                switch ($rs["code"]) {
                    case 1: //1 表示成功
                        $update_data = ["jinjian_status" => 1, "jinjian_status_info" => "", "jinjian_status_time" => time(), "jinjian_qr_url" => $rs["data"]["qrUrl"], "jinjian_stoe_id" => $rs["data"]["stoeId"], "jinjian_device_number" => $rs["data"]["deviceNumber"], "jinjian_trade_key" => $rs["data"]["tradeKey"]];
                        break;
                    case 3: //3 驳回
                        $update_data = ["jinjian_status" => 3, "jinjian_status_info" => $rs["data"]["reject"], "jinjian_status_time" => time(), "jinjian_qr_url" => "", "jinjian_device_number" => "", "jinjian_trade_key" => ""];
                        break;
                }
                //print_r($update_data);
                db("jinjian")->where(["jinjian_id" => $listsOne->jinjian_id])->update($update_data);
            }
            echo "执行了" . count($lists);
        } else {
            echo "执行了0次";
        }

    }
}
