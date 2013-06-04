<?php
/**
 * ********************************************
 * Description   : 图片自动生成程序
 * Filename      : img_imagick.php
 * Create time   : 2013-04-08 17:30:01
 * Last modified : 2013-06-04 11:24:15
 * License       : MIT, GPL
 * ********************************************
 */

$img        = $_SERVER['REQUEST_URI'];
$scriptpath = $_SERVER['DOCUMENT_ROOT'];
$toimg      = $scriptpath.$img;

if (preg_match("/(.*\/(.*?)_(r|t|s|508|254)\.(png|jpg|jpeg|gif)/i", $toimg, $m )) {
    thumbnail($m);
} else {
    die('file not allow');
}

/**
 * 生成缩略图
 *
 * @param   array   $imgarr 源图片信息数组
 */
function thumbnail($imgarr) {
    $ext        = $imgarr[4];
    $srcimg     = str_replace('pic/smallcase', 'pic/case', $imgarr[1].'.'.$ext);
    if (!file_exists($srcimg)) {
        die('file not found');
    }
    $toimg      = $imgarr[0];
    $to_path    = pathinfo($toimg);

    makeDir($to_path['dirname']);

    $type       = $imgarr[3];

    if ($type == 'r') {
        $param  = 192;
    } else if ($type == 't') {
        $param  = 640;
    } elseif ($type == 's') {
        $param  = '230x230';
    } elseif($type > 0) {
        $param  = $type;
    }
    $cmd    = 'convert -unsharp 0x2.5 -resize '.$param.' '.$srcimg.' '.$toimg;
    exec($cmd);

    $new_img_path   = file_exists( $toimg ) ? $toimg : $srcimg;

    header ( 'content-type:image/'.$ext );

    echo file_get_contents($new_img_path);
}

/**
 * @功能：创建目录
 * @参数：$path => 要创建的目录, $mod => 指定权限
 * @返回：若创建成功返回true,否则返回false或直接跳出
 */

function makeDir($path, $mod = 0777) {
    if (stripos(PHP_OS, 'win') === false) {
        $cmd  = 'mkdir -p '.$path;
    } else {
        $path = str_replace('/', '\\', $path);
        $cmd  = 'mkdir '.$path;
    }   
    exec($cmd);
    return true;
}
