/*
Navicat MySQL Data Transfer

Source Server         : 127.0.0.1
Source Server Version : 50553
Source Host           : 127.0.0.1:3306
Source Database       : pay

Target Server Type    : MYSQL
Target Server Version : 50553
File Encoding         : 65001

Date: 2019-09-04 15:19:05
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for `qs_admin`
-- ----------------------------
DROP TABLE IF EXISTS `qs_admin`;
CREATE TABLE `qs_admin` (
  `admin_id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_username` varchar(255) NOT NULL DEFAULT '' COMMENT '用户名',
  `admin_nicename` varchar(255) NOT NULL DEFAULT '' COMMENT '昵称',
  `admin_password` varchar(32) NOT NULL DEFAULT '' COMMENT '密码',
  `admin_email` varchar(50) NOT NULL DEFAULT '' COMMENT '邮箱账号',
  `admin_lastloginip` varchar(50) NOT NULL DEFAULT '' COMMENT '最后登录IP',
  `admin_addtime` int(11) NOT NULL COMMENT '新增时间',
  `admin_lastlogintime` int(11) NOT NULL DEFAULT '0' COMMENT '最后登录时间',
  `admin_active` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态',
  `admin_admin_role_id` varchar(2000) NOT NULL DEFAULT '' COMMENT '支持多角色选择',
  PRIMARY KEY (`admin_id`)
) ENGINE=MyISAM AUTO_INCREMENT=10015 DEFAULT CHARSET=utf8 COMMENT='后台管理员表';

-- ----------------------------
-- Records of qs_admin
-- ----------------------------
INSERT INTO `qs_admin` VALUES ('10003', 'setconfig', '超级管理员', '714c947b12b1876321902e512596d106', '', '115.194.187.98', '0', '1559621047', '1', '');
INSERT INTO `qs_admin` VALUES ('10010', 'adminwzgly', 'adminwzgly', '714c947b12b1876321902e512596d106', '', '183.156.255.198', '1493966714', '1559694294', '1', '9');
INSERT INTO `qs_admin` VALUES ('10014', '123456', 'admin', 'c56d0e9a7ccec67b4ea131655038d604', '', '183.156.255.198', '1559667543', '1559694702', '1', '9');

-- ----------------------------
-- Table structure for `qs_admin_role`
-- ----------------------------
DROP TABLE IF EXISTS `qs_admin_role`;
CREATE TABLE `qs_admin_role` (
  `admin_role_id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_role_name` varchar(1000) NOT NULL DEFAULT '' COMMENT '角色名称',
  `admin_role_powerlist` text NOT NULL COMMENT '保存角色权限的json',
  `admin_role_addtime` int(11) NOT NULL,
  `admin_role_active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`admin_role_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='管理员分组表';

-- ----------------------------
-- Records of qs_admin_role
-- ----------------------------

-- ----------------------------
-- Table structure for `qs_agent`
-- ----------------------------
DROP TABLE IF EXISTS `qs_agent`;
CREATE TABLE `qs_agent` (
  `agent_id` int(11) NOT NULL AUTO_INCREMENT,
  `agent_company_name` char(255) DEFAULT '' COMMENT '代理商公司名称',
  `agent_name` char(100) DEFAULT '' COMMENT '代理商姓名',
  `agent_username` varchar(50) DEFAULT '' COMMENT '代理商账号',
  `agent_password` varchar(32) DEFAULT '' COMMENT '登入密码',
  `agent_mobile` char(255) DEFAULT '' COMMENT '代理商联系电话',
  `agent_addtime` int(11) DEFAULT NULL COMMENT '添加时间',
  `agent_active` tinyint(1) DEFAULT '1',
  `agent_last_login_ip` varchar(255) DEFAULT '',
  `agent_last_login_time` int(11) DEFAULT NULL,
  `agent_proportion` decimal(5,2) DEFAULT '0.00' COMMENT '佣金比例',
  `agent_open_son_agent` tinyint(1) DEFAULT '0' COMMENT '是否权限开设子代理',
  `agent_parent_agent_id` int(11) DEFAULT '0' COMMENT '上级代理商',
  PRIMARY KEY (`agent_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='代理商';

-- ----------------------------
-- Records of qs_agent
-- ----------------------------

-- ----------------------------
-- Table structure for `qs_appupdate`
-- ----------------------------
DROP TABLE IF EXISTS `qs_appupdate`;
CREATE TABLE `qs_appupdate` (
  `appupdate_android_updatetip` varchar(2000) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT 'app升级提示',
  `appupdate_android_version` varchar(2000) COLLATE utf8_unicode_ci NOT NULL COMMENT '版本号',
  `appupdate_android_time` char(20) COLLATE utf8_unicode_ci NOT NULL COMMENT '2016-12-08',
  `appupdate_android_source` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT '下载URL',
  `appupdate_ios_updatetip` varchar(2000) COLLATE utf8_unicode_ci NOT NULL,
  `appupdate_ios_version` varchar(1000) COLLATE utf8_unicode_ci DEFAULT '',
  `appupdate_ios_time` char(20) COLLATE utf8_unicode_ci NOT NULL,
  `appupdate_ios_source` varchar(255) COLLATE utf8_unicode_ci NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ----------------------------
-- Records of qs_appupdate
-- ----------------------------

-- ----------------------------
-- Table structure for `qs_car_access`
-- ----------------------------
DROP TABLE IF EXISTS `qs_car_access`;
CREATE TABLE `qs_car_access` (
  `car_access_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `car_access_out_access_id` bigint(20) DEFAULT '0' COMMENT '来自数据源的ＩＤ',
  `car_access_number_plate` char(20) DEFAULT NULL COMMENT '车牌号',
  `car_access_out_port_id` int(11) DEFAULT NULL COMMENT '出入口ID',
  `car_access_out_time` int(11) DEFAULT NULL COMMENT '经过的时间',
  `car_access_color` varchar(50) DEFAULT '' COMMENT '车牌号',
  `car_access_out_cartype` varchar(255) DEFAULT '' COMMENT '当前车辆收费类型(如：临时车)',
  `car_access_out_parking_id` int(11) DEFAULT '0' COMMENT '停车场的ＩＤ(来自外部）',
  `car_access_addtime` int(11) DEFAULT NULL COMMENT '新增的时间',
  PRIMARY KEY (`car_access_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='车辆进出记录数据';

-- ----------------------------
-- Records of qs_car_access
-- ----------------------------

-- ----------------------------
-- Table structure for `qs_cash_record`
-- ----------------------------
DROP TABLE IF EXISTS `qs_cash_record`;
CREATE TABLE `qs_cash_record` (
  `cashrecord_id` int(255) NOT NULL AUTO_INCREMENT,
  `cashrecord_type` char(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '动作标识',
  `cashrecord_cash` double(20,2) NOT NULL DEFAULT '0.00' COMMENT '交易金额',
  `cashrecord_from_obj_id` int(11) NOT NULL COMMENT '来源ID,0为系统(充值\\扣保证金)',
  `cashrecord_obj_id_type` char(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'user' COMMENT '流水拥有者身份，默认user',
  `cashrecord_obj_id` int(11) NOT NULL COMMENT '流水拥有者的ID,可普通用户、店铺、代理商、平台',
  `cashrecord_addtime` int(11) NOT NULL COMMENT '发生时间',
  `cashrecord_ip` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '发生IP',
  `cashrecord_lon` double(11,6) NOT NULL DEFAULT '0.000000',
  `cashrecord_lat` double(11,6) NOT NULL DEFAULT '0.000000',
  `cashrecord_balance` double(20,2) NOT NULL DEFAULT '0.00' COMMENT '当前余额',
  `cashrecord_aboutid` char(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '相关ID',
  `cashrecord_aboutcode` varchar(3000) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '相关代码',
  `cashrecord_title` varchar(500) COLLATE utf8_unicode_ci DEFAULT '' COMMENT '简要说明',
  `cashrecord_desc` varchar(255) COLLATE utf8_unicode_ci DEFAULT '',
  PRIMARY KEY (`cashrecord_id`),
  KEY `cashrecord_type` (`cashrecord_type`) USING BTREE,
  KEY `cashrecord_addtime` (`cashrecord_addtime`) USING BTREE,
  KEY `uid` (`cashrecord_obj_id`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ----------------------------
-- Records of qs_cash_record
-- ----------------------------

-- ----------------------------
-- Table structure for `qs_category`
-- ----------------------------
DROP TABLE IF EXISTS `qs_category`;
CREATE TABLE `qs_category` (
  `category_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `category_name` varchar(20) NOT NULL DEFAULT '' COMMENT '分类名',
  `shop_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '商户id',
  `store_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '店铺id',
  `img` varchar(255) DEFAULT '' COMMENT '分类图片地址',
  `sort` int(11) unsigned NOT NULL COMMENT '排序',
  `is_delete` tinyint(4) unsigned NOT NULL DEFAULT '0' COMMENT '是否删除 0否 1是',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '添加时间',
  PRIMARY KEY (`category_id`),
  KEY `merchant_id` (`shop_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='分类表';

-- ----------------------------
-- Records of qs_category
-- ----------------------------

-- ----------------------------
-- Table structure for `qs_commission`
-- ----------------------------
DROP TABLE IF EXISTS `qs_commission`;
CREATE TABLE `qs_commission` (
  `commission_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `commission_ym` int(11) NOT NULL COMMENT '月份,时间戳',
  `commission_pin_mch_id` char(16) DEFAULT '' COMMENT '支付宝商户pid,微信的商户id',
  `commission_shop_name` char(150) DEFAULT '' COMMENT '微信、支付宝登记的商户名称',
  `commission_addtime` int(11) DEFAULT NULL COMMENT '记录直接时间',
  `commission_total_amount` decimal(20,2) DEFAULT '0.00' COMMENT '有效交易金额',
  `commission_count_amount` int(11) DEFAULT '0' COMMENT '有效交易笔数',
  `commission_feilv` decimal(7,4) DEFAULT '0.0000' COMMENT '结算费率,如0.0055',
  `commission_settle_amount` decimal(20,2) DEFAULT '0.00' COMMENT '应结算总金额',
  `commission_site` char(10) DEFAULT '' COMMENT 'alipay,wxpay',
  `commission_shop_id` int(11) DEFAULT '0' COMMENT '商户shop_id',
  PRIMARY KEY (`commission_id`),
  KEY `commission_ym` (`commission_ym`),
  KEY `commission_pin_mch_id` (`commission_pin_mch_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='微信服务奖励金\\支付宝协作费率,官方月数据';

-- ----------------------------
-- Records of qs_commission
-- ----------------------------

-- ----------------------------
-- Table structure for `qs_goods`
-- ----------------------------
DROP TABLE IF EXISTS `qs_goods`;
CREATE TABLE `qs_goods` (
  `goods_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `shop_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '商户id',
  `store_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '店铺id',
  `cate_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '商品分类id',
  `goods_name` varchar(30) NOT NULL DEFAULT '' COMMENT '商品名称',
  `price` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '商品价格',
  `goods_unit` varchar(5) NOT NULL DEFAULT '个' COMMENT '商品单位',
  `sort` int(11) unsigned NOT NULL DEFAULT '1' COMMENT '排序',
  `is_delete` tinyint(4) unsigned NOT NULL DEFAULT '0' COMMENT '是否删除 0否 1是',
  `img` varchar(255) NOT NULL DEFAULT '' COMMENT '商品图片地址',
  `remark` varchar(30) NOT NULL DEFAULT '' COMMENT '备注',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '添加时间',
  PRIMARY KEY (`goods_id`),
  KEY `merchant_id` (`shop_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='商品表';

-- ----------------------------
-- Records of qs_goods
-- ----------------------------

-- ----------------------------
-- Table structure for `qs_goods_order`
-- ----------------------------
DROP TABLE IF EXISTS `qs_goods_order`;
CREATE TABLE `qs_goods_order` (
  `order_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `merchant_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '商户id',
  `order_sn` varchar(30) NOT NULL DEFAULT '' COMMENT '订单号',
  `order_price` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '订单价格',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '订单状态 0待支付 1已支付',
  `is_delete` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否删除 0否 1是',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '添加时间',
  PRIMARY KEY (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='订单表';

-- ----------------------------
-- Records of qs_goods_order
-- ----------------------------

-- ----------------------------
-- Table structure for `qs_member`
-- ----------------------------
DROP TABLE IF EXISTS `qs_member`;
CREATE TABLE `qs_member` (
  `member_id` int(11) NOT NULL AUTO_INCREMENT,
  `member_nickname` varchar(255) NOT NULL DEFAULT '' COMMENT '昵称',
  `member_realname` varchar(32) NOT NULL DEFAULT '' COMMENT '真实姓名',
  `member_tel` varchar(32) NOT NULL DEFAULT '' COMMENT '绑定的手机号码',
  `member_company_name` varchar(255) NOT NULL DEFAULT '' COMMENT '公司名称',
  `member_company_address` varchar(255) NOT NULL DEFAULT '' COMMENT '公司地址',
  `member_wx` varchar(255) NOT NULL DEFAULT '' COMMENT '绑定微信账号',
  `member_zfb` varchar(255) NOT NULL DEFAULT '' COMMENT '支付宝账号',
  `member_addtime` int(11) NOT NULL DEFAULT '0' COMMENT '新增时间',
  `member_wx_openid` varchar(64) NOT NULL DEFAULT '' COMMENT '微信openid',
  `member_wx_nickname` varchar(255) NOT NULL DEFAULT '' COMMENT '微信昵称',
  `member_wx_sex` tinyint(1) NOT NULL DEFAULT '0' COMMENT '微信性别',
  `member_wx_headimgurl` varchar(255) NOT NULL DEFAULT '' COMMENT '微信头像',
  `member_zfb_nickname` varchar(255) NOT NULL DEFAULT '' COMMENT '支付宝昵称',
  `member_zfb_userid` varchar(32) NOT NULL DEFAULT '' COMMENT '支付宝中用户ID',
  `member_zfb_content` varchar(2000) NOT NULL DEFAULT '' COMMENT '支付宝用户其他信息',
  PRIMARY KEY (`member_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COMMENT='用户表 By zhjhqk';

-- ----------------------------
-- Records of qs_member
-- ----------------------------

-- ----------------------------
-- Table structure for `qs_member_car`
-- ----------------------------
DROP TABLE IF EXISTS `qs_member_car`;
CREATE TABLE `qs_member_car` (
  `car_id` int(11) NOT NULL AUTO_INCREMENT,
  `car_member_id` int(11) NOT NULL DEFAULT '0' COMMENT '用户ID',
  `car_number_plate` varchar(32) NOT NULL DEFAULT '' COMMENT '车牌号',
  `car_addtime` int(11) NOT NULL DEFAULT '0' COMMENT '车牌绑定时间',
  PRIMARY KEY (`car_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COMMENT='用户绑定的车辆表 By zhjhqk';

-- ----------------------------
-- Records of qs_member_car
-- ----------------------------

-- ----------------------------
-- Table structure for `qs_member_car_record`
-- ----------------------------
DROP TABLE IF EXISTS `qs_member_car_record`;
CREATE TABLE `qs_member_car_record` (
  `record_id` int(11) NOT NULL AUTO_INCREMENT,
  `record_member_id` int(11) NOT NULL DEFAULT '0' COMMENT '会员信息ID',
  `record_car_id` int(11) NOT NULL DEFAULT '0' COMMENT '所停车辆ID',
  `record_car_number_plate` varchar(32) NOT NULL DEFAULT '' COMMENT '车牌号',
  `record_store_id` int(11) NOT NULL DEFAULT '0' COMMENT '停车场ID',
  `record_parking_name` varchar(255) NOT NULL DEFAULT '' COMMENT '停车场名称',
  `record_parking_lng` double(10,6) NOT NULL DEFAULT '0.000000' COMMENT '停车场经度',
  `record_parking_lat` double(10,6) NOT NULL DEFAULT '0.000000' COMMENT '停车场纬度',
  `record_parking_monthly_price` double(10,2) NOT NULL DEFAULT '0.00' COMMENT '包月价格',
  `record_start_time` int(11) NOT NULL DEFAULT '0' COMMENT '记录包月开始时间',
  `record_end_time` int(11) NOT NULL DEFAULT '0' COMMENT '记录包月结束时间',
  `record_addtime` int(11) NOT NULL DEFAULT '0' COMMENT '记录增加时间 ',
  `record_status` int(1) NOT NULL DEFAULT '0' COMMENT '状态0待支付-1废弃1支付成功2过期',
  `record_real_pay_total` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '实际支付金额',
  `record_real_pay_type` varchar(50) NOT NULL DEFAULT '' COMMENT '实际支付方式',
  `record_real_pay_time` int(11) NOT NULL DEFAULT '0' COMMENT '实际支付时间',
  `record_real_pay_id` varchar(50) NOT NULL DEFAULT '' COMMENT '支付相关的支付记录ID',
  PRIMARY KEY (`record_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COMMENT='车辆包月记录 By zhjhqk';

-- ----------------------------
-- Records of qs_member_car_record
-- ----------------------------

-- ----------------------------
-- Table structure for `qs_menu`
-- ----------------------------
DROP TABLE IF EXISTS `qs_menu`;
CREATE TABLE `qs_menu` (
  `menu_id` int(11) NOT NULL AUTO_INCREMENT,
  `menu_name` varchar(500) NOT NULL,
  `menu_fid` int(11) NOT NULL DEFAULT '0' COMMENT '父级ID',
  `menu_addtime` int(11) NOT NULL,
  `menu_sortnum` int(11) NOT NULL DEFAULT '999' COMMENT '排序',
  `menu_active` tinyint(4) NOT NULL DEFAULT '1' COMMENT '正常，或停用',
  `menu_url` varchar(2000) NOT NULL COMMENT '转跳网址',
  `menu_deep` int(11) NOT NULL DEFAULT '0',
  `menu_powerpoint` varchar(2000) NOT NULL COMMENT '权限标识',
  PRIMARY KEY (`menu_id`)
) ENGINE=MyISAM AUTO_INCREMENT=2561 DEFAULT CHARSET=utf8 COMMENT='后台菜单栏目';

-- ----------------------------
-- Records of qs_menu
-- ----------------------------
INSERT INTO `qs_menu` VALUES ('2542', '交易查询', '0', '1512980035', '999', '1', '#', '0', '{\"view\":\"查看\"}');
INSERT INTO `qs_menu` VALUES ('2543', '交易明细', '2542', '1513578345', '999', '1', 'order/index', '1', '{\"view\":\"查看\"}');
INSERT INTO `qs_menu` VALUES ('2485', '管理员管理', '0', '1484232116', '9999', '1', '#', '0', '{\"view\":\"查看\"}');
INSERT INTO `qs_menu` VALUES ('2486', '管理员', '2485', '1484287707', '999', '1', 'admin/index', '1', '{\"view\":\"查看\",\"add\":\"增/改\",\"delete\":\"删\"}');
INSERT INTO `qs_menu` VALUES ('2487', '角色组', '2485', '1484287323', '999', '1', 'admin_role/index', '1', '{\"view\":\"查看\",\"add\":\"增/改\",\"delete\":\"删\"}');
INSERT INTO `qs_menu` VALUES ('2540', '商户', '0', '1505984174', '999', '1', '#', '0', '{\"view\":\"查看\"}');
INSERT INTO `qs_menu` VALUES ('2541', '商户管理', '2540', '1505984193', '999', '1', 'shop/index', '1', '{\"view\":\"查看\",\"add\":\"增/改\",\"delete\":\"删\"}');
INSERT INTO `qs_menu` VALUES ('2545', '代理商', '0', '1514463997', '999', '1', '#', '0', '{\"view\":\"查看\"}');
INSERT INTO `qs_menu` VALUES ('2547', '佣金报表', '2545', '1516852155', '999', '1', 'commissionreport/index', '1', '{\"view\":\"查看\"}');
INSERT INTO `qs_menu` VALUES ('2553', '批量聚合码', '2540', '1519454887', '999', '1', 'short_url/index', '1', '{\"view\":\"查看\",\"add\":\"生成\"}');
INSERT INTO `qs_menu` VALUES ('2546', '代理商管理', '2545', '1514464046', '999', '1', 'agent/index', '1', '{\"view\":\"查看\",\"add\":\"增/改\",\"delete\":\"删\"}');
INSERT INTO `qs_menu` VALUES ('2548', '月报表', '2542', '1516157286', '999', '1', 'orderreport/monthindex', '1', '{\"view\":\"查看\"}');
INSERT INTO `qs_menu` VALUES ('2549', '首页', '0', '1516262935', '99', '1', '#', '0', '{\"view\":\"查看\"}');
INSERT INTO `qs_menu` VALUES ('2550', '默认页', '2549', '1516263453', '999', '1', 'index/index', '1', '{\"view\":\"查看\"}');
INSERT INTO `qs_menu` VALUES ('2551', '基本信息', '2549', '1516777037', '999', '1', 'sysconfig/baseconfig', '1', '{\"view\":\"查看\",\"add\":\"增/改\"}');
INSERT INTO `qs_menu` VALUES ('2552', '佣金数据', '2542', '1516852382', '999', '1', 'commission/index', '1', '{\"view\":\"查看\",\"add\":\"导入\"}');
INSERT INTO `qs_menu` VALUES ('2557', '包月查询', '2542', '1539746351', '999', '1', 'order/parking_baoyue', '1', '{\"view\":\"查看\"}');
INSERT INTO `qs_menu` VALUES ('2560', '进件审批', '2559', '1544166955', '999', '0', 'jinjian/index', '1', '{\"view\":\"查看\",\"add\":\"增/改\",\"delete\":\"删\"}');
INSERT INTO `qs_menu` VALUES ('2559', '进件系统', '0', '1544166883', '999', '0', '#', '0', '{\"view\":\"查看\"}');

-- ----------------------------
-- Table structure for `qs_order`
-- ----------------------------
DROP TABLE IF EXISTS `qs_order`;
CREATE TABLE `qs_order` (
  `order_id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT 'order_id',
  `order_num` char(50) COLLATE utf8_unicode_ci DEFAULT '',
  `order_user_id` int(11) NOT NULL COMMENT '收银账号的ID',
  `order_addtime` int(11) NOT NULL DEFAULT '0' COMMENT '购买时间',
  `order_subject` char(250) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '商品名称',
  `order_total_amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '总价，参与支付',
  `order_status` int(10) NOT NULL DEFAULT '0' COMMENT '支付的最后状态值，以支付宝为准',
  `order_trade_no` char(50) COLLATE utf8_unicode_ci DEFAULT '' COMMENT '第三方支付的订单号',
  `order_channel_id` int(10) DEFAULT NULL COMMENT '1支付方式',
  `order_pay_time` int(11) DEFAULT '0' COMMENT '支付时间',
  `order_pay_realprice` decimal(10,2) DEFAULT '0.00' COMMENT '实际支付金额',
  `order_auth_code` char(50) COLLATE utf8_unicode_ci DEFAULT '' COMMENT '用户付款码(冗余设计，正常应放到支付记录表里)',
  `order_shop_mch_id` char(50) COLLATE utf8_unicode_ci DEFAULT '' COMMENT '子商户号，alipay是shop_id，wxpay是sub_mch_id，都是第三方系统的ID;冗余设计，正常应放到支付记录表里',
  `order_shop_id` int(11) DEFAULT '0' COMMENT '商户的ID，冗余设计',
  `order_store_id` int(11) DEFAULT '0' COMMENT '商店的ID，冗余设计',
  `order_guest_brief` char(255) COLLATE utf8_unicode_ci DEFAULT '' COMMENT '客户备注',
  `order_product_code` char(32) COLLATE utf8_unicode_ci DEFAULT '' COMMENT '支付宝的，销售产品码',
  `order_create_where` char(50) COLLATE utf8_unicode_ci DEFAULT 'h5' COMMENT '订单发起客户端 pc,payapp,guestscan',
  `order_other_sale_order_num` char(50) COLLATE utf8_unicode_ci DEFAULT '' COMMENT '第三方销售系统的订单号，比如超市pos，停车收费订单',
  PRIMARY KEY (`order_id`),
  UNIQUE KEY `order_num` (`order_num`) USING BTREE,
  KEY `order_channel_id` (`order_channel_id`) USING BTREE,
  KEY `order_status` (`order_status`) USING BTREE,
  KEY `order_addtime` (`order_addtime`) USING BTREE,
  KEY `order_shop_id` (`order_shop_id`) USING BTREE,
  KEY `order_store_id` (`order_store_id`) USING BTREE,
  KEY `order_pay_time` (`order_pay_time`) USING BTREE,
  KEY `order_user_id` (`order_user_id`) USING BTREE,
  KEY `order_other_sale_order_num` (`order_other_sale_order_num`),
  KEY `order_total_amount` (`order_total_amount`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ----------------------------
-- Records of qs_order
-- ----------------------------

-- ----------------------------
-- Table structure for `qs_order_detail`
-- ----------------------------
DROP TABLE IF EXISTS `qs_order_detail`;
CREATE TABLE `qs_order_detail` (
  `detail_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `order_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '订单id',
  `goods_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '商品id',
  `goods_name` varchar(30) NOT NULL DEFAULT '' COMMENT '商品名称',
  `goods_num` int(11) unsigned NOT NULL DEFAULT '1' COMMENT '商品数量',
  `goods_price` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '商品单价',
  `total_price` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '商品总价',
  `is_delete` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否删除 0否 1是',
  `create_time` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '添加时间',
  PRIMARY KEY (`detail_id`),
  KEY `order_id` (`order_id`),
  KEY `goods_id` (`goods_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='订单详情表';

-- ----------------------------
-- Records of qs_order_detail
-- ----------------------------

-- ----------------------------
-- Table structure for `qs_order_freeze`
-- ----------------------------
DROP TABLE IF EXISTS `qs_order_freeze`;
CREATE TABLE `qs_order_freeze` (
  `order_freeze_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `order_freeze_num` char(50) DEFAULT '',
  `order_freeze_user_id` int(11) DEFAULT '0' COMMENT '收银员的user_id',
  `order_freeze_addtime` int(11) DEFAULT NULL,
  `order_freeze_subject` char(255) DEFAULT NULL,
  `order_freeze_total_amount` decimal(10,2) DEFAULT NULL,
  `order_freeze_operation_type` char(20) DEFAULT 'FREEZE' COMMENT '支付宝资金操作类型',
  `order_freeze_status` int(11) DEFAULT '0',
  `order_freeze_trade_no` char(50) DEFAULT '',
  `order_freeze_auth_code` char(50) DEFAULT '' COMMENT '用户付款码(冗余设计，正常应放到支付记录表里)',
  `order_freeze_shop_id` int(11) DEFAULT '0' COMMENT '商户的ID，冗余设计',
  `order_freeze_store_id` int(11) DEFAULT '0' COMMENT '商店的ID，冗余设计',
  `order_freeze_app_user_id` char(30) DEFAULT '' COMMENT '收钱人的支付宝user_id',
  `order_freeze_auth_no` char(28) DEFAULT '' COMMENT '支付宝28位授权资金订单号',
  `order_freeze_operation_id` char(50) DEFAULT '' COMMENT '支付宝标识本次资金操作的流水号',
  `order_freeze_pay_time` int(11) DEFAULT NULL,
  `order_freeze_unfree_time` int(11) DEFAULT '0' COMMENT '授权解冻时间',
  PRIMARY KEY (`order_freeze_id`),
  KEY `order_freeze_num` (`order_freeze_num`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='当面资金授权记录';

-- ----------------------------
-- Records of qs_order_freeze
-- ----------------------------

-- ----------------------------
-- Table structure for `qs_order_pay_log`
-- ----------------------------
DROP TABLE IF EXISTS `qs_order_pay_log`;
CREATE TABLE `qs_order_pay_log` (
  `order_pay_log_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `order_pay_log_order_id` bigint(20) DEFAULT NULL,
  `order_pay_log_addtime` int(11) DEFAULT NULL,
  `order_pay_log_status` smallint(10) DEFAULT '0' COMMENT '新状态值',
  `order_pay_log_status_info` varchar(2000) DEFAULT '' COMMENT '状态说明',
  `order_pay_log_returncode` varchar(2000) DEFAULT '' COMMENT '返回的log数据',
  `order_pay_log_from` varchar(255) DEFAULT NULL COMMENT '触发来源(异步，同步)',
  `order_pay_log_user_id` bigint(20) DEFAULT '0' COMMENT '触发者的user_id,比如谁发起的退款',
  `order_pay_log_data1` char(50) DEFAULT '' COMMENT '支付附属数据1',
  `order_pay_log_data2` char(50) DEFAULT '' COMMENT '支付附属数据2',
  PRIMARY KEY (`order_pay_log_id`),
  KEY `order_pay_log_status` (`order_pay_log_status`) USING BTREE,
  KEY `order_pay_log_order_id` (`order_pay_log_order_id`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='订单支付状态变化';

-- ----------------------------
-- Records of qs_order_pay_log
-- ----------------------------

-- ----------------------------
-- Table structure for `qs_order_pay_notify`
-- ----------------------------
DROP TABLE IF EXISTS `qs_order_pay_notify`;
CREATE TABLE `qs_order_pay_notify` (
  `order_pay_notify_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `order_pay_notify_notify_id` varchar(50) DEFAULT '' COMMENT '第三方通知ID',
  `order_pay_notify_from` varchar(255) DEFAULT '' COMMENT '通知是alipay,wechat',
  `order_pay_notify_order_num` varchar(255) DEFAULT '' COMMENT 'sync，同步的通知',
  PRIMARY KEY (`order_pay_notify_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='支付通道webhook异步通知记录(支付宝当面付、H5支付；微信公众号支付会出现在这里)';

-- ----------------------------
-- Records of qs_order_pay_notify
-- ----------------------------

-- ----------------------------
-- Table structure for `qs_parking`
-- ----------------------------
DROP TABLE IF EXISTS `qs_parking`;
CREATE TABLE `qs_parking` (
  `parking_id` int(11) NOT NULL AUTO_INCREMENT,
  `parking_name` char(255) DEFAULT '',
  `parking_uuid` char(100) DEFAULT '' COMMENT '第三方的唯一编号',
  `parking_store_id` int(11) DEFAULT '0' COMMENT '关联”门店ID“',
  `parking_addtime` int(11) DEFAULT '0',
  `parking_ali_parking_id` char(28) DEFAULT '' COMMENT '支付宝返回停车场id',
  `parking_from_compay` varchar(20) DEFAULT 'epapi' COMMENT '停车场系统供应商标示',
  PRIMARY KEY (`parking_id`),
  KEY `parking_store_id` (`parking_store_id`),
  KEY `parking_ali_parking_id` (`parking_ali_parking_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COMMENT='停车场';

-- ----------------------------
-- Records of qs_parking
-- ----------------------------

-- ----------------------------
-- Table structure for `qs_parking_channel`
-- ----------------------------
DROP TABLE IF EXISTS `qs_parking_channel`;
CREATE TABLE `qs_parking_channel` (
  `parking_channel_id` int(11) NOT NULL AUTO_INCREMENT,
  `parking_channel_uuid` char(255) DEFAULT '' COMMENT '进程通道唯一标示（来自第三方系统的）',
  `parking_channel_brief` char(255) DEFAULT '' COMMENT '通道备注',
  `parking_channel_parking_id` int(11) DEFAULT '0' COMMENT '隶属停车场',
  `parking_channel_addtime` int(11) DEFAULT '0' COMMENT '新增时间',
  `_parking_channel_fd` int(11) DEFAULT '0' COMMENT 'TCP 的FD的ID',
  `_parking_channel_system_uuid` char(100) DEFAULT '' COMMENT '收费系统的uuid',
  `parking_channel_user_id` int(11) DEFAULT '0' COMMENT '智慧收银系统的收费员ID',
  `parking_channel_car_number` char(20) DEFAULT '' COMMENT '最后的牌子（出口才有）',
  `parking_channel_car_number_time` int(11) DEFAULT '0' COMMENT '车牌最后更新时间',
  `parking_channel_in_or_out` char(3) DEFAULT '' COMMENT '"in","out"出入口标示',
  PRIMARY KEY (`parking_channel_id`),
  KEY `parking_channel_uuid` (`parking_channel_uuid`(250)),
  KEY `parking_channel_system_uuid` (`_parking_channel_system_uuid`),
  KEY `parking_channel_parking_id` (`parking_channel_parking_id`),
  KEY `parking_channel_user_id` (`parking_channel_user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COMMENT='停车场，通道记录';

-- ----------------------------
-- Records of qs_parking_channel
-- ----------------------------

-- ----------------------------
-- Table structure for `qs_parking_record`
-- ----------------------------
DROP TABLE IF EXISTS `qs_parking_record`;
CREATE TABLE `qs_parking_record` (
  `parking_record_id` int(11) NOT NULL AUTO_INCREMENT,
  `parking_record_car_number` char(20) DEFAULT '' COMMENT '车牌号',
  `parking_record_in_time` int(11) DEFAULT '0' COMMENT '进场时间unix时间戳格式',
  `parking_record_car_type` varchar(255) DEFAULT '' COMMENT '车型',
  `parking_record_in_type` varchar(100) DEFAULT '' COMMENT '进场类型',
  `parking_record_order_id` varchar(50) DEFAULT '' COMMENT '订单记录号(车辆在停车场停车唯一订单编号)',
  `parking_record_empty_plot` smallint(10) DEFAULT '0' COMMENT '空闲车位数',
  `parking_record_in_channel_id` int(11) DEFAULT '0' COMMENT '进场通道id',
  `parking_record_in_remark` varchar(2000) DEFAULT '' COMMENT '备注',
  `parking_record_addtime` int(11) DEFAULT NULL COMMENT '记录新增时间',
  `parking_record_out_time` int(11) DEFAULT '0' COMMENT '出场时间',
  `parking_record_total` decimal(10,2) DEFAULT '0.00' COMMENT '停车费用',
  `parking_record_duration` smallint(10) DEFAULT '0' COMMENT '停车时长，分',
  `parking_record_out_type` varchar(100) DEFAULT '' COMMENT '出场类型',
  `parking_record_pay_type` varchar(50) DEFAULT '' COMMENT '请求支付类型',
  `parking_record_auth_code` varchar(50) DEFAULT '' COMMENT '微信、支付宝付款码',
  `parking_record_out_channel_id` varchar(50) DEFAULT '',
  `parking_record_out_user_id` varchar(50) DEFAULT '' COMMENT '入场收费员id',
  `parking_record_out_remark` varchar(2000) DEFAULT '' COMMENT '出门备注',
  `parking_record_real_pay_total` decimal(10,2) DEFAULT '0.00' COMMENT '实际支付金额',
  `parking_record_real_pay_type` varchar(50) DEFAULT '' COMMENT '实际支付方式',
  `parking_record_real_pay_time` int(11) DEFAULT '0' COMMENT '实际支付时间',
  `parking_record_real_pay_id` varchar(50) DEFAULT '' COMMENT '支付相关的支付记录ID',
  `parking_record_reduce_amount` decimal(10,2) DEFAULT '0.00' COMMENT '优惠金额',
  `parking_record_reduce_remark` varchar(255) DEFAULT '' COMMENT '优惠说明',
  `parking_record_pay_state` smallint(5) DEFAULT '0' COMMENT '支付状态',
  `parking_record_get_price_last_time` int(11) DEFAULT '0' COMMENT '获取到价格的最后时间',
  `parking_plate_type` varchar(255) DEFAULT '' COMMENT '临时车、月租车',
  `parking_record_parking_id` int(11) DEFAULT '0' COMMENT '隶属停车场的ID',
  PRIMARY KEY (`parking_record_id`),
  KEY `parking_record_order_id` (`parking_record_order_id`),
  KEY `parking_record_car_number` (`parking_record_car_number`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COMMENT='停车进出场记录表';

-- ----------------------------
-- Records of qs_parking_record
-- ----------------------------

-- ----------------------------
-- Table structure for `qs_refund`
-- ----------------------------
DROP TABLE IF EXISTS `qs_refund`;
CREATE TABLE `qs_refund` (
  `refund_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `refund_order_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '订单id',
  `refund_part_num` varchar(20) NOT NULL DEFAULT '' COMMENT '部分退款的唯一码',
  `refund_type` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '退款类型 0全额退款 1部分退款 ',
  `refund_amount` decimal(10,2) unsigned NOT NULL DEFAULT '0.01' COMMENT '退款金额',
  `refund_time` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '退款时间',
  PRIMARY KEY (`refund_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COMMENT='退款记录表';

-- ----------------------------
-- Records of qs_refund
-- ----------------------------
INSERT INTO `qs_refund` VALUES ('1', '2222222', '', '0', '0.01', '0');

-- ----------------------------
-- Table structure for `qs_reward`
-- ----------------------------
DROP TABLE IF EXISTS `qs_reward`;
CREATE TABLE `qs_reward` (
  `reward_id` int(11) NOT NULL AUTO_INCREMENT,
  `reward_is_default` tinyint(1) DEFAULT '0' COMMENT '设置默认金额',
  `reward_store_id` bigint(20) DEFAULT '0' COMMENT '门店ID',
  `reward_cash` decimal(10,2) DEFAULT '0.00' COMMENT '打赏金额',
  PRIMARY KEY (`reward_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='打赏金额方案表';

-- ----------------------------
-- Records of qs_reward
-- ----------------------------

-- ----------------------------
-- Table structure for `qs_scheduled_tasks`
-- ----------------------------
DROP TABLE IF EXISTS `qs_scheduled_tasks`;
CREATE TABLE `qs_scheduled_tasks` (
  `scheduled_tasks_id` int(11) NOT NULL AUTO_INCREMENT,
  `scheduled_tasks_title` varchar(255) DEFAULT NULL COMMENT '任务名称，描述',
  `scheduled_tasks_start_time` int(11) DEFAULT NULL COMMENT '启动时间',
  `scheduled_tasks_end_time` int(11) DEFAULT '0' COMMENT '结束时间/与执行次数',
  `scheduled_tasks_last_time` int(11) DEFAULT '0' COMMENT '上次执行时间',
  `scheduled_tasks_time_interval` varchar(2000) DEFAULT '[0]' COMMENT '执行间隔，单位秒,可多个',
  `scheduled_tasks_status` varchar(100) DEFAULT 'realy' COMMENT '状态,realy:准备好，doing:执行中，end任务已经完结',
  `scheduled_tasks_name` varchar(255) DEFAULT '' COMMENT '任务模块名称，唯一识别名称',
  `scheduled_tasks_param` varchar(2000) DEFAULT '' COMMENT '测试JOSN/array格式',
  `scheduled_tasks_times_limit` int(11) DEFAULT '0' COMMENT '执行次数限制',
  `scheduled_tasks_times_this` int(11) DEFAULT '0' COMMENT '当前执行了第几次',
  PRIMARY KEY (`scheduled_tasks_id`),
  KEY `scheduled_tasks_end_time` (`scheduled_tasks_end_time`),
  KEY `scheduled_tasks_status` (`scheduled_tasks_status`),
  KEY `scheduled_tasks_times_limit` (`scheduled_tasks_times_limit`),
  KEY `scheduled_tasks_times_this` (`scheduled_tasks_times_this`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='计划任务';

-- ----------------------------
-- Records of qs_scheduled_tasks
-- ----------------------------

-- ----------------------------
-- Table structure for `qs_shop`
-- ----------------------------
DROP TABLE IF EXISTS `qs_shop`;
CREATE TABLE `qs_shop` (
  `shop_id` int(11) NOT NULL AUTO_INCREMENT,
  `shop_alipay_seller_id` char(16) DEFAULT '' COMMENT '支付宝的商户PID(user id),2088开头',
  `shop_wxpay_sub_mch_id` char(32) DEFAULT '' COMMENT '微信支付分配的子商户号',
  `shop_wxpay_sub_appid` char(32) DEFAULT '' COMMENT '微信分配的子商户公众账号ID,非必填',
  `shop_name` char(255) DEFAULT '' COMMENT '店名',
  `shop_addtime` int(11) DEFAULT '0',
  `shop_active` tinyint(1) DEFAULT '1' COMMENT '关闭，开放',
  `shop_address` char(255) DEFAULT '' COMMENT '详细地址',
  `shop_agent_id` int(11) DEFAULT '0' COMMENT '隶属代理商ID（隶属管理员）',
  `shop_sortnum` int(11) DEFAULT '0',
  `shop_head_picture` char(255) DEFAULT '' COMMENT '门头照片',
  `shop_business_license` char(255) DEFAULT '' COMMENT '营业执照号码',
  `shop_business_license_picture` char(255) DEFAULT '' COMMENT '营业执照照片',
  `shop_master_name` char(50) DEFAULT '' COMMENT '商户法人姓名',
  `shop_master_sfz` char(50) DEFAULT '' COMMENT '商户法人身份证号',
  `shop_master_mobile` char(50) DEFAULT '' COMMENT '商户手机号',
  `shop_tel` char(255) DEFAULT '' COMMENT '店铺电话',
  `shop_content` char(255) DEFAULT '' COMMENT '商家简介',
  `shop_id_token` char(32) DEFAULT NULL COMMENT '商户的唯一识别码',
  `shop_alipay_account` char(50) DEFAULT '' COMMENT '收款支付宝账号',
  `shop_alipay_app_auth_token` char(50) DEFAULT '' COMMENT '支付宝商户授权令牌',
  `shop_alipay_auth_app_id` char(50) DEFAULT '' COMMENT '授权商户的AppId（如果有服务窗，则为服务窗的AppId）',
  `shop_alipay_app_auth_token_auto_pay` char(50) DEFAULT '' COMMENT '支付宝商户授权令牌(无感停车_生活号)',
  `shop_alipay_auth_app_id_auto_pay` char(50) DEFAULT '' COMMENT '授权商户的AppId（无感支付)',
  PRIMARY KEY (`shop_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='商户/个体/公司';

-- ----------------------------
-- Records of qs_shop
-- ----------------------------

-- ----------------------------
-- Table structure for `qs_shop_attr`
-- ----------------------------
DROP TABLE IF EXISTS `qs_shop_attr`;
CREATE TABLE `qs_shop_attr` (
  `shop_attr_id` int(11) NOT NULL AUTO_INCREMENT,
  `shop_attr_shop_id` int(11) DEFAULT NULL,
  `shop_attr_app_auth_token_expires_in` int(11) DEFAULT '0' COMMENT '交换令牌的有效期，单位秒，换算成天的话为365天',
  `shop_attr_app_auth_token_re_expires_in` int(11) DEFAULT '0' COMMENT '刷新令牌有效期，单位秒，换算成天的话为372天',
  `shop_attr_app_refresh_token` char(50) DEFAULT '' COMMENT '刷新令牌后，我们会保证老的app_auth_token从刷新开始10分钟内可继续使用，请及时替换为最新token',
  `shop_attr_app_token_update_time` int(11) DEFAULT '0' COMMENT '最后更新的时间',
  `shop_attr_alipay_rates` decimal(12,6) DEFAULT '0.000000' COMMENT '支付宝的返的费率',
  `shop_attr_wxpay_rates` decimal(12,6) DEFAULT '0.000000' COMMENT '微信的返的的费率',
  `shop_attr_app_auth_token_expires_in_auto_pay` int(11) DEFAULT '0',
  `shop_attr_app_auth_token_re_expires_in_auto_pay` int(11) DEFAULT '0',
  `shop_attr_app_refresh_token_auto_pay` char(50) DEFAULT '0',
  `shop_attr_app_token_update_time_auto_pay` int(11) DEFAULT '0',
  PRIMARY KEY (`shop_attr_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='qs_shop 的扩展表，不常用的属性都放在这';

-- ----------------------------
-- Records of qs_shop_attr
-- ----------------------------

-- ----------------------------
-- Table structure for `qs_shop_data`
-- ----------------------------
DROP TABLE IF EXISTS `qs_shop_data`;
CREATE TABLE `qs_shop_data` (
  `shop_data_id` int(11) NOT NULL AUTO_INCREMENT,
  `shop_data_master_id_images` varchar(2000) DEFAULT '',
  `shop_data_shop_id` int(11) DEFAULT NULL,
  `shop_data_store_head_image` varchar(255) DEFAULT '' COMMENT '1张店铺招牌',
  `shop_data_store_images` varchar(2000) DEFAULT '' COMMENT '3张店铺内景照片',
  `shop_data_bank_number` varchar(32) DEFAULT '' COMMENT '银行卡',
  `shop_data_bank_name` varchar(255) DEFAULT '' COMMENT '银行开户行',
  `shop_data_other_images` text COMMENT '其它图片',
  `shop_data_other_info` text COMMENT '填写备注',
  `shop_data_status` char(3) DEFAULT '0' COMMENT '状态',
  `shop_data_status_info` varchar(2000) DEFAULT '' COMMENT '状态说明',
  `shop_data_status_change_time` int(11) DEFAULT NULL COMMENT '状态变更时间',
  `shop_alipay_yz_url` varchar(100) DEFAULT '' COMMENT '支付宝，商户同意的链接',
  `shop_wxpay_yz_url` varchar(100) DEFAULT '' COMMENT '微信商户、银行验证的链接',
  PRIMARY KEY (`shop_data_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='业务员收集的店铺的数据，每个商户一一对应';

-- ----------------------------
-- Records of qs_shop_data
-- ----------------------------

-- ----------------------------
-- Table structure for `qs_short_url`
-- ----------------------------
DROP TABLE IF EXISTS `qs_short_url`;
CREATE TABLE `qs_short_url` (
  `short_url_id` bigint(11) NOT NULL AUTO_INCREMENT,
  `short_url_key` char(6) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT '',
  `short_url_action` char(20) DEFAULT '' COMMENT '短链标示',
  `short_url_data` varchar(2000) DEFAULT '' COMMENT '绑定的数据,json格式',
  `short_url_active_addtime` int(11) DEFAULT '0' COMMENT '0未激活，其它是激活的时间',
  `short_url_agent_id` int(11) DEFAULT '0' COMMENT '谁生成的ID(代理商的ID)',
  `short_url_addtime` int(11) DEFAULT NULL COMMENT '生成日期/批次',
  PRIMARY KEY (`short_url_id`),
  UNIQUE KEY `short_url_key` (`short_url_key`),
  KEY `short_url_addtime` (`short_url_addtime`),
  KEY `short_url_active_addtime` (`short_url_active_addtime`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='短url地址,活码';

-- ----------------------------
-- Records of qs_short_url
-- ----------------------------

-- ----------------------------
-- Table structure for `qs_store`
-- ----------------------------
DROP TABLE IF EXISTS `qs_store`;
CREATE TABLE `qs_store` (
  `store_id` int(11) NOT NULL AUTO_INCREMENT,
  `store_name` char(255) DEFAULT '' COMMENT '小店名称，显示在前端',
  `store_addtime` int(11) DEFAULT '0',
  `store_updatetime` int(11) DEFAULT '0',
  `store_shop_id` int(11) NOT NULL COMMENT '商户ID',
  `store_address` char(255) DEFAULT '' COMMENT '门店地址',
  `store_open_reward` tinyint(1) NOT NULL DEFAULT '0' COMMENT '开启门店员工的打赏功能',
  `store_mobile` char(50) NOT NULL DEFAULT '' COMMENT '门店电话',
  `store_pay_after_ad` text,
  `store_pay_after_ad_active` tinyint(1) DEFAULT '0',
  `store_open_funds_authorized` tinyint(1) NOT NULL DEFAULT '0' COMMENT '开启预授权功能',
  `store_parking_poiid` char(50) DEFAULT '' COMMENT '高德地图唯一标识',
  `store_parking_compatibility_mode` tinyint(1) DEFAULT '0' COMMENT '停车场兼容模式（魔小盒）',
  `store_is_park` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否停车场0否1是 by zhjhqk',
  `store_temporary_parking_fee` double(10,2) NOT NULL DEFAULT '0.00' COMMENT '临时停车费用单位每小时 By zhjhqk',
  `store_monthly_fee` double(10,2) NOT NULL DEFAULT '0.00' COMMENT '停车包月费用 By zhjhqk',
  `store_parking_lng` decimal(10,6) NOT NULL DEFAULT '0.000000' COMMENT '停车场经度 By zhjhqk',
  `store_parking_lat` decimal(10,6) NOT NULL DEFAULT '0.000000' COMMENT '停车场纬度 By zhjhqk',
  `store_parking_content` varchar(255) NOT NULL DEFAULT '' COMMENT '停车场说明',
  PRIMARY KEY (`store_id`),
  KEY `store_shop_id` (`store_shop_id`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='店铺,商铺';

-- ----------------------------
-- Records of qs_store
-- ----------------------------

-- ----------------------------
-- Table structure for `qs_sysconfig`
-- ----------------------------
DROP TABLE IF EXISTS `qs_sysconfig`;
CREATE TABLE `qs_sysconfig` (
  `sysconfig` text NOT NULL COMMENT 'json，所有的配置表',
  `id` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COMMENT='配置字段';

-- ----------------------------
-- Records of qs_sysconfig
-- ----------------------------
INSERT INTO `qs_sysconfig` VALUES ('{\"telphone\":\"0571-5659000\",\"contactaddress\":\"\\u7965\\u56ed\\u8def\",\"contact_address\":\"\\u7965\\u56ed\\u8def3\"}', '1');

-- ----------------------------
-- Table structure for `qs_user`
-- ----------------------------
DROP TABLE IF EXISTS `qs_user`;
CREATE TABLE `qs_user` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '自动ID',
  `user_username` char(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '用户登陆名',
  `user_nicename` char(20) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '妮称，仅作显示，不可用于登陆，限10汉字字符',
  `user_mobile` bigint(20) DEFAULT '0' COMMENT '手机号（登陆使用）',
  `user_password` char(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '密码,md5(md5&md5)',
  `user_sex` tinyint(1) NOT NULL DEFAULT '1' COMMENT '性别，1：男，0女',
  `user_last_login_time` int(11) NOT NULL DEFAULT '0' COMMENT '最后登陆时间',
  `user_last_login_ip` char(128) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '最后登陆Ip',
  `user_active` tinyint(1) NOT NULL DEFAULT '1' COMMENT '账号状态,0禁用,1正常',
  `user_headimg` char(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '用户头像',
  `user_email` char(50) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `user_token` char(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '登陆生成的',
  `user_token_seller` char(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '商家端的token',
  `user_pushtoken` char(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '极光等token',
  `user_pushtoken_seller` char(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '极光等token_商家端的',
  `user_realname` char(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `user_tel` char(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `user_paypassword` char(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '支付密码',
  `user_refund_auth` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1拥有退款的权限,0没有权限',
  `user_refund_password` char(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '退款密码',
  `user_addtime` int(11) NOT NULL DEFAULT '0' COMMENT '用户注册时间',
  `user_mobilestatus` tinyint(1) NOT NULL DEFAULT '0' COMMENT '手机认证状态',
  `user_last_login_lat` double(12,6) NOT NULL DEFAULT '0.000000',
  `user_last_login_lon` double(12,6) NOT NULL DEFAULT '0.000000',
  `user_store_id` int(11) NOT NULL DEFAULT '0' COMMENT '归属店铺的ID',
  `user_role` tinyint(1) NOT NULL DEFAULT '2' COMMENT '角色（0 商户的老板，能查看所有下属店铺, 1 门店店长，查看其它收银员的数据 2收银员',
  `user_play_reward` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否开启打赏',
  `user_play_reward_list` char(255) COLLATE utf8_unicode_ci DEFAULT '' COMMENT '打赏金额列表',
  PRIMARY KEY (`user_id`),
  KEY `user_username` (`user_username`) USING BTREE,
  KEY `user_mobile` (`user_mobile`) USING BTREE,
  KEY `user_password` (`user_password`) USING BTREE,
  KEY `user_store_id` (`user_store_id`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='所有用户（一账通）';

-- ----------------------------
-- Records of qs_user
-- ----------------------------
