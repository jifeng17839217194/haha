<?php
namespace app\api\controller;

use app\api\controller\Apibase;
use think\captcha\Captcha;

//不开启接口加密，公共调用
class Index extends Apibase
{
    public function _initialize()
    {
        
        // if (time() > 1501383645) {
        //     parent::_initialize();
        // }
    }

    public function index($value = '')
    {
        
    }

    public function qrcode()
    {
        $value = input("value", "");
        $size  = input("size", 8);
        return qrcode($value, $size, false);
    }

    /**
     * [getcaptcha 返回图形验证码]
     * @param  string $identifier [唯一识别码,建议“毫米级时间戳+随机数”]
     * @return [type]             [src的图形]
     */
    public function getcaptcha($identifier = "")
    {
        if (!$identifier) {
            return ["code" => 0, "message" => "参数identifier不可为空", "data" => ""];
        }
        if (strlen($identifier) < 17) {
            return ["code" => 0, "message" => "参数identifier使用时间戳（毫秒）+ 6位以上随机数", "data" => ""];
        }
        ob_clean();
        //if(!$identifier)$identifier=getMillisecond() . mt_rand(0,999999);//避免恶意请求请求间的相互干扰
        $captcha           = new Captcha();
        $captcha->length   = 4;
        $captcha->codeSet  = '0123456789'; //用的收银台输入不了字符
        $captcha->useCurve = false;
        return $captcha->entry($identifier);
    }

    /**
     * APP端，展示公司联系人信息 2018-1-24
     * @return [type] [description]
     */
    public function appinfo()
    {
        $config             = model("Sysconfig")->getConfig();
        $config->site_title = config("site_title");
        return ["code" => 1, "message" => "", "data" => $config];
    }

    /**
     * TCP服务器端
     * 使用php cli 运行 >php public/index.php api/index/tcpserver
     * 停止cli>
     * lsof -i :390001 (查看占用接口的进程)
     * kill 1**** (结束进程)
     * @return [type] [description]
     */
    public function tcpserver()
    {
        $serv = new \swoole_server('0.0.0.0', config("swoole_port"), SWOOLE_PROCESS, SWOOLE_SOCK_TCP);
        $serv->set(array(
            'worker_num'               => 4, //表示启动多少个Worker进程，同样，Worker进程数量不是越多越好，仍然设置为与CPU核心数量相同，或者两倍即可。
            'daemonize'                => true,
            'backlog'                  => 128,
            'heartbeat_check_interval'=>120,//上面的设置就是每120秒侦测一次心跳，一个TCP连接如果在120秒内未向服务器端发送数据，将会被切断。
            //'heartbeat_check_interval' => 3600, //上面的设置就是每5秒侦测一次心跳，一个TCP连接如果在10秒内未向服务器端发送数据，将会被切断。
        ));

        $serv->on('connect', function ($serv, $fd) {
            $returnData = ["code" => 1, "message" => "connect success", "data" => "fd:" . $fd];
            $serv->send($fd, json_encode($returnData, JSON_UNESCAPED_UNICODE));
        });

        $serv->on('receive', function ($serv, $fd, $from_id, $data) {
            $tofd       = $fd;
            $returnData = ["code" => 1, "message" => "", "data" => ""];
            try {
                $JSONArray = json_decode($data, 1);
                if (count($JSONArray) > 0) {
                    $rs = model("tcp")->dotaskv2($serv, $fd, $from_id, $JSONArray);
                    $rs = (!is_array($rs)) ? json_decode($rs, 1) : $rs;
                    //if(isset($rs["data"]["target"]))
                    if (isset($rs["target"])) {
                        $tofd = $rs["target"];
                        if ($serv->exist($tofd)) {
                            $rs = $serv->send($tofd, model("tcp")->datasign($rs["data"]));
                            if($rs==1)
                            {
                                $serv->send($fd, json_encode(["code"=>1,"message"=>"tcp send success","data"=>""]));
                            }
                            else
                            {
                                $serv->send($fd, $rs);
                            }
                            
                        } else {
                            $serv->send($fd, json_encode(["code" => -1, "message" => "[" . $tofd . "]tcp socket contact was closed", "data" => ""]));
                        }

                    } else {
                        if ($serv->exist($tofd)) {
                            $rs=$serv->send($fd, json_encode($rs, JSON_UNESCAPED_UNICODE)); //回复来者
                            if($rs==1)
                            {
                                $serv->send($fd, json_encode(["code"=>1,"message"=>"tcp send success","data"=>""]));
                            }
                            else
                            {
                                $serv->send($fd, $rs);
                            }
                        } else {
                            $serv->send($fd, json_encode(["code" => -1, "message" => "[" . $tofd . "]tcp socket contact was closed", "data" => ""]));
                        }
                    }

                } else {
                    $returnData["code"]    = 0;
                    $returnData["message"] = "param format error";
                    $returnData["data"]    = $data . " this fd is:" . $fd;
                    $serv->send($tofd, json_encode($returnData, JSON_UNESCAPED_UNICODE));
                }
            } catch (Exception $e) {
                $returnData["code"]    = 0;
                $returnData["message"] = "php code error";
                $returnData["data"]    = $data;
                $serv->send($tofd, json_encode($returnData, JSON_UNESCAPED_UNICODE));
            }

        });

        $serv->on('close', function ($serv, $fd) {
            $returnData = ["code" => 0, "message" => "Client Close", "data" => ""];
        });
        $serv->start();
    }
 

    /*public function websocketserver()
    {
        $server = new \swoole_websocket_server("0.0.0.0", 39010);
        $server->on('open', function (\swoole_websocket_server $server, $request) {
            echo "server: handshake success with fd{$request->fd}\n";
        });

        $server->on('message', function (\swoole_websocket_server $server, $frame) {
            echo "receive from {$frame->fd}:{$frame->data},opcode:{$frame->opcode},fin:{$frame->finish}\n";
            $server->push($frame->fd, "this is server");
        });

        $server->on('close', function ($ser, $fd) {
            echo "client {$fd} closed\n";
        });

        $server->start();
    }*/
}
