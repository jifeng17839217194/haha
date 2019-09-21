<?php
namespace app\common\model;

use think\Model;

class AdminRole extends Model
{
    protected $type = [
        'admin_role_addtime' => 'timestamp',
    ];
    protected $autoWriteTimestamp = false;

    //权限检测基础函数
    /**
     * 前置:已经登录，且有效账号，角色也正常
     * $pointID:数字型
     * $action:菜单栏定义的动作
     */
    public function isHavePowder($pointID = "", $action = "", $admin_id)
    {
        //权限节点判断
        $admin_role_powerlist = $this->getAdminPowerArray($admin_id);
        //echo in_array("point_2480_view",$admin_role_powerlist)?1:0;
        //print_r($admin_role_powerlist);die();
        $thisCA = "";
        if ($action == "") {
            $action = "view";
        }

        
        if ($pointID == "") {
            //根据菜单反查结点
            //判断用户对当前模块是否有权限(默认以model+control)
            $request = request();
            $thiscontroller = $request->controller();

            //AdminRole 还原为 admin_role
            $thiscontroller = preg_replace("/^_{1}/", "", strtolower(preg_replace("/([A-Z]{1})/", "_$1", $thiscontroller)));

            $thisCA = $thiscontroller . "/" . $request->action();
            //echo $thisCA; die();
            //trace("权限节点=>".$thisCA);
            $thisCA = strtolower($thisCA);
            $menu = model("Menu");
            $menuOne = $menu->where("menu_url", $thisCA)->find();
            if ($menuOne) {
                $pointID = "point_" . $menuOne->menu_id . "_" . $action;
            } else {
                return ["code" => 0, "message" => "根据当前控制器名核验权限失败（没有找到" . $thisCA . "）"];
            }
        } else {
            //通过指定
            if (is_string($pointID)) {
                $menu = model("Menu");
                $menuOne = $menu->where("menu_url", $pointID)->find();
                if ($menuOne) {
                    $pointID = "point_" . $menuOne->menu_id . "_" . $action;
                } else {
                    return ["code" => 0, "message" => "根据当前控制器名核验权限失败（没有找到" . $pointID . "）"];
                }
            }
            else //通过ID检测
            {
                $pointID = "point_" . $pointID . "_" . $action;
            }

        }
        if (in_array($pointID, $admin_role_powerlist)) {
            return ["code" => 1, "message" => "OK"];
        } else {
            return ["code" => 0, "message" => "没有操作权限"];
        }
    }

    //获取用户的有效权限数组
    public function getAdminPowerArray($admin_id)
    {
        //用户信息
        $adminOne = model("Admin")->cache(true, 1)->field(" ", true)->find($admin_id);
        //__用户信息

        $adminRoleObj = model("AdminRole")->cache(true, 1)->field("admin_role_powerlist")->where('admin_role_id', 'in', $adminOne->admin_admin_role_id)->where('admin_role_active', 1)->select();
        $admin_role_powerlist = [];
        foreach ($adminRoleObj as $adminRoleObjOne) {
            $admin_role_powerlist = array_merge($admin_role_powerlist, json_decode($adminRoleObjOne->admin_role_powerlist, 1));
        }
        return $admin_role_powerlist;
    }
}
