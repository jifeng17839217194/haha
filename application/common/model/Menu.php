<?php
namespace app\common\model;

use think\Model;

class menu extends Model
{
    protected $type = [
        'menu_addtime' => 'timestamp',
    ];
    protected $autoWriteTimestamp = false;

    /**
     * 修改器
     */
    /*public function setmenuPasswordAttr($value)
    {
    return $this->passwordSetMd5($value);
    }*/

    //获得左侧菜单栏目的代码
    public function getTreeForLeftMenu($admin_id)
    {
        $menudate = $this->diGuiMenuTree();
        $html = '<ul class="nav nav-list">';
        foreach ($menudate as $menuOne) {
            $html .= $this->createLeftMenuHtml($menuOne, $admin_id);
        }

        if (input("session." . config("database")["database"] . "admin_id") == 10003) {

            $html .= '<li><a href="'.url('menu/index').'" class="menu/index">';
            $html .= '        <i class="menu-icon glyphicon glyphicon-pencil"></i>';
            $html .= '        <span class="menu-text">';
            $html .= '          菜单管理';
            $html .= '        </span>';
            $html .= '            </a>';
            $html .= '            <b class="arrow"></b></li>';
        }

        $html .= '</ul>';
        return $html;
    }

    //左侧自动url生成
    private function autoLeftMenuUrl($url)
    {
        return url($url);
    }

    //获得菜单栏目页的代码
    public function getTreeForMenuManage()
    {
        $menudate = $this->diGuiMenuTree();
        //dump($menudate);die();
        $html = "";
        foreach ($menudate as $menuOne) {
            $html .= $this->createMenuManageHtml($menuOne);
        }

        return $html;
    }

    //获得select的代码
    public function getTreeForMenuSelect()
    {
        $menudate = $this->diGuiMenuTree();

        $html = "";
        foreach ($menudate as $menuOne) {
            $html .= $this->createMenuSelect($menuOne);
        }

        return $html;
    }

    //获取角色分配页面的代码
    public function getTreeForRoleSelect()
    {
        $menudate = $this->diGuiMenuTree();
        $html = "";
        foreach ($menudate as $menuOne) {
            $html .= $this->createRoleSelect($menuOne);
        }
        return $html;
    }

    private function createRoleSelect($menuOne)
    {

        $html = '';

        $html .= '<div class="form-group"><label class="col-sm-3 text-left">' . $this->getSpace($menuOne["menu_deep"], 2) . ($menuOne["menu_deep"] != 0 ? '┝ ' : '') . '<a href="javascript:;" onclick="javascript:$(\':checkbox[name^=point_' . $menuOne['menu_id'] . '_]\').attr(\'checked\',\'checked\');">' . $menuOne['menu_name'] . '</a></label><div class="col-sm-9">' . $this->createRoleItemHtml($menuOne) . '</div></div>';
        if (isset($menuOne["son"]) && $menuOne["son"] != "") {
            foreach ($menuOne["son"] as $son2) {
                $html .= $this->createRoleSelect($son2);
            }
        }
        return $html;
    }

    private function createRoleItemHtml($menuOne)
    {
        $html = '';
        $menu_powerpoint = $menuOne["menu_powerpoint"];
        if ($menu_powerpoint) {

            $menu_powerpoint_Array = json_decode($menu_powerpoint, 1);
            //dump($menu_powerpoint_Array);
            foreach ($menu_powerpoint_Array as $key => $value) {
                $html .= '<label><input type="checkbox" name="point_' . $menuOne['menu_id'] . '_' . $key . '" value="' . $key . '"/> ' . $value . "</label>&nbsp;&nbsp;";
            }
        }
        return $html;
    }

