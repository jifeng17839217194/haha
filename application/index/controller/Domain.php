<?php
namespace app\index\controller;

use think\Controller;
use think\Url;
class Domain extends Controller
{
    //test用
    public function index()
    {
        
        echo "isSsl:".(request()->isSsl()?"true":"false");
        //dump($_SERVER);
        die();
        return view();
    }

}
