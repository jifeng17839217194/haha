<?php
namespace app\api\controller;
use app\api\controller\Apibase;
/**
* 会员接口表 
* By
* Date:2018-9-10
*/
class Member extends Apibase
{
	/*
	*会员列表
	*/
	public function get_list()
	{
		$keyword=input('keyword','',null);
		$user_id=input('user_id',0);
		$page = input("page",0);
        $per_page = input("per_page",20);
        $rs=model("member")->getlist(['user_id'=>$user_id,'keyword'=>$keyword,'page'=>$page,'per_page'=>$per_page]);
        return $rs;
	}

	/*
	*包月列表 
	*By
	*create:2018-9-11
	*/
	public function car_month_list()
	{
		$keyword=input('keyword','',null);
		$user_id=input('user_id',0);
		$page=input('page',0);
		$per_page=input('per_page',20);
		$rs=model('member_car_record')->user_get_list(['user_id'=>$user_id,'keyword'=>$keyword,'page'=>$page,'per_page'=>$per_page]);
		return $rs;
	}
}