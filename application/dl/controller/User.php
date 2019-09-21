<?php
namespace app\dl\controller;

use app\dl\controller\Adminbase;

class User extends Adminbase
{
    public function _initialize()
    {
        parent::_initialize();
    }

    public function index()
    {
        Adminbase::checkActionAuth("shop/index", "view");
        $user    = model("user");
        $where   = ["user_store_id" => input("user_store_id", 0)];
        $keyword = input("get.keyword", "");
        if ($keyword) {
            $where["user_username|user_realname|user_mobile"] = ["like", "%" . $keyword . "%"];
        }
        $lists = $user->join("__PARKING_CHANNEL__", "parking_channel_user_id=user_id", "left")->where($where)->paginate(15);
        //dump($lists);die;
        if ($lists) {
            foreach ($lists as $listsOne) {
                $listsOne->user_role_name = $user->roleId2NiceName($listsOne);
            }
        }
        $this->assign('lists', $lists);
        return view();
    }

    public function yibotest() //易泊远程测试

    {
        Adminbase::checkActionAuth("shop/index", "add");
        $user_id = input("user_id");

        $parking_record_one = db("parking_channel")->join("__PARKING__", "parking_id=parking_channel_parking_id", "left")->join("__STORE__", "parking_store_id=store_id")->where(["parking_channel_user_id" => $user_id])->find();

        $Parkcommon = new \Parkcommon\Apiget();

        switch (input("actionname")) {
            case 'LED_display':
                $LEDDisplayData = [
                    "from_compay" => isset($parking_record_one["parking_from_compay"]) ? $parking_record_one["parking_from_compay"] : "epapi",
                    "uuid"        => $parking_record_one["parking_uuid"],
                    "port_id"     => $parking_record_one["parking_channel_uuid"],
                    "text"        => [
                        ["row_index" => "1", "row_text" => "支付成功", "row_color" => ""],
                        ["row_index" => "2", "row_text" => "测A123456", "row_color" => ""],
                        ["row_index" => "3", "row_text" => mt_rand(0, 99) . "元", "row_color" => ""],
                        ["row_index" => "4", "row_text" => "一路顺风", "row_color" => ""],
                    ]];

                //$rs=model("epapi")->sendData("LED_display", $LEDDisplayData, "no");
                $rs = $Parkcommon->ledDisplay($LEDDisplayData);
                return $rs;
                break;

            case 'LED_sound':

                $LEDPlayVoiceData = [
                    "from_compay" => isset($parking_record_one["parking_from_compay"]) ? $parking_record_one["parking_from_compay"] : "epapi",
                    "uuid"        => $parking_record_one["parking_uuid"],
                    "port_id"     => $parking_record_one["parking_channel_uuid"],
                    "voice_text"  => "浙AD1Q2T,收费" . mt_rand(0, 99) . "元",
                ];

                //$rs=model("epapi")->sendData("LED_playVoice", $LEDPlayVoiceData, "no");
                $rs = $Parkcommon->playVoice($LEDPlayVoiceData);
                return $rs;
                break;

            case 'open_door':

                $OpenDoorData = [
                    "from_compay" => isset($parking_record_one["parking_from_compay"]) ? $parking_record_one["parking_from_compay"] : "epapi",
                    "uuid"        => $parking_record_one["parking_uuid"],
                    "port_id"     => $parking_record_one["parking_channel_uuid"],
                ];

                //$rs=model("epapi")->sendData("OpenDoor", $OpenDoorData, "no");
                $rs = $Parkcommon->openDoor($OpenDoorData);
                return $rs;
                break;

            default:
                # code...
                break;
        }
    }

    public function add()
    {
        Adminbase::checkActionAuth("shop/index", "add");
        $user_store_id = input("user_store_id");
        $user          = model("user");
        $one           = [];
        if (input("user_id")) {
            $one           = $user->join("__PARKING_CHANNEL__", "parking_channel_user_id=user_id", "left")->where(["user_id" => input("user_id")])->find();
            $user_store_id = $one->user_store_id;
        }
        $store_open_reward = db("store")->where(["store_id" => $user_store_id])->value("store_open_reward");
        $this->assign("store_open_reward", $store_open_reward);
        $this->assign("one", $one);
        return view();
        //继续做角色新增的动作11
    }

    public function delete()
    {
        Adminbase::checkActionAuth("shop/index", "delete");
        $user_id = input("user_id");
        if ($user_id) {
            $user = model("user");
            if (db("order")->where(["order_user_id" => $user_id])->count() > 0) {
                return ["code" => 0, "message" => "该账号下有交易记录，不可删除", "wait" => 1];
            }
            $user->where(["user_id" => $user_id])->delete();
            model("parking_channel")->save(["parking_channel_user_id" => 0], ["parking_channel_user_id" => $user_id]);
            return ["code" => 1, "message" => "删除成功", "wait" => -1, "url" => url('index?user_store_id=' . input("user_store_id/d") . '&target=self')];
        } else {
            return ["code" => 0, "message" => "没有数据删除", "wait" => 1];
        }

    }

