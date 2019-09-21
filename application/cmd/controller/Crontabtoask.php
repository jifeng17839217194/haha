<?php
namespace app\cmd\controller;
use think\Controller;
//定时任务专用
class Crontabtoask  extends Controller
{
    public function _initialize()
    {
        // if (time() > 1501383645) {
        //     parent::_initialize();
        // }
    }

    public function index()
    {
        model("scheduled_tasks")->doToast();
        innerHttpsPost("cmd/innerrequest/updateJinjianStatus", []);//更新进件的状态
        //innerHttpsPost("cmd/carparkstatus/notice", []);//停车场状态通知
    }
}
