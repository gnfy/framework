<?php
/**
 ************************************
 * 路由控制文件
 ************************************
 */
$query = pathinfo($_SERVER['QUERY_STRING']);    // 获得参数相关信息

if (stripos($query['filename'], '=') != false) {

    $file   = empty($_GET['apps'])   ? 'index.php' :'/ajax/'.trim($_GET['apps']).'.php';
    $action = empty($_GET['action']) ? 'index' : trim($_GET['action']);

} else {

    $ctrl  = empty ($query) ? '' : explode('-', $query['filename']);      // 获得控制器相关信息
    $query = null;
    $file = empty ($ctrl[0]) ? 'index.php' : $ctrl[0].'.php'; // 文件
    $action = empty($ctrl[1]) ? 'index' : $ctrl[1]; // 控制器
    unset($ctrl[0]);
    unset($ctrl[1]);
    $param = array_values($ctrl); // 参数
    $ctrl = null;

}

if ( file_exists('apps/'.$file) ) {
    include_once 'apps/'.$file;
} else {
    include_once 'view/notfound.php';
}
