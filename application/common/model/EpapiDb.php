<?php
namespace app\common\model;

use think\Db;
use think\Model;

class EpapiDb extends Model
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

    //计费软件的数据库同步到本地
    public function dblocal2service($postdataarray)
    {
        $postdata = $postdataarray["postdata"];
        if ($postdata) {
            $parking_id = $postdata["parking_id"];
            if ($parking_id) {
                if ($parking_one = db("parking")->where(["parking_uuid" => $parking_id])->find()) //是本服务商的数据
                {
                    //判断表是否存在
                    $service_table_name = strtolower("qs_auto_" . $parking_one["parking_id"] . "_" . $postdata["table_name"]);
                    $exist              = Db::query('show tables like "' . $service_table_name . '"');
                    if (!$exist) {
                        //创建表
                        $this->createtable($service_table_name, $postdata["columns"], $postdata["table_key"]);
                    } else {
                        if ($postdata["table_action"] == "abs_add") {
                            Db::query('drop tables ' . $service_table_name . ''); //绝对新增，要删除原来的数据，采用删除，兼容有“表结构的升级”的情况
                            $this->createtable($service_table_name, $postdata["columns"], $postdata["table_key"]);
                            $postdata["table_action"] = "add";
                        }
                    }
                    $table_data = json_decode(htmlspecialchars_decode($postdata["table_data"]), 1);
                    if ($table_data) {
                        switch ($postdata["table_action"]) {
                            case 'add': //新增
                                $sql_key       = [];
                                $sql_add_value = [];
                                foreach ($table_data as $table_data_item) {
                                    $this_value = [];
                                    foreach ($table_data_item as $key => $table_data_one) {
                                        $sql_key[$key] = $key;
                                        $this_value[]  = $this->fixValueToSql($table_data_one); //要根据字段类型，是否要加上引号;
                                    }
                                    $this_value[]    = "'".$this->getUUID($table_data_item)."'"; //record_uuid,放在最后一条
                                    $sql_add_value[] = "(" . implode(",", $this_value) . ")";
                                }
                                $sql_key["record_uuid"] = "record_uuid"; //添加一个记录唯一ID
                                $sql                    = 'INSERT INTO ' . $service_table_name . '(' . implode(",", $sql_key) . ') VALUES' . implode(",", $sql_add_value);
                                //trace($sql, "debug");
                                Db::query($sql);
                                break;

                            case 'delete': //删除

                                $sql_delete_value = [];
                                foreach ($table_data as $table_data_item) {
                                    // $this_value = [];
                                    // foreach ($table_data_item as $key => $table_data_one) {
                                    //     $table_data_one = $this->fixValueToSql($table_data_one);
                                    //     $this_value[]   = $key . ($table_data_one == 'NULL' ? ' is ' : ' = ') . $table_data_one; //要根据字段类型，是否要加上引号;
                                    // }
                                    $sql_delete_value[] = "(record_uuid='" . $this->getUUID($table_data_item) . "')";
                                }
                                $sql = 'delete from ' . $service_table_name . " where " . implode(" or ", $sql_delete_value) . "";
                                //trace($sql, "debug");
                                Db::query($sql);
                                break;

                            default:
                                # code...
                                break;
                        }
                    } else {
                        trace($service_table_name . "没有数据更新", "debug");
                    }
                }
            }
        } else {
            trace("计费软件的数据库同步到远程,数据格式错误", "error");
        }

        echo "success";die;
    }

    public function getFieldsType($columns, $field)
    {
        $columns_data = json_decode(htmlspecialchars_decode($columns), 1);
        foreach ($columns_data as $columns_data_one) {
            if ($columns_data_one["COLUMN_NAME"] == $field) {
                return $columns_data_one["TYPE_NAME"];
            }
        }
    }

    //给sql加上转义符号
    public function fixValueToSql($value)
    {
        //trace($value,"debug");
        $value = is_null($value) ? 'NULL' : "'" . addslashes($value) . "'";
        //trace("==>".$value,"debug");
        return $value;
    }

    /**
     * 创建数据表
     * @param  [type] $columns_data [description]
     * @return [type]               [description]
     */
    public function createtable($service_table_name, $columns_data, $table_key)
    {
        $columns_data = json_decode(htmlspecialchars_decode($columns_data), 1);
        if ($table_key) {
            $table_key = json_decode(htmlspecialchars_decode($table_key), 1);
        }

        $sql       = "create table " . $service_table_name . "(";
        $sql_array = [];
        foreach ($columns_data as $key => $columns_data_one) {
            $TYPE_NAME = $this->getTypeName($columns_data_one["TYPE_NAME"]);
            $PRECISION = $columns_data_one["PRECISION"];
            switch ($TYPE_NAME) {
                case 'datetime':
                case 'text':
                    $PRECISION = 0;
                    break;

                default:
                    # code...
                    break;
            }
            $sql_array[] = '`' . trim($columns_data_one["COLUMN_NAME"]) . '`' . " " . trim($TYPE_NAME) . "(" . $PRECISION . ")  COLLATE utf8_unicode_ci";
        }
        //数据的uuid
        $sql_array[] = '`record_uuid`' . " char(32)  COLLATE utf8_unicode_ci";
        $sql .= implode(",", $sql_array);

        $table_key_array = [];
        if ($table_key) {
            foreach ($table_key as $table_key_one) {
                $table_key_array[] = 'KEY `' . $table_key_one . '` (`' . $table_key_one . '`) USING BTREE';
            }
            $table_key_array[] = 'KEY `record_uuid` (`record_uuid`) USING BTREE';
            $sql .= ", " . implode(",", $table_key_array);
        }

        $sql .= ")ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
        trace($sql, "debug");
        Db::query($sql);
    }

    //类型转换(sql server 转 mysql 的字段类型)
    public function getTypeName($sql_server_type_name)
    {
        $sql_server2mysql = ["bigint" => "bigint", "binary" => "binary", "bit" => "tinyint", "char" => "char", "date" => "date", "datetime" => "datetime", "datetime2" => "datetime", "datetimeoffset" => "datetime", "decimal" => "decimal", "float" => "float", "int identity" => "int", "int" => "int", "money" => "float", "nchar" => "char", "ntext" => "text", "numeric" => "decimal", "nvarchar" => "varchar", "real" => "float", "smalldatetime" => "datetime", "smallint" => "smallint", "smallmoney" => "float", "text" => "text", "time" => "time", "timestamp" => "timestamp", "tinyint" => "tinyint", "uniqueidentifier" => "varchar", "varbinary" => "varbinary", "varchar" => "varchar", "xml" => "text"]; //字段映射
        return (isset($sql_server2mysql[$sql_server_type_name]) ? $sql_server2mysql[$sql_server_type_name] : "varchar");
    }

    //获得md5唯一
    public function getUUID($value)
    {
        $string = "";
        if(is_array($value))
        {
            $string= http_build_query($value);
        }
        else
        {
            $string = $value;
        }
        $string = strtolower(md5($string));
        return $string; //
    }
}