    private function createMenuSelect($menuOne)
    {
        $html = "";
        $html .= "<option value='" . $menuOne['menu_id'] . "'>" . $this->getSpace($menuOne["menu_deep"]) . ($menuOne["menu_deep"] != 0 ? '┝' : '') . $menuOne['menu_name'] . "</option>";
        if (isset($menuOne["son"]) && $menuOne["son"] != "") {
            foreach ($menuOne["son"] as $son2) {
                $html .= $this->createMenuSelect($son2);
            }
        }
        return $html;
    }

    private function getSpace($int, $doble = 1)
    {
        $space = "";
        if ($int >= 0) {
            for ($i = 0; $i < $int; $i++) {
                if ($doble == 1) {
                    $space .= "&nbsp;&nbsp;&nbsp;&nbsp;";
                } else {
                    $space .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
                }

            }

        }
        return $space;
    }

    private function createMenuManageHtml($menuOne)
    {
        $html = "";
        //dump($menuOne);
        if ($menuOne["menu_fid"] == 0) {
            $addsonhtml = "<a class='add' href='" . url("add?fid=" . $menuOne['menu_id']) . "'>增加子类</a>" . "&nbsp;|&nbsp;<a href='#' ajax='{\"data\":\"menu_id=" . $menuOne['menu_id'] . "\",\"url\":\"" . url("copy") . "\"}'>复制</a>&nbsp;|" . "&nbsp;<a class='add' href='" . url("add?menu_id=" . $menuOne['menu_id']) . "'>编辑</a>" . "&nbsp;|&nbsp;<a href='#' ajax='{\"confirm\":\"删除吗？\",\"data\":\"menu_id=" . $menuOne['menu_id'] . "\",\"url\":\"" . url("delete") . "\"}'>删除</a>&nbsp;";
            $allowdelete = "";
            $allowuserset = "";
            $html = "";
            if (isset($menuOne["son"]) && $menuOne["son"] != "") {
                //dump($menuOne["son"]);die();
                foreach ($menuOne["son"] as $son2) {
                    $html .= $this->createMenuManageHtml($son2);
                }
            }
            $html = "<table class='table table-striped table-bordered table-hover'><thead><tr height=20><th><span class='" . ($menuOne['menu_active'] != 1 ? "menu_hide" : "") . "'>" . $menuOne['menu_name'] . "</span><div class='pull-right'>" . $addsonhtml . $allowuserset . $allowdelete . "<input name='sortnum' type='text' class='sortnum' ajax='{\"data\":\"menu_id=" . $menuOne['menu_id'] . "\",\"url\":\"" . url("changeordernum") . "\",\"prompt\":\"text\"}' value='" . $menuOne['menu_sortnum'] . "'/><input name='id[]' type='hidden' value='" . $menuOne['menu_id'] . "' /></div></th></tr></thead>" . $html . "</table>";
        } else {
            $html = "";

            $allowdelete = "|&nbsp;<a ajax='{\"confirm\":\"删除吗？\",\"data\":\"menu_id=" . $menuOne['menu_id'] . "\",\"url\":\"" . url("delete") . "\"}' href='#'>删除</a>";

            $html .= "<tr height=20 class='" . ($menuOne['menu_active'] == 0 ? "menu_hide" : "") . "'><td ><div class='pull-left '>" . $this->getSpace($menuOne["menu_deep"], 2) . ($menuOne["menu_deep"] != 0 ? '┝' : '') . $menuOne['menu_name'] . "</div><div class='pull-right '><a class='add' href='" . url("add?fid=" . $menuOne['menu_id']) . "'>增加子类</a>" . "&nbsp;|&nbsp;<a href='#' ajax='{\"data\":\"menu_id=" . $menuOne['menu_id'] . "\",\"url\":\"" . url("copy") . "\"}'>复制</a>&nbsp;|" . "&nbsp;<a class='add' href='" . url("add?menu_id=" . $menuOne['menu_id']) . "'>编辑</a>" . "&nbsp;" . $allowdelete . "&nbsp;<input  ajax='{\"data\":\"menu_id=" . $menuOne['menu_id'] . "\",\"url\":\"" . url("changeordernum") . "\",\"prompt\":\"text\"}' name='sortnum' type='text' class='sortnum' value='" . $menuOne['menu_sortnum'] . "' title='修改排序' /><input name='id[]' type='hidden' value='" . $menuOne['menu_sortnum'] . "' /></div></td></tr>";

            if (isset($menuOne["son"]) && $menuOne["son"] != "") {
                foreach ($menuOne["son"] as $son2) {
                    $html .= $this->createMenuManageHtml($son2);
                }
            }

        }
        return $html;
    }

