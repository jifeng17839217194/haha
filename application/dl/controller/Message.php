<?php
namespace app\dl\controller;

use app\dl\controller\Adminbase;

class Message extends Adminbase
{
    public function _initialize()
    {
        parent::_initialize();
    }

    public function index()
    {
        Adminbase::checkActionAuth();
        $Message = model("Message");
        $where = [];
        $keyword = input("get.keyword", "");
        if ($keyword) {
            $where["message_title"] = ["like", "%" . $keyword . "%"];
        }
        $lists = $Message->where($where)->paginate(15);

        $this->assign('lists', $lists);
        return view();
    }

    public function add()
    {
        Adminbase::checkActionAuth("message_id/index", "add");
        $Message = model("Message");
        $one = [];
        if (input("message_id")) {
            $one = $Message->find(input("message_id"));
        }
        $this->assign("one", $one);
        return view();
    }

    public function delete()
    {
        Adminbase::checkActionAuth("message_id/index", "delete");
        $message_id = input("message_id/a");
        if ($message_id) {
            $Message = model("Message");
            $Message->where("message_id", "in", $message_id)->delete();
            return ["code" => 1, "message" => "删除成功", "wait" => -1, "url" => url('index')];
        } else {
            return ["code" => 0, "message" => "没有数据删除", "wait" => 1];
        }

    }

    //发起Push
    public function sendpush()
    {
        Adminbase::checkActionAuth("message_id/index", "add");
        $message_id = input("message_id");
        $messageOne = model("Message")->where("message_id",$message_id)->find();
        $user_pushtoken_array = model("User")->where(["user_pushtoken" => ["neq", ""]])->column("user_pushtoken");
        $pushdata = array(
                "title" => $messageOne["message_title"],
                "text" => "查看详情",
                "needlogin" => 0,
                "type" => "openwin",
                "pagename" => "home_message",
                "pageparam" => array("id" => 1),
        );
        model("Push")->push($user_pushtoken_array, $pushdata);
        return json(["code" => 1, "message" => "已经向" . count($user_pushtoken_array) . "个用户发出推送", "data" => ""]);
    }

    //修改排序
    /*public function sortnum()
    {
    $Message = model("Message");
    if (!is_numeric(input("promptvalue"))) {
    $this->error("必需是数字");
    }
    $Message->save(
    ['message_sortnum' => input("promptvalue"),
    ], ['message_id' => input("message_id")]);
    return ["code" => 1, "message" => "保存成功", "wait" => -1, "url" => request()->server("HTTP_REFERER")];
    }*/

    //保存或新增
    public function save()
    {
        Adminbase::checkActionAuth("message_id/index", "add");
        $Message = model("Message");

        //判断重复
        $wherehave["message_title"] = input("message_title");
        if (input("message_id/d") > 0) {
            $wherehave["message_id"] = ["neq", input("message_id/d")];
        }
        if ($Message->where($wherehave)->find()) {
            return ["code" => 0, "message" => input("message_title") . " 已经存在!", "url" => "#"];
        }

        $newData = [
            'message_title' => input("message_title"),
            'message_content' => input("post.message_content", "", null),
            //'agents_city_id'=>input("agents_city_id/a"),
            //'message_active' => input("message_active/d", 0),
            //'message_sortnum' => input("message_sortnum/d", 0),
        ];
        if (input("message_id/d") > 0) {
            $Message->save($newData, input("message_id") ? ['message_id' => input("message_id")] : null);
        } else {
            $Message->addOne(input("message_title"), input("post.message_content", "", null));
        }
        return ["code" => 1, "message" => "保存成功", "wait" => -1, "url" => url('index')];
    }

}
