<?php
namespace app\common\model;

use think\Model;

class ScheduledTasks extends Model
{
    protected $type = [
        //'sysconfig' => 'object',
        //'user_last_logintime' => 'timestamp',
    ];
    protected $autoWriteTimestamp = false;

    //新增计划任务请求
    public function addOne($paramData)
    {

        if (isset($paramData["scheduled_tasks_title"])) {
            $newData["scheduled_tasks_title"] = $paramData["scheduled_tasks_title"];
        }

        if (isset($paramData["scheduled_tasks_start_time"])) {
            $newData["scheduled_tasks_start_time"] = $paramData["scheduled_tasks_start_time"];
        }

        if (isset($paramData["scheduled_tasks_end_time"])) {
            $newData["scheduled_tasks_end_time"] = $paramData["scheduled_tasks_end_time"];
        }

        if (isset($paramData["scheduled_tasks_last_time"])) {
            $newData["scheduled_tasks_last_time"] = $paramData["scheduled_tasks_last_time"];
        }

        if (isset($paramData["scheduled_tasks_time_interval"])) {
            $newData["scheduled_tasks_time_interval"] = $paramData["scheduled_tasks_time_interval"];
        }

        if (isset($paramData["scheduled_tasks_status"])) {
            $newData["scheduled_tasks_status"] = $paramData["scheduled_tasks_status"];
        }

        if (isset($paramData["scheduled_tasks_name"])) {
            $newData["scheduled_tasks_name"] = $paramData["scheduled_tasks_name"];
        }

        if (isset($paramData["scheduled_tasks_param"])) {
            $newData["scheduled_tasks_param"] = $paramData["scheduled_tasks_param"];
        }

        if (isset($paramData["scheduled_tasks_times_limit"])) {
            $newData["scheduled_tasks_times_limit"] = $paramData["scheduled_tasks_times_limit"];
        }

        if (isset($paramData["scheduled_tasks_times_this"])) {
            $newData["scheduled_tasks_times_this"] = $paramData["scheduled_tasks_times_this"];
        }

        $this->data($newData)->save();
    }

    //查询并执行任务
    public function doToast()
    {
        //搜索有效的任务
        //(未到结束时间的) and (状态是readly的 or 执行中，但执行超过了1分钟的) and （未超过执行次数的）
        //已经过了执行时间，但还是readly的 ; 2017-12-21 21:25:53 新增，处理异常记录
        // $wherestring = "
        // (scheduled_tasks_end_time=0 or scheduled_tasks_end_time>= " . time() . ") and
        // (scheduled_tasks_status='realy' ) and 
        // (scheduled_tasks_times_limit > scheduled_tasks_times_this or scheduled_tasks_times_limit=0)
        // ";

        $wherestring = "
        (scheduled_tasks_status='realy' ) and 
        (scheduled_tasks_times_limit > scheduled_tasks_times_this or scheduled_tasks_times_limit=0)
        ";

        $lists = $this->where($wherestring)->select();
        //trace("本次开始轮训时间：" . date("Y-m-d H:i:s", time()), "debug");
        if ($lists) {
            foreach ($lists as $listsOne) {
                //$scheduled_tasks_time_interval=json_decode($listsOne->scheduled_tasks_time_interval,1);
                if (
                    //按scheduled_tasks_time_interval设定的时间间隔执行
                    ($listsOne->scheduled_tasks_last_time + json_decode($listsOne->scheduled_tasks_time_interval, 1)[0]) <= time()

                ) {
                    $param                       = json_decode($listsOne->scheduled_tasks_param, 1);
                    $param["scheduled_tasks_id"] = $listsOne->scheduled_tasks_id;

                    switch ($listsOne->scheduled_tasks_name) {
                        case 'check_wxpay_order_status': //检测
                        case 'check_alipay_order_status': //检测
                            //trace("有任务了开始时间：" . date("Y-m-d H:i:s", time()), "debug");
                            innerHttpsPost("cmd/innerrequest/checkpayorderstatus", $param);
                            //trace("异步跳过结束时间：" . date("Y-m-d H:i:s", time()), "debug");
                            break;
                        case 'check_alipay_order_freeze_status': //检测支付宝预授权
                            innerHttpsPost("cmd/innerrequest/checkorderfreezestatus", $param);
                            break;
                        default:
                            trace("计划任务：".$listsOne->scheduled_tasks_name."没有实施","debug");
                            break;
                    }
                }
            }
        }
    }
}
