<?php
/**
 * ********************************************
 * Description   : 导入master库中csv文件(utf8编码的文件)
 * Filename      : import_master_csv.php
 * Create time   : 2013-01-05 14:47:34
 * Last modified : 2013-01-06 16:11:13
 * License       : MIT, GPL
 * ********************************************
 */
// 加载配置
include_once dirname(__FILE__).'/../application/conf/common.config.php';

// 数据库类
include_once APP_DIR.'/lib/cls_DB.php';

// 加载通用方法
include_once APP_DIR.'/lib/function.inc.php';

$file_name  = $argv[1];
$node_name  = $argv[2];
$node       = intval($argv[3]);
$node_name  = empty($node_name) ? 'master' : $node_name;

if (file_exists($file_name)) {
    $path_info  = pathinfo($file_name);
    $table      = $path_info['filename'];

    $db         = cls_DB::getDB($node_name, $node);
    $db->sql    = 'desc '.$table;
    $arr        = $db->getAll();

    if ($arr) {
        $fields = array();
        foreach ($arr AS $v) {
            $fields[] = $v['Field'];
        }
        if ($fields) {
            $handle = fopen($file_name, 'r');
            $num    = 0;
            $id     = 0;
            while ($data    = fgets($handle)) {
                if ($num == 1) {
                    $db->sql    = 'TRUNCATE TABLE '.$table;
                    // 清队数据表
                    $db->del();
                    $db->sql    = null;
                    $db->table  = $table;
                }
                if ($num > 0) {
                    $data   = preg_replace_callback('/"(.*?)"/s', 'replaceStr', $data);
                    $arr    = preg_split('/,( ?)/', $data);
                    $param  = array ();
                    foreach ($fields AS $k => $v) {
                        $param[$v]  = str_replace('(@)', ',', trim($arr[$k]));
                    }
                    $id = $db->insert($param);
                }
                $num++;
            }
            $ret    = $table.' insert '.$id.' rows';
        } else {
            $ret    = 'database table no Field';
        }
    } else {
        $ret    = $table. ' not found';
    }
} else {
    $ret = $file_name.' not found.';
}
echo $ret."\r\n";

/**
 * 正则替换
 *
 * @param   string      $str    要替换的字符串
 * @return  string
 */
function replaceStr($str) {
    $arr = str_replace(',', '(@)', $str);
    return $arr[1];
}
