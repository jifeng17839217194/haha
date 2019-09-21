<?php
namespace app\common\model;

use think\Model;

class Appupdate extends Model
{
    protected $type = [
        //'sysconfig' => 'object',
        //'user_last_logintime' => 'timestamp',
    ];
    protected $autoWriteTimestamp = false;

    //关联
    public function profile()
    {
        //return $this->belongsTo('Province','city_province_id')->field('province_name');
    }

    public function getOne()
    {
        return $this->find();
    }

    /**
     * 获取配置名称
     * @param  string $key [跟字段一致]
     * @return [type]      [description]
     */
    public function getValue($field='')
    {
        return $this->value($field);
    }


    public function setValue($field='',$value="")
    {
        $this->isupdate(true)->save([$field=>$value],["appupdate_android_version"=>["neq",""]]);
    }
}
