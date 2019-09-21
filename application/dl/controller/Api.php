<?php
namespace app\dl\controller;
use app\dl\controller\Adminbase;

class Api extends Adminbase {
	public function _initialize() {
		parent::_initialize();
	}

	public function index() {
		Adminbase::checkActionAuth();
		$Api = model("Api");
		$where = [];
		$keyword = input("get.keyword", "");
		if ($keyword) {
			$where["api_title"] = ["like", "%" . $keyword . "%"];
		}
		$lists = $Api->where($where)->order(["api_id desc"])->paginate(20);

		if ($lists) {
			/*$cityData=model("City")->column("city_id,city_name");
				            foreach ($lists as $listsOne) {
				                $listsOne->cityname= implode(",",self::idGetVal($listsOne->agents_city_id,$cityData));
			*/
		}

		$this->assign('lists', $lists);
		return view();
	}

	public function add() {
		Adminbase::checkActionAuth("api/index", "add");
		$Api = model("Api");
		$one = [];
		if (input("api_id")) {
			$one = $Api->find(input("api_id"));
		}
		$this->assign("one", $one);
		return view();
		//继续做角色新增的动作11
	}

	public function delete() {
		Adminbase::checkActionAuth("api/index", "delete");
		$api_id = input("api_id/a");
		if ($api_id) {
			$Api = model("Api");
			$Api->where("api_id", "in", $api_id)->delete();
			return ["code" => 1, "message" => "删除成功", "wait" => -1, "url" => url('index')];
		} else {
			return ["code" => 0, "message" => "没有数据删除", "wait" => 1];
		}

	}

	public function copy() {
		Adminbase::checkActionAuth("api/index", "add");
		$api_id = input("api_id/a");
		if ($api_id) {
			$Api = model("Api");
			$lists = $Api->where("api_id", "in", $api_id)->field("api_id", true)->select();
			if ($lists) {
                $addData=[];
				foreach ($lists as $key => $listsOne) {
					$listsOne->api_title = $listsOne->api_title . "_copy";
                    $addData[]=json_decode(json_encode($listsOne), 1);
				}
				$Api->saveAll($addData);

			}
			return ["code" => 1, "message" => "成功成功", "wait" => -1, "url" => url('index')];
		} else {
			return ["code" => 0, "message" => "没有数据删除", "wait" => 1];
		}

	}

	//保存或新增
	public function save() {
		Adminbase::checkActionAuth("api/index", "add");
		$Api = model("Api");

		//判断重复
		$wherehave["api_title"] = input("api_title");
		if (input("api_id/d") > 0) {
			$wherehave["api_id"] = ["neq", input("api_id/d")];
		}
		if ($Api->where($wherehave)->find()) {
			return ["code" => 0, "message" => input("api_title") . " 已经存在!", "url" => "#"];
		}

		$newData = [
			'api_title' => input("api_title"),
			'api_model' => input("post.api_model", "", null),
			'api_action' => input("post.api_action", "", null),
			'api_type' => input("post.api_type", "", null),
			'api_content' => input("post.api_content", "", null),
			'api_param' => input("post.api_param", "", null),
			'api_result' => input("post.api_result", "", null),
		    'api_creater' => input("post.api_creater", "", null),
		];
		if (input("api_id/d") > 0) {
			$newData["api_updatetime"] = time();
		} else {
			$newData["api_addtime"] = time();
			$newData["api_updatetime"] = time();
		}

		$Api->save($newData, input("api_id") ? ['api_id' => input("api_id")] : null);
		return ["code" => 1, "message" => "保存成功", "wait" => -1, "url" => url('index')];
	}

}
