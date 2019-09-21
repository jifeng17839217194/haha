<?php
namespace app\common\model;

use think\Model;

class Sysconfig extends Model
{
    protected $type = [
        'sysconfig' => 'object',
        //'user_last_logintime' => 'timestamp',
    ];
    protected $autoWriteTimestamp = false;

    //  定义全局的查询范围
    protected function base($query)
    {
        
    }

    /**
     * 通用配置保存
     * 万能接收,尽管arrayData过来喔
     * arrayData: key=>value
     */
    public function updateConfig($arrayData)
    {
        $configOne = $this::get(1);
        $newconfig = $configOne->sysconfig;
        if ($arrayData) {
            foreach ($arrayData as $key => $value) {
                $newconfig->$key = $value;
            }
            $configOne->sysconfig = $newconfig;
            $configOne->save();
        }
    }

    /**
     * 通用配置提取
     */
    public function getConfig($itemName="")
    {
        $configOne = $this::get(1);
        $newconfig = $configOne->sysconfig;
        return $itemName?$newconfig->$itemName:$newconfig;
    }
}
