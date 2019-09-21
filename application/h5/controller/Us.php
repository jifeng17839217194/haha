<?php
namespace app\h5\controller;
use think\Controller;
use think\Request;
use think\Db;
use think\Session;
/**
* 
*/
class Us extends Controller
{	
	public function help(){
		return view();
	}

	public function about(){
		return view();
	}
}