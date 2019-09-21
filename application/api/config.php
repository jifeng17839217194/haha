<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

return [
    // +----------------------------------------------------------------------
    // | 应用设置
    // +----------------------------------------------------------------------

    // 应用调试模式
    'app_debug'           => true,
    // 应用Trace
    'app_trace'           => false,
    // 应用模式状态
    'app_status'          => '',
    'default_return_type' => 'json',
    'log'                 => [
        'type' => 'File',
        // 日志记录级别，使用数组表示
        //level' => ['log', 'error',"info", 'notice', 'debug', 'sql'],
        //'path'  =>  RUNTIME_PATH.'log_api/'
    ],

    /*'log'                    => [
        'type'             => 'socket',
        'host'             => 'slog.iaapp.cn',
        //日志强制记录到配置的client_id
        'force_client_ids' => ["slog_123456"],
        //限制允许读取日志的client_id
        'allow_client_ids' => [],
    ],*/
    // 是否支持多模块

];
