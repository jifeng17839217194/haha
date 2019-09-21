<?php
namespace Kede;

use Kede\Net;

/**
 * 科德外挂版本
 */
class Api
{
    public $NetObj;
    public function __construct()
    {
        $this->NetObj = new Net();
    }

    public function test()
    {
        dump($this->GetVehicleInfo("buasmq59"));
    }

    /**
     * 获取获取车道信息
     * http://doc.cpyht.com/MerDoc/DescribeView?group=7&subgroup=71&code=32
     */
    public function getVehicleInfo($parkKey = "")
    {
        $data   = ["parkKey" => $parkKey];
        $apiuri = "/Api/Inquire/GetVehicleInfo";
        return $this->NetObj->post($data, $apiuri);
    }

    /**
     * 查询停车订单费用
     * $param["orderNo"] //停车订单号 同一车场下唯一
     * $param["parkKey"] //车场唯一编号
     * http://doc.cpyht.com/MerDoc/DescribeView?group=7&subgroup=71&code=28
     */
    public function getOrderFee($param=[])
    {
        $data   = $param;
        $apiuri = "/Api/Inquire/GetOrderFee";
        return $this->NetObj->post($data, $apiuri);
    }


    /**
     * 查询停车场剩余车位
     * $param["parkKey"] //车场唯一编号
     * http://doc.cpyht.com/MerDoc/DescribeView?group=7&subgroup=71&code=28
     */
    public function getRemainingSpace($param=[])
    {
        $data   = $param;
        $apiuri = "/Api/Inquire/GetRemainingSpace";
        return $this->NetObj->post($data, $apiuri);
    }
}
