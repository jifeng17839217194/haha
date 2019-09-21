<?php
namespace app\index\controller;

use think\Controller;
use think\Url;
class Carpark extends Controller
{
    public function index()
    {
        echo "string";
    }

    //相机http 测试
    public function httppost3()
    {
    	trace(file_get_contents('php://input', 'r'),"debug");
    }

}
