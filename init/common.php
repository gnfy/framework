<?php
/**
 * ********************************************
 * Description   : 公用文件
 * Filename      : common.php
 * Create time   : 2012-04-27 10:22:35
 * Last modified : 2012-04-27 10:22:35
 * License       : MIT, GPL
 * ********************************************
 */
Header("Content-Type:text/html;charset=utf-8");
//set_include_path(get_include_path() . PATH_SEPARATOR . 'init/'); // 包含路径

$g_baseurl = $baseurl = 'http://www.ifeng.com/';

$g_common_title = $common_title = '凤凰网';

// 设置报错
//error_reporting(E_ALL);
ini_set('error_reporting', E_ALL);

include_once 'mysql.config.php';    // mysql 配置文件
include_once 'function.inc.php';    // 常用方法
$last_dir = getLastDir(dirname(__file__));           // 获得上级目录
include_once $last_dir.'/model/cls_mysql.php';   // mysql类
$g_db = $db = new cls_mysql();

// 模板缓存引擎
include_once 'Gcache.cls.php';
$Gc = new Gcache;
$Gc->cache_time = 7200;
