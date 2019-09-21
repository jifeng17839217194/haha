<?php
namespace app\common\model;

use think\Model;

class Agent extends Model
{
    protected $type = [
        //'sysconfig' => 'object',
        'agent_addtime' => 'timestamp',
    ];
    protected $autoWriteTimestamp = false;

    //关联
    public function profile()
    {
        //return $this->belongsTo('Province','city_province_id')->field('province_name');
    }

    /**
     * 获取隶属当前代理商所有的商户（包括子代理商的）shop_id
     * @param  [type] $agent_id [description]
     * @return [type]           [description]
     */
    public function getAllShopIdByAgentId($agent_id)
    {
        
        $fun=function($agent_id)use(&$fun){
            if($sonAgentArray=$this->where(["agent_parent_agent_id"=>$agent_id])->column("agent_id"))
            {
                $thisAgentData=[$agent_id];
                foreach ($sonAgentArray as $agent_id_one) {
                    //dump($agent_id_one);
                    if($thisTimeArray=$fun($agent_id_one))
                    {
                        $thisAgentData=array_merge($thisAgentData,$thisTimeArray);
                    }
                    else
                    {
                        $thisAgentData=array_merge($thisAgentData,[$agent_id_one]);
                    }
                }
                return $thisAgentData;
            }
            else
            {
                //dump($agent_id);die("CC");
                return [$agent_id];
            }
        };
        
        $agentArray=$fun($agent_id);
        return model("shop")->where(["shop_agent_id"=>["in",$agentArray]])->column("shop_id");
    }


    /**
     * 
     * 获取隶属当前代理商所有的上级代理商(包括自己)
     * @param  [type] $agent_id [description]
     * @return [type]           [description]
     */
    public function getParentsAllAgentByAgentId($agent_id)
    {
        
        $fun=function($agent_id)use(&$fun){
            if($ParentAgentArray=$this->where(["agent_id"=>$agent_id])->find())
            {
                $thisAgentData=[$ParentAgentArray];
                if($thisTimeArray=$fun($ParentAgentArray->agent_parent_agent_id))
                {
                    $thisAgentData=array_merge($thisAgentData,$thisTimeArray);
                }
                return $thisAgentData;
            }
            else
            {
                //dump($agent_id);die("CC");
                return false;
            }
        };
        
        $agentArray=$fun($agent_id);
        return $agentArray;
    }

    /**
     * 代理商（店家）登陆
     * 账号有效性判断
     * return:['code' => 1, 'data' => $userOne, 'message' => '']
     */
    public function agentcheckAndLogin($username, $password)
    {

        $password = model("user")->passwordSetMd5($password);
        //判断是否绑定商家
        $agentOne = $this->where(["agent_username" => $username, "agent_password" => $password])->find();
        if ($agentOne) {
            if ($agentOne->agent_active == 0) {
                return ["code"=>0,"message"=>"代理商未激活或已关闭","data"=>""];
            } else {
                return ["code"=>1,"message"=>"","data"=>$agentOne];
            }
        } else {
            return ['code' => 0, 'data' => "", 'message' => '账号或密码错误',"wait"=>5];
        }

    }

    /**
     * userOne :当个用户Object
     * 商户（店家）登陆成功后的后续操作_for PC
     * 前置：账号登陆成功
     * return:没有返回值
     */
    public function afterAgentLoginPC($agentOne)
    {
        $this->save([
            'agent_last_login_ip'   => request()->ip(),
            'agent_last_login_time' => time(),
        ], ['agent_id' => $agentOne->agent_id]);

        //留有session
        session(config("database")["database"] . "agent_id", $agentOne->agent_id);
        session("agent_id", $agentOne->agent_id);
        session("agent_name", $agentOne->agent_name);
        session("agent_company_name", $agentOne->agent_company_name);
        session("agent_proportion", $agentOne->agent_proportion);
        session("agent_open_son_agent", $agentOne->agent_open_son_agent);
        
    }

    /**
     * 用户退出动作
     */
    public function doAgentLogout()
    {
        session(config("database")["database"] . "agent_id", null);
        session("agent_id", null);
        session("agent_name", null);
        session("agent_company_name", null);
        session("agent_proportion", null);
    }
}
