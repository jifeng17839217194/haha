<?php
namespace app\dl\controller;

use think\Request;

class Admin extends Adminbase
{
    public function _initialize()
    {
        $this->request = Request::instance();
        parent::_initialize();
    }

    public function index()
    {
        Adminbase::checkActionAuth();
        $keyWord = $this->request->get('keyword');
        $admin = model("Admin");
        $condition =['admin_username'=>['neq','setconfig']];
        if(!empty($keyWord)){
            $condition['admin_username|admin_nicename'] = ['like',$keyWord.'%'];
        }
        $lists = $admin->where($condition)->order("admin_id", "desc")->select();

        if ($lists) {
            $adminRoleId2Name = model("AdminRole")->column("admin_role_id,admin_role_name");
            foreach ($lists as $listsOne) {
                $role_id = $listsOne->admin_admin_role_id;
                if ($role_id) {
                    $listsOne->admin_admin_role_id = "";
                    $role_id_array = explode(",", $role_id);
                    foreach ($role_id_array as $role_id_one) {
                        if(isset($adminRoleId2Name[$role_id_one]))$listsOne->admin_admin_role_id .= "<span class='label label-sm label-primary arrowed arrowed-right'>" . $adminRoleId2Name[$role_id_one] . "</span><br>";
                    }
                }
            }
        }

        $this->assign('lists', $lists);
        return view();
    }

    //更新我自己的密码
    public function updateMySelfPwd()
    {
        $password=input("promptvalue","",null);
        if(strlen($password)<6)
        {
            $this->error("密码长度必需长于6位");
        }
        $admin = model("Admin");
        $saveData["admin_password"] = $admin->passwordSetMd5($password);
        $admin->save($saveData, ['admin_id' => request()->session(config("database")["database"]."admin_id")]);
        return ["code" => 1, "message" => "更新成功", "wait" => 1, "url" =>""];
    }

    public function add()
    {
        Adminbase::checkActionAuth("admin/index","add");
        $admin = model("Admin");
        $one = [];
        if (input("admin_id")) {
            $one = $admin->find(input("admin_id"));
        }
        $adminRole = model("AdminRole");
        $this->assign("adminRoleList", $adminRole->select());
        $this->assign("one", $one);
        return view();
        //继续做角色新增的动作11
    }
    
    public function delete()
    {
        Adminbase::checkActionAuth("admin/index","delete");
        if (input("admin_id")==request()->session(config("database")["database"] . "admin_id"))
        {
            return ["code" => 0, "message" => "不能删除自己", "wait" => 1];
        }
        if (input("admin_id")) {
            $admin = model("Admin");
            $admin->where("admin_id", input("admin_id"))->delete();
        }
        return ["code" => 1, "message" => "删除成功", "wait" => 1, "url" => url('index')];
    }

    //保存或新增
    public function save()
    {
        Adminbase::checkActionAuth("admin/index","add");
        $admin = model("Admin");

        $id = input("post.id", 0);

        if (!isset($_POST["admin_admin_role_id"])) {
            return ["code" => 0, "message" => "角色不可为空"];
        }

        $saveData = [
            'admin_nicename' => input("admin_nicename"),
            'admin_active' => input("admin_active", 0),
            'admin_admin_role_id' => implode(",", $_POST["admin_admin_role_id"]),
        ];

        if ($id > 0) {
            if (input("admin_password")) {
                if (strlen(input("admin_password")) < 6) {
                    return ["code" => 0, "message" => "密码长度必需长于6位"];
                } else {
                    $saveData["admin_password"] = $admin->passwordSetMd5(input("admin_password", "", null));
                }
            }
        } else {
            if (strlen(input("admin_password")) < 6) {
                return ["code" => 0, "message" => "密码长度必需长于6位"];
            } else {
                $saveData["admin_password"] = $admin->passwordSetMd5(input("admin_password", "", null));
            }
            $saveData["admin_username"] = input("admin_username");
            $saveData["admin_addtime"] = time();
        }

        //判断重复
        $wherehave["admin_username"]=input("admin_username");
        if(input("id/d")>0)
        {
            $wherehave["admin_id"]=["neq",input("id/d")];
        }
        if($admin->where($wherehave)->find())
        {
            return ["code" => 0, "message" => input("admin_username")." 已经存在!","url"=>"#"];
        }



        $admin->save($saveData, input("id") ? ['admin_id' => input("id")] : null);
        return ["code" => 1, "message" => "保存成功", "wait" => -1, "url" => url('index')];
    }

}
