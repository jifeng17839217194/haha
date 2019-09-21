<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

// [ 应用入口文件 ]

if(version_compare(phpversion(), "5.5")==-1)
{

	echo "<b>当前php".phpversion()."</b><br />";
	echo "支付宝2.0接口要求php>=5.5<br />";
	echo "ThinkPHP5.0.9要求php>=5.4<br />";
	//阿里虚拟主机最高支持php5.5 2017-3
	die();
}

// 定义应用目录
define('APP_PATH', __DIR__ . '/../application/');
define('SCRIPT_DIR', dirname($_SERVER['SCRIPT_NAME'])=="/"?"":dirname($_SERVER['SCRIPT_NAME']));
// 加载框架引导文件
require __DIR__ . '/../thinkphp/start.php';
