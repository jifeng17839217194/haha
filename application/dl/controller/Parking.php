<?php
namespace app\dl\controller;

use app\dl\controller\Adminbase;

class Parking extends Adminbase
{
    public function _initialize()
    {
        parent::_initialize();
    }

    public function index()
    {
        Adminbase::checkActionAuth();

        //$this->assign('lists', $lists);
        return view();
    }

    public function config()
    {
        Adminbase::checkActionAuth(request()->controller() . "/config", "add");

        $this->assign('one', []);

        return view();
    }

    /**
     * 等待接入的停车场配置
     * @return [type] [description]
     */
    public function newparking()
    {
        Adminbase::checkActionAuth(request()->controller() . "/newparking");
        $CarAccess = model("CarAccess");
        $where = [];
        $lists = $CarAccess->where($where)->paginate(15);
        $this->assign('lists', $lists);
        return view();
    }

    public function newparkingdelete()
    {
        Adminbase::checkActionAuth(request()->controller()."/newparking", "index");
        $car_access_id = input("car_access_id/a");
        if ($car_access_id) {
            $new_table = model("CarAccess");
            $new_table->where("car_access_id", "in", $car_access_id)->delete();
            return ["code" => 1, "message" => "删除成功", "wait" => -1, "url" => url('newparking')];
        } else {
            return ["code" => 0, "message" => "没有数据删除", "wait" => 1];
        }

    }

    public function save()
    {
        Adminbase::checkActionAuth(request()->controller() . "/config", "add");
        if (request()->isPost()) {
            $data["merchant_name"]          = input("merchant_name", "", null);
            $data["merchant_service_phone"] = input("merchant_service_phone", "", null);
            $data["account_no"]             = input("account_no", "", null);
            $data["interface_info_list"]    = [
                "interface_name" => "alipay.eco.mycar.parking.userpage.query",
                "interface_type" => "interface_page",
                "interface_url"  => "https%3A%2F%2Fwww.parking24.cn%2Frf_carlife_alipay%2FCarLifeAction%21alipayAuth.action",
            ];
            $data["merchant_logo"] = input("merchant_logo", "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAIAAAACCAYAAABytg0kAAAAE0lEQVQImWP81mfxn4GJAYQgAAAqvgLBUG4ufAAAAABJRU5ErkJggg==", null);

            $alipay = model("alipay");
            $app_auth_token = $shopOne         = model("shop")->where(["shop_id" => 10012])->value("shop_alipay_app_auth_token");
            $resultCodeArray = $alipay->request("AlipayEcoMycarParkingConfigSetRequest", $data,$app_auth_token);
            switch ($resultCodeArray["code"]) {
                case 10000:
                    # code...
                    break;
                
                default:
                    return ["code"=>0,"message"=>$resultCodeArray["sub_code"]."<br />".$resultCodeArray["sub_msg"],"data"=>$resultCodeArray];
                    break;
            }
            
        }
    }
}
