<?php
namespace app\dl\controller;
use app\dl\controller\Adminbase;

class Demo extends Adminbase
{
    public function _initialize()
    {
        parent::_initialize();
    }

    public function doupdate() //更新程序
    {
        //多次运行没有影响的
        $Epapi   = new \Epapi\Api();
        $rs=db("parking")->where(["parking_uuid"=>["neq",""]])->field("parking_uuid")->select();
        if($rs)
        {
            foreach ($rs as $rsOne) {
                $rsArray = $Epapi->setReturn(["park_id"=>$rsOne["parking_uuid"]]);
                dump($rsArray);
            }
        }
        
    }

    public function index()
    {
        Adminbase::checkActionAuth();
        $new_table = model("new_table");
        $where = [];
        $keyword = input("get.keyword", "");
        if ($keyword) {
            $where["new_table_title"] = ["like", "%" . $keyword . "%"];
        }
        $lists = $new_table->where($where)->order(["case news_sortnum when 0 then 99999999999999999 end asc,news_id desc"])->paginate(15);

        if($lists)
        {
            /*$cityData=model("City")->column("city_id,city_name");
            foreach ($lists as $listsOne) {
                $listsOne->cityname= implode(",",self::idGetVal($listsOne->agents_city_id,$cityData));
            }*/
        }

        $this->assign('lists', $lists);
        return view();
    }

     //
    /*private function idGetVal($idArray,$ObjArray)
    {
        $rs=[];
        foreach ($idArray as $idArraykey) {
            if(isset($ObjArray[$idArraykey]))
            {
                $rs[]=$ObjArray[$idArraykey];
            }
        }
        return $rs;
    }*/
    
    public function add()
    {
        Adminbase::checkActionAuth(request()->controller()."/index", "add");
        $new_table = model("new_table");
        $one = [];
        if (input("news_id")) {
            $one = $new_table->find(input("news_id"));
        }
        $this->assign("one", $one);
        return view();
        //继续做角色新增的动作11
    }

    public function delete()
    {
        Adminbase::checkActionAuth(request()->controller()."/index", "delete");
        $news_id = input("news_id/a");
        if ($news_id) {
            $new_table = model("new_table");
            $new_table->where("news_id", "in", $news_id)->delete();
            return ["code" => 1, "message" => "删除成功", "wait" => -1, "url" => url('index')];
        } else {
            return ["code" => 0, "message" => "没有数据删除", "wait" => 1];
        }

    }

    //修改排序
    public function sortnum()
    {
        $new_table = model("new_table");
        if (!is_numeric(input("promptvalue"))) {
            $this->error("必需是数字");
        }
        $new_table->save(
            ['news_sortnum' => input("promptvalue"),
            ], ['news_id' => input("news_id")]);
        return ["code" => 1, "message" => "保存成功", "wait" => -1, "url" => request()->server("HTTP_REFERER")];
    }

    //保存或新增
    public function save()
    {
        Adminbase::checkActionAuth(request()->controller()."/index", "add");
        $new_table = model("new_table");

        //判断重复
        $wherehave["new_table_title"] = input("new_table_title");
        if (input("news_id/d") > 0) {
            $wherehave["news_id"] = ["neq", input("news_id/d")];
        }
        if ($new_table->where($wherehave)->find()) {
            return ["code" => 0, "message" => input("new_table_title") . " 已经存在!", "url" => "#"];
        }

        $newData = [
            'new_table_title' => input("new_table_title"),
            'news_content' => input("post.news_content", "", null),
            //'agents_city_id'=>input("agents_city_id/a"),
            'news_active' => input("news_active/d", 0),
            'news_sortnum' => input("news_sortnum/d", 0),
        ];
        if (input("news_id/d") > 0) {
            $newData["news_updatetime"] = time();
        } else {
            $newData["news_addtime"] = input("news_addtime", time());
        }

        $new_table->save($newData, input("news_id") ? ['news_id' => input("news_id")] : null);
        return ["code" => 1, "message" => "保存成功", "wait" => -1, "url" => url('index')];
    }

}
