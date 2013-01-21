<?php
/**
 * ********************************************
 * Description   : 导入文件到数据库(utf8编码的文件)
 * Filename      : import_file.php
 * Create time   : 2013-01-05 14:47:34
 * Last modified : 2013-01-08 15:19:51
 * License       : MIT, GPL
 * ********************************************
 */
// 加载配置
include_once dirname(__FILE__).'/../application/conf/common.config.php';

// 数据库类
include_once APP_DIR.'/lib/cls_DB.php';

// 加载通用方法
include_once APP_DIR.'/lib/function.inc.php';

$dir        = dirname(__FILE__);

$file_name  = $dir.'/'.$argv[1];
$node_name  = $argv[2];
$node       = intval($argv[3]);
$node_name  = empty($node_name) ? 'master' : $node_name;

if (file_exists($file_name)) {
    $path_info  = pathinfo($file_name);
    $table      = $path_info['filename'];

    $db         = cls_DB::getDB($node_name, $node);
    $db->sql    = 'TRUNCATE TABLE '.$table;
    // 清队数据表
    $db->del();
    $db->sql    = 'LOAD DATA INFILE \''.$file_name.'\' REPLACE INTO TABLE `'.$table.'` FIELDS TERMINATED BY \',\' ENCLOSED BY \'"\' ESCAPED BY \'"\' LINES TERMINATED BY \''.'\r\n'.'\' IGNORE 1 LINES';
    $db->getOne();
    $db->sql    = 'SELECT count(*) AS sum FROM '.$table;
    $row        = $db->getOne();
    $ret        = $table.' has '.$row['sum'].' rows';
} else {
    $ret = $file_name.' not found.';
}
echo $ret."\r\n";