    //保存或新增
    public function save()
    {
        Adminbase::checkActionAuth("shop/index", "add");
        $user = model("user");

        $user_mobile = input("user_mobile");

        //判断重复
        if ($user_mobile) {
            $wherehave                = [];
            $wherehave["user_mobile"] = $user_mobile;
            if (input("user_id/d") > 0) {
                $wherehave["user_id"] = ["neq", input("user_id/d")];
            }
            if ($user->where($wherehave)->find()) {
                return ["code" => 0, "message" => $user_mobile . " 已经存在!", "url" => "#"];
            }

            if (!preg_match("/^1[0-9]{10}$/", $user_mobile)) {
                return ["code" => 0, "message" => $user_mobile . " 手机号格式不正确!", "url" => "#"];
            }
        }

        $newData = [
            'user_mobile'      => input("user_mobile"),
            'user_realname'    => input("user_realname"),
            'user_store_id'    => input("user_store_id"),
            'user_refund_auth' => input("user_refund_auth", 0),
            'user_active'      => input("user_active", 1),
            'user_role'        => input("user_role", 2), //默认是最小权限的“收银员”
            'user_play_reward' => input("user_play_reward", 0),
        ];

        //没有强制要填支付密码（2017-12-5 17:03:16，因为输入数据认证不好判断）
        if (input("user_refund_password", "", null)) {
            if (mb_strlen(input("user_refund_password", "", null)) < 6) {
                return ["code" => 0, "message" => "退款密码至少6位", "url" => "#"];
            }
            $newData['user_refund_password'] = $user->passwordSetMd5(input("user_refund_password", "", null));
        }

        if (input("user_password", "", null)) {
            if (mb_strlen(input("user_password", "", null)) < 6) {
                return ["code" => 0, "message" => "密码至少6位", "url" => "#"];
            }
            $newData['user_password'] = $user->passwordSetMd5(input("user_password", null));
            $newData['user_token']    = md5(request()->domain() . getMillisecond() . mt_rand(0, 99999)); //给一个默认的数据;0次登入的情况下要用下；
        }

        if (input("user_id/d") > 0) {
            //$newData["news_updatetime"] = time();
        } else {
            $newData["user_addtime"] = input("user_addtime", time());
        }

        $user->save($newData, input("user_id") ? ['user_id' => input("user_id")] : null);

        $parking_channel_in_or_out = input("parking_channel_in_or_out");
        if ($parking_channel_in_or_out) //设置是出、入口
        {
            db("parking_channel")->where(["parking_channel_user_id" => input("user_id")])->update(["parking_channel_in_or_out" => $parking_channel_in_or_out]); //由于该操作只有“修改”再有，所以不会存在input("user_id")为空的状态
        }

        return ["code" => 1, "message" => "保存成功", "wait" => -1, "url" => url('index?user_store_id=' . input("user_store_id"))];
    }

    public function qrcode()
    {
        $value        = input("value", "");
        $size         = input("size", 8);
        $downloadname = input("downloadname", false);
        return qrcode($value, $size, $downloadname);
    }

    public function downloadconfig()
    {
        Adminbase::checkActionAuth("shop/index", "add");
        $user    = model("user");
        $user_id = input("user_id");
        if ($user_id > 0) {
            $userone = $user->where(["user_id" => $user_id])->find();

            $outconfigfile = ["pay" =>
                [
                    "auth_code_regex" => "^(((10|11|12|13|14|15){1}[0-9]{16})|((25|26|27|28|29|30){1}[0-9]{14,22})){1}$",
                    "remote_url"      => request()->domain() . "/",
                    "user_id"         => $user_id,
                    "user_token"      => $userone->user_token,
                ],
            ];
            session("outconfigfile", $outconfigfile);
            return ["code" => 1, "message" => "", "data" => ["url" => url('downfile')]];
        }
    }

    public function downfile()
    {
        Adminbase::checkActionAuth("shop/index", "add");
        $filePath = TEMP_PATH . DS . "user.json";
        file_put_contents($filePath, json_encode(session("outconfigfile")));
        //echo $filePath;die();
        if (!$filePath) {
            return;
        }
        $file = fopen($filePath, "r");
        header("Content-Type: application/octet-stream");
        header("Accept-Ranges: bytes");
        header("Accept-Length: " . filesize($filePath));
        header("Content-Disposition: attachment; filename=" . explode(DS, $filePath)[count(explode(DS, $filePath)) - 1]);
        echo fread($file, filesize($filePath));
        fclose($file);
    }
}
