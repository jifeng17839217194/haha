<?php
namespace app\seller\controller;
use app\common\controller\Managebase;

class News extends Managebase
{
    public function _initialize()
    {
        parent::_initialize();
    }

    public function index()
    {
        
        $News = model("News");
        $where = [];
        $keyword = input("get.keyword", "");
        if ($keyword) {
            $where["news_title"] = ["like", "%" . $keyword . "%"];
        }
        $lists = $News->where($where)->order(["case news_sortnum when 0 then 99999999999999999 end asc,news_id desc"])->paginate(15);

        if($lists)
        {
            $cityData=model("City")->column("city_id,city_name");
            foreach ($lists as $listsOne) {
                $listsOne->cityname= implode(",",self::idGetVal($listsOne->agents_city_id,$cityData));
            }
        }

        $this->assign('lists', $lists);
        return view();
    }

     //
    private function idGetVal($idArray,$ObjArray)
    {
        $rs=[];
        foreach ($idArray as $idArraykey) {
            if(isset($ObjArray[$idArraykey]))
            {
                $rs[]=$ObjArray[$idArraykey];
            }
        }
        return $rs;
    }
    
    public function add()
    {
        
        $News = model("News");
        $one = [];
        if (input("news_id")) {
            $one = $News->find(input("news_id"));
        }
        $this->assign("one", $one);
        return view();
        //继续做角色新增的动作11
    }

    public function delete()
    {
        
        $news_id = input("news_id/a");
        if ($news_id) {
            $News = model("News");
            $News->where("news_id", "in", $news_id)->delete();
            return ["code" => 1, "msg" => "删除成功", "wait" => -1, "url" => url('index')];
        } else {
            return ["code" => 0, "msg" => "没有数据删除", "wait" => 1];
        }

    }

    //修改排序
    public function sortnum()
    {
        $News = model("News");
        if (!is_numeric(input("promptvalue"))) {
            $this->error("必需是数字");
        }
        $News->save(
            ['news_sortnum' => input("promptvalue"),
            ], ['news_id' => input("news_id")]);
        return ["code" => 1, "msg" => "保存成功", "wait" => -1, "url" => request()->server("HTTP_REFERER")];
    }

    //保存或新增
    public function save()
    {
        
        $News = model("News");

        //判断重复
        $wherehave["news_title"] = input("news_title");
        if (input("news_id/d") > 0) {
            $wherehave["news_id"] = ["neq", input("news_id/d")];
        }
        if ($News->where($wherehave)->find()) {
            return ["code" => 0, "msg" => input("news_title") . " 已经存在!", "url" => "#"];
        }

        $newData = [
            'news_title' => input("news_title"),
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

        $News->save($newData, input("news_id") ? ['news_id' => input("news_id")] : null);
        return ["code" => 1, "msg" => "保存成功", "wait" => -1, "url" => url('index')];
    }

}
