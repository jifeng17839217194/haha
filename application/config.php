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
    'saleversion'                => 3, //1基本版，2无感支付，3旗舰版
    'softversion'                => "2.0.1",
    'site_title'                 => '杭州实行科技有限公司',

    //异常未支付订单 查询次数
    'scheduled_tasks_limits'     => 12, //查询次数,24次是2分钟,建议24次；

    //异常未支付订单 查询间隔
    'scheduled_tasks_interval'   => 5, //单位是秒，不能低于5秒；因为检查机制的心跳就是5秒，低于5秒都是按5秒执行的；


    //服务器域名
    'site_url'                       =>'https://skingpay.hzsxkj19.com',
    //'sx_site'                    =>1,
    'carpark_token'              => "", //与停车场云端通信的token，每家服务商必需不一致
    //'swoole_port'                => 39050, //需要打开防火墙 1.7.4开始，不再使用这个，全部转http接口

    //极光
    'ajpush_AppKey'              => "",
    'ajpush_Secret'              => "",
    'apns_production'            => true, //是否是生成环境

    //'ajpush_AppKey_seller'=>"",
    //'ajpush_Secret_seller'=>"",
    //'apns_production_seller'=>true,//是否是生成环境

    'app_token'                  => "", //绑定App,防止接口非法调用,加密方式：md5(md5(md5(config['app_token']))+ md5(times+""+ rnds+""+config['app_token']));//在websocket也使用该加密数据

    //发短信用的
    //'sms_key'                   => '3SDK-HSB-0130-',

    //百度map server ak
    'bmap_ak'                    => "",

    //百度语音应用(20万/日请求)
    'baidu_ApiSpeech_APP_ID'     => '',
    'baidu_ApiSpeech_API_KEY'    => '',
    'baidu_ApiSpeech_SECRET_KEY' => '',

    //goEasy的配置
    'goEasy_Subscribe_key'       => "",
    'goEasy_Common_key'          => "",

    //支付宝参数
    'sys_service_provider_id'    => "2088631075574288", //支付宝PID //
    'alipay_app_id'              => "2019081966331712", //支付宝APPID(统一使用生活号的当面付) //
    'alipay_shh_app_id'          => "2019081966331712",//支付宝生活号APPID(代扣里面要用到) //

    'alipay_gatewayUrl'          => "https://openapi.alipay.com/gateway.do", //
    //应用私钥2048
    'alipay_rsaPrivateKey'       => 'MIIEoQIBAAKCAQEAz2VnY6AxKdsQIL8Fq5Q148aV4MnJoWqyTcA2D/UYrgQ2MsO85xIGLg8ds7UJSjKq3Nu30COZqjbhl9xX019pnKveU47Mk6k135/CZyv7h6ANgBcuCrw2E5ACUfbD4LTLt0bTMDP+nhGaoPsfZKjxrEdYXj5cUD97setHZ93CAFTz8/+UR27XbQkv9vUCO9LE3aTezGOVzUGqQmVo2rAeKQOpnFC46CD69Ry4U1ySib1SLay5qokXOhfX2TnVyhc9e4fULduixT+Ehcd90hxbNDoGJ6KHDhVHzXsJz4lMrkHhA5NLLg3PaDO6YUPW1auCO/S6KBEwNHjccQpDkCy6OwIDAQABAoIBABBZ71fUdmvVYKUkSl8ntUP7ETAC2MnILrygjeKeMU7u+BLyib8yFZimCnJzsILQEdaN4CUh2WipIQonDimVGS+IAWRXGtv9eEjkQKB/wDoGLWpO6YthIWZTcUtjCDh8KJ+PjvD6+HUf8DAG/IekJkGt8lKj2gVdq/PwF9Yhjr0V0TxsFnluMl5MQ39Oi4uR9vTXfMJi/KjMbezCjaObtd9b2q6enMihoFoqwLIz3iElHxpJWgOY25nHGlJGPnO7ZXKM8rrO4Gyc2JH5LRUkNjT3gLVLrM56673hU5TKwJgoDQHuOPmLFK3lnHbL8Fk/096UOKJ3EJ96XTC5LauC/PkCgYEA5/DwFdZx3gr5bjr4s9xIe8z7RjAmbKKjssEFJ/Kz2m6ST4bbdIKapr501+3TYwz8yit794Yreaz2k6EKpeI9rlA9ibhjywFVZTPCiaq4/DxxHJ05pgorVj9FfEq5rOqYI38sK7t9/mBvI0eD2swtMUQ0TsMgIhJStMR/I4pXtccCgYEA5Oivumo3fzrKWqbtQHH+yiPQgkWMIjD0VnlgIno5ufCPRnDBWT43DrY6vDsY5k71uwUXU6ioXoxAuQdBVY+quuuONBOWGnp5pUyuWJD0Uqv+vATEbvRewpi1mrEjMAtfXWaEAys2xdWQc2hoYaUbK6Pd/MaC0BNvTNQcnqz3B+0CfyCdwSVRArI22NynblHcqFTAfpVgMAcW1+5LNm5nsuMEqY6FaFb6BsVsAwJab19+dA36D3S/aV2y9PnNq7GoHwRkREMZqu7hQSD6JmE1oM3XshBUC8dNpsp6G0tfNr0aQEq9l3iO5SjgZsCPTft2uuQysyhgSCSCbx78guR8j9kCgYEA3gyNcmJYpVWNN3SXzp0GEGW/fK8kKYKdckjZJXi1CJa/FRCJrh044U+KGE+nbrmHizx8DU4czWJ14kaUbQApGJspXYDmaZcG638/3G/4YT2wpAhn2E26oj/qYj2UqaVOg9bPFhfUUQJC1oCgSKAVhDa4Ptz9xgQkQj276XxVQV0CgYAS8qBN5UgL9N3qsNNVOPnqsLJsAhsONJl/AIHFPNCSF98rEYhh5eWyAoHo8562bQL13r6lqpGkaDLQxYmFUv2wOJ1hG/RR+Q/n/VEjL71aandiCfzBP9u/YZz3R+P6HA3kbJnxo3ux02s2p0STiLxkxjIK2d86JWAjc8mVPs+lbQ==',
    //支付宝公钥(RSA2)
    'alipay_alipayrsaPublicKey'  => 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAhC+gwHxAetizZoFHUJiCtV00PWfXkYEdQuj7AQxlOcJzME3SKEptGNe0HiZ+Yb4ZriIxL2DOO2jIJeBembe8odVeEFrd1tJbvTR9n1sTJtzkNp/3Yyl+nJwfoGCf6BD7EhY4xAbL/aXEBywUe0JgFCJV5RacEgl3cpJoVQ8TXhQilHAzHNWW74uN3yYUGpr8nj6RAYH9unM9ZQwi03eArNvl0HtF2GLg4dm66YHcG3sFlRholQurZibz5XPJAlYixNGmpcSiW92V8eE3szHqWAxlJNt2Pq9/SESbqfJ+IISad4DJNmfS4sZnuBA04o3orXiSik6Rua9Q9isrfu0R8QIDAQAB',

    //微信的数据
    "wxpay_app_id"               => "wxba8876c601928570", //微信分配的公众账号ID(服务商)
    "wxpay_mch_id"               => "1532615291", //微信支付分配的商户号(服务商)
    'wxpay_cert_path'            => EXTEND_PATH . 'apiclient_cert.pem', // XXX: 绝对路径！！！！,服务商的pem文件(服务商)
    'wxpay_key_path'             => EXTEND_PATH . 'apiclient_key.pem', // XXX: 绝对路径！！！！,服务商的pem文件(服务商)
    'wxpay_api_secret'           => "ElTddWYkzBHsK5ZlbIha08sKN4r1lgFI", //理解为API密钥,https://pay.weixin.qq.com/index.php/core/cert/api_cert 在“微信支付”后台->API密钥 设置
    'wxpay_app_secret'           => "485735501573d528a8fc05cfbc994662",

    //内部curl异步post通信授权码
    'inner_post_secret'          => "COQlgulBt4lCJpuu242HguPTbQ2XgACM",
    "inner_post_domain"          => "http://127.0.0.1:2020", //内部post端口，要在nginx开启站点 127.0.0.1:2020 访问,防止域名解析过慢,并且有延时，造成cult "Operation timed out after 100 milliseconds with 0 bytes received" 之类的错误

    // 应用命名空间
    'app_namespace'              => 'app',
    // 应用调试模式
    'app_debug'                  => true,
    // 应用Trace
    'app_trace'                  => false,
    // 应用模式状态
    'app_status'                 => '',
    // 是否支持多模块
    'app_multi_module'           => true,
    // 入口自动绑定模块
    'auto_bind_module'           => false,
    // 注册的根命名空间
    'root_namespace'             => [],
    // 扩展函数文件
    'extra_file_list'            => [THINK_PATH . 'helper' . EXT],
    // 默认输出类型
    'default_return_type'        => 'html',
    // 默认AJAX 数据返回格式,可选json xml ...
    'default_ajax_return'        => 'json',
    // 默认JSONP格式返回的处理方法
    'default_jsonp_handler'      => 'jsonpReturn',
    // 默认JSONP处理方法
    'var_jsonp_handler'          => 'callback',
    // 默认时区
    'default_timezone'           => 'PRC',
    // 是否开启多语言
    'lang_switch_on'             => false,
    // 默认全局过滤方法 用逗号分隔多个
    'default_filter'             => 'htmlspecialchars,trim',
    // 默认语言
    'default_lang'               => 'zh-cn',
    // 应用类库后缀
    'class_suffix'               => false,
    // 控制器类后缀
    'controller_suffix'          => false,

    // +----------------------------------------------------------------------
    // | 模块设置
    // +----------------------------------------------------------------------

    // 默认模块名
    'default_module'             => 'dl',
    // 禁止访问模块
    'deny_module_list'           => ['common'],
    // 默认控制器名
    'default_controller'         => 'Index',
    // 默认操作名
    'default_action'             => 'index',
    // 默认验证器
    'default_validate'           => '',
    // 默认的空控制器名
    'empty_controller'           => 'Error',
    // 操作方法后缀
    'action_suffix'              => '',
    // 自动搜索控制器
    'controller_auto_search'     => false,

    // +----------------------------------------------------------------------
    // | URL设置
    // +----------------------------------------------------------------------

    // PATHINFO变量名 用于兼容模式
    'var_pathinfo'               => 's',
    // 兼容PATH_INFO获取
    'pathinfo_fetch'             => ['ORIG_PATH_INFO', 'REDIRECT_PATH_INFO', 'REDIRECT_URL'],
    // pathinfo分隔符
    'pathinfo_depr'              => '/',
    // URL伪静态后缀
    'url_html_suffix'            => 'html',
    // URL普通方式参数 用于自动生成
    'url_common_param'           => false,
    // URL参数方式 0 按名称成对解析 1 按顺序解析
    'url_param_type'             => 0,
    // 是否开启路由
    'url_route_on'               => true,
    // 路由使用完整匹配
    'route_complete_match'       => false,
    // 路由配置文件（支持配置多个）
    'route_config_file'          => ['route'],
    // 是否强制使用路由
    'url_route_must'             => false,
    // 域名部署
    'url_domain_deploy'          => false,
    // 域名根，如thinkphp.cn
    'url_domain_root'            => '',
    // 是否自动转换URL中的控制器和操作名
    'url_convert'                => true,
    // 默认的访问控制器层
    'url_controller_layer'       => 'controller',
    // 表单请求类型伪装变量
    'var_method'                 => '_method',
    // 表单ajax伪装变量
    'var_ajax'                   => '_ajax',
    // 表单pjax伪装变量
    'var_pjax'                   => '_pjax',
    // 是否开启请求缓存 true自动缓存 支持设置请求缓存规则
    'request_cache'              => false,
    // 请求缓存有效期
    'request_cache_expire'       => null,

    // +----------------------------------------------------------------------
    // | 模板设置
    // +----------------------------------------------------------------------

    'template'                   => [
        // 模板引擎类型 支持 php think 支持扩展
        'type'         => 'Think',
        // 模板路径
        'view_path'    => '',
        // 模板后缀
        'view_suffix'  => 'html',
        // 模板文件名分隔符
        'view_depr'    => DS,
        // 模板引擎普通标签开始标记
        'tpl_begin'    => '{',
        // 模板引擎普通标签结束标记
        'tpl_end'      => '}',
        // 标签库标签开始标记
        'taglib_begin' => '{',
        // 标签库标签结束标记
        'taglib_end'   => '}',
    ],
    'captcha'                    => [ //  验证码字符集合
        'codeSet'  => '2345678abcdefhijkmnpqrstuvwxyzABCDEFGHJKLMNPQRTUVWXY', //  验证码字体大小(px)
        'fontSize' => 18,
        'useCurve' => false, //  是否画混淆曲线
        'imageH'   => 32, //  验证码图片高度
        'imageW'   => 120, //  验证码图片宽度
        'length'   => 4, //  验证码位数
        'reset'    => true, //  验证成功后是否重置
    ],

    // 视图输出字符串内容替换
    'view_replace_str'           => [
        '../assets/'       => SCRIPT_DIR . '/static/dl/assets/',
        '/static/'         => SCRIPT_DIR . '/static/',
        '/caipiao/public/' => SCRIPT_DIR . '/', //历史目录转义到当前的子目录 程剑虎 2017-5-2
    ],
    // 默认跳转页面对应的模板文件
    'dispatch_success_tmpl'      => THINK_PATH . 'tpl' . DS . 'dispatch_jump.tpl',
    'dispatch_error_tmpl'        => THINK_PATH . 'tpl' . DS . 'dispatch_jump.tpl',

    // +----------------------------------------------------------------------
    // | 异常及错误设置
    // +----------------------------------------------------------------------

    // 异常页面的模板文件
    'exception_tmpl'             => THINK_PATH . 'tpl' . DS . 'think_exception2.tpl',

    // 错误显示信息,非调试模式有效
    'error_message'              => '页面错误！请稍后再试～',
    // 显示错误信息
    'show_error_msg'             => false,
    // 异常处理handle类 留空使用 \think\exception\Handle
    'exception_handle'           => '',

    // +----------------------------------------------------------------------
    // | 日志设置
    // +----------------------------------------------------------------------

    'log'                        => [
        // 日志记录方式，内置 file socket 支持扩展
        'type'  => 'File',
        // 日志保存目录
        'path'  => LOG_PATH,
        // 日志记录级别
        'level' => [],
    ],

    // +----------------------------------------------------------------------
    // | Trace设置 开启 app_trace 后 有效
    // +----------------------------------------------------------------------
    'trace'                      => [
        // 内置Html Console 支持扩展
        'type' => 'Html',
    ],

    // +----------------------------------------------------------------------
    // | 缓存设置
    // +----------------------------------------------------------------------

    'cache'                      => [
        // 驱动方式
        'type'   => 'File',
        // 缓存保存目录
        'path'   => CACHE_PATH,
        // 缓存前缀
        'prefix' => '',
        // 缓存有效期 0表示永久缓存
        'expire' => 0,
    ],

    // +----------------------------------------------------------------------
    // | 会话设置
    // +----------------------------------------------------------------------

    'session'                    => [
        'id'             => '',
        // SESSION_ID的提交变量,解决flash上传跨域
        'var_session_id' => '',
        // SESSION 前缀
        'prefix'         => 'think',
        // 驱动方式 支持redis memcache memcached
        'type'           => '',
        // 是否自动开启 SESSION
        'auto_start'     => true,
    ],

    // +----------------------------------------------------------------------
    // | Cookie设置
    // +----------------------------------------------------------------------
    'cookie'                     => [
        // cookie 名称前缀
        'prefix'    => '',
        // cookie 保存时间
        'expire'    => 0,
        // cookie 保存路径
        'path'      => '/',
        // cookie 有效域名
        'domain'    => '',
        //  cookie 启用安全传输
        'secure'    => false,
        // httponly设置
        'httponly'  => '',
        // 是否使用 setcookie
        'setcookie' => true,
    ],

    //分页配置
    'paginate'                   => [
        'type'      => 'bootstrap',
        'var_page'  => 'page',
        'list_rows' => 15,
        'query'     => $_GET,
    ],
    //  开启自动写入时间戳字段
    'auto_timestamp'             => false,
];
