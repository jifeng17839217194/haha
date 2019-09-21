<?php
namespace app\user\controller;
use think\Controller;
use think\Url;
class Index extends Controller
{
    public function index()
    {

        return view();
    }    
    public function _empty()
    {
        return view(request()->action());
    }

    public function qrcode()
    {
        $value        = input("value", "");
        $size         = input("size", 2);
        $downloadname = input("downloadname", false);
        return qrcode($value, $size, $downloadname);
    }
}