    private function createLeftMenuHtml($menuOne, $admin_id)
    {
        //权限检测
        if (input("session." . config("database")["database"] . "admin_id") != 10003) {

            $isAllowView = model("AdminRole")->isHavePowder($menuOne["menu_id"], "view", $admin_id);
            if ($isAllowView["code"] == 0) {
                return "";
            }

        }

        $html = "";
        $html2 = "";
        $html .= '        <li class="menu_id_'.$menuOne["menu_id"].'">';
        if (isset($menuOne["son"]) && $menuOne["son"] != "") {

            foreach ($menuOne["son"] as $son2) {
                $html2 .= $this->createLeftMenuHtml($son2, $admin_id);
            }

            $html .= '            <a menu_url="'.$menuOne["menu_url"].'" href="' . ($html2 ? "#" : $this->autoLeftMenuUrl($menuOne['menu_url'])) . '" class="' . ($html2 ? "dropdown-toggle" : "") .'">';
            if ($menuOne["menu_fid"] == 0) {
                $html .= '        <i class="menu-icon glyphicon glyphicon-list"></i>';
                $html .= '        <span class="menu-text">';
                $html .= $menuOne['menu_name'];
                $html .= '        </span>';
            } else {
                $html .= '        <i class="menu-icon fa fa-caret-right"></i>' . $menuOne['menu_name'];
            }
            $html .= $html2 ? '       <b class="arrow fa fa-angle-down"></b>' : '';
            $html .= '            </a>';
            $html .= '            <b class="arrow"></b>';
            $html .= $html2 ? '   <ul class="submenu">' : '';
            $html .= $html2;
            $html .= $html2 ? '   </ul>' : '';

        } else {

            $html .= '            <a menu_url="'.$menuOne["menu_url"].'" href="' . $this->autoLeftMenuUrl($menuOne['menu_url']) . '" class="">';
            if ($menuOne["menu_fid"] == 0) {
                $html .= '        <i class="menu-icon fa fa-desktop"></i>';
                $html .= '        <span class="menu-text">';
                $html .= $menuOne['menu_name'];
                $html .= '        </span>';
            } else {
                $html .= '        <i class="menu-icon fa fa-caret-right"></i>' . $menuOne['menu_name'];
            }
            $html .= '            </a>';
            $html .= '            <b class="arrow"></b>';

        }
        $html .= '        </li>';
        return $html;
    }

    private function diGuiMenuTree($fid = 0, $allTable = [])
    {
        if (!$allTable) {
            $allTableRs = $this->field(" ", true)->order("menu_sortnum asc,menu_id asc")->select();
            if ($allTableRs) {
                foreach ($allTableRs as $allTableRsOne) {
                    $allTable[$allTableRsOne->menu_fid][] = $allTableRsOne;
                }
            }
        }
        $rs = isset($allTable[$fid]) ? $allTable[$fid] : "";
        if ($rs) {
            $rs = json_decode(json_encode($rs), true);
            $son = [];
            foreach ($rs as $key => $one) {
                $sonobj = empty($this->diGuiMenuTree($one["menu_id"], $allTable)) ? [] : $this->diGuiMenuTree($one["menu_id"], $allTable);

                if (count($sonobj) > 0) {
                    $rs[$key]["son"] = $sonobj;
                }
            }
        }
        return $rs;
    }
}
