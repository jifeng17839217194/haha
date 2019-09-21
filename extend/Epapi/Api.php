<?php
namespace Epapi;

use Epapi\Config;
use Epapi\Net;

/**
 * 易泊 版本
 */
class Api
{
    public $NetObj;
    public function __construct()
    {
        $this->NetObj = new Net();
    }

    /**
     * 获取获取车道信息
     */
    public function getVehicleInfo($param = [])
    {
        $data   = $param;
        $apiuri = "/getportid";
        $this->setReturn(["park_id" => $param["park_id"]]);//同时做下回调设置
        return $this->NetObj->post($data, $apiuri);
    }

    /**
     * 查询停车订单费用
     * $param["orderNo"] //停车订单号 同一车场下唯一
     * $param["parkKey"] //车场唯一编号
     */
    public function getOrderFee($param = [])
    {
        $data   = $param;
        $apiuri = "/getprepayamount";
        return $this->NetObj->post($data, $apiuri);
    }

    /**
     * 查询停车场剩余车位
     */
    public function getRemainingSpace($param = [])
    {
        $data   = $param;
        $apiuri = "/getparkset";
        return $this->NetObj->post($data, $apiuri);
    }

    /**
     * 下发支付完成
     * @param  [type] $param [description]
     * @return [type]        [description]
     */
    public function sendPayOk($param = [])
    {
        trace("硬件通信", "debug");
        trace("动作：sendPayOk，参数↓↓", "debug");
        trace($param, "debug");

        $data   = $param;
        $apiuri = "/payok";
        return $this->NetObj->post($data, $apiuri);
    }

    /**
     * 添加内部车
     * @param [type] $param [description]
     */
    public function addInnerCar($param = [])
    {
        $data   = $param;
        $apiuri = "/addinnercar";
        return $this->NetObj->post($data, $apiuri);
    }

    /**
     * LED 显示
     * @param [type] $param [description]
     */
    public function ledDisplay($param = [])
    {
        $data   = $param;
        $apiuri = "/leddisplay";
        return $this->NetObj->post($data, $apiuri);
    }

    /**
     * 声音播放
     * @param [type] $param [description]
     */
    public function playVoice($param = [])
    {
        $data   = $param;
        $apiuri = "/playvoice";
        return $this->NetObj->post($data, $apiuri);
    }

    /**
     * 设置推送进出场数据的 URL
     * {
    "park_id":"111"， //停车场编号
    }
     * @param [type] $param [description]
     */
    public function setReturn($param = [])
    {
        $data                  = $param;
        $data["user_id"]       = Config::SDKNAME;
        $data["user_password"] = Config::SDKPWD;
        $data["park_name"]     = "";
        $data["url_name"]      = request()->domain() . '/carpark/epapinotice.php'; //回调地址
        $apiuri                = "/setparkurl";
        return $this->NetObj->post($data, $apiuri);
    }

}