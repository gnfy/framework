<?php
/**
 * ********************************************
 * Description   : 常用方法
 * Filename      : function.inc.php
 * Create time   : 2012-04-27 10:14:35
 * Last modified : 2012-04-27 10:14:35
 * License       : MIT, GPL
 * ********************************************
 */

/**
 * @功能：显示方法
 * @参数：$f => 要显示的模板文件, $__display_data => 要显示的数据
 * @返回：无
 */
function display($f, $__display_data = '') {
    
    if ( !file_exists($f) ) die($f.'文件不存在');
    
    $p = pathinfo($f);
    
    if ($p['extension'] == 'php') { // 是否是PHP文件
        $temp = $f;
    } else {
       if ($p['dirname'] != '.') {
           $temp = $p['dirname'].'/'.$p['filename'].'.php';
       } else {
          $temp = $p['filename'].'.php';
       }
       copy($f,$temp); // 复制一个文件,用于显示
    }
    if ( is_array($__display_data) ) { // 给模板赋值,变量设定为数组的下标
        foreach ( $__display_data as $k => $v ) {
            if ( $k == '__display_data' ) {
                exit('__display_data 是保留变量名称');
            }
            $$k = $v;
        }
        $v = null;
        $__display_data = null;
    }
    include_once($temp);
    exit;// 显示时，断点
}

/**
 * @功能：文件上传
 * @参数：$f => 文件上传参数
 *        $f['savetype']    => 上传类型 1->本地(默认), 2->远程;
 *        $f['file']        => 上传文件的相关属性
 *        $f['allow']       => 允许上传文件类型
 *        $f['filename']    => 设定文件上传名称
 *        $f['filesize']    => 允许文件上传大小
 *        $f['basepath']    => 文件上传基本路径
 *        $f['dirpath']     => 文件上传路径
 * @返回：若上传成功，则返回上传的路径(字符串),否则返回带错误信息的数组
 */
function saveFile($f) {
    
    $file     = pathinfo(strtolower($f['file']['name'])); // 获得文件上传的相关信息
    
    $savetype = empty ($f['savetype']) ? 1 : $f['savetype'];
    $filesize = empty ($f['filesize']) ? 6 * 1024 * 1024 : $f['filesize']; // 文件大小默认6M
    $allow    = empty ($f['allow'])    ? 'jpeg,jpg,rar,txt,gif,doc,docx,xls,xlsx,bmp,png,pdf,gz' : $f['allow']; // 允许上传的文件
    $basepath = empty ($f['basepath']) ? '.' : $f['basepath'];  // 基本路径
    $dirpath  = empty ($f['dirpath'])  ? date('Y/m/d') : $f['dirpath'];   // 上传路径
    $dirpath  = $basepath.'/'.$dirpath;
    $filename = empty ($f['filename']) ? getUniqueName($dirpath, $file['extension']) : $f['filename']; // 保存的文件名称
    if ( $savetype == 1 ) {
        
        $f['file']['size'] > $filesize ? $error[] = '上传的文件过大' : '';

    } else {
        $img_code = file_get_contents($f['file']['name']);
        strlen($img_code) > 0 ? '' : $error[] = '获取不到文件';
        strlen($img_code) > $filesize ? $error[] = '上传的文件过大' : '';
    }
    strpos($allow, $file['extension']) > -1 ? '' : $error[] = '上传的文件类型不允许';
    
    if ( is_array($error)) return $error;
    
    makeDir($dirpath); // 建立目录
    
    $return_path = $dirpath.'/'.$filename; // 返回路径
    if ( $savetype == 1 ) {
        if (!move_uploaded_file($f['file']['tmp_name'], $return_path)) $error[] = '文件移动失败';
    } else {
       if (!file_put_contents($return_path, $img_code, LOCK_EX)) $error[] = '文件保存失败';
    }

    return is_array(@$error) ? $error : $return_path;
}

/**
 * 获得当前目录下不重名的文件名
 *
 * @param string $dir 目录
 * @param string $ext 文件后缀
 * @return string 文件名
 */
function getUniqueName($dir, $ext) {
    $filename = '';
    while ( empty($filename) ) {
        $filename = time().getRandCode().'.'.$ext;
        if ( file_exists($dir.'/'.$filename) ) {
            $filename = '';
        }
    }
    return $filename;
}

/**
 * @功能：获得随机码
 * @参数：$length => 随机码长度
 * @返回：返回指定长度的随机码
 */
function getRandCode( $length = 4 ) {
    $baseCode = '0123456789abcdef';
    $str = '';
    for ($i = 0; $i < $length; $i++ ) {
        
        $p = rand(0, strlen($baseCode)-1);
        $str .= $baseCode{$p};
        
    }
    return $str;
}

/**
 * @功能：创建目录
 * @参数：$path => 要创建的目录, $mod => 指定权限
 * @返回：若创建成功返回true,否则返回false或直接跳出
 */

function makeDir($path, $mod = 0777) {
    
    if ($path == '.' || $path == '..') return false;
    $path = str_replace('\\', '/', $path);
    $dir = '';
    foreach ( explode('/', $path) as $v ) {
        $dir .= $v.'/';
        if ($v == '.' || $v == '..') continue;
        if ( !file_exists($dir) ) {
            if ( !@mkdir($dir) ) {
                exit('创建'.$dir.'失败');
            }
            @chmod($dir, $mod);
        }
    }
    return true;
}

/**
 * @功能：获得图片缩放尺寸($size[0]/$size[1] = $w/$h)
 * @参数：$picurl => 图片地址, $w => 要缩放的宽, $h => 要缩放的高
 * @返回：数组，$data[0] => 缩放后的宽, $data[1] => 缩放后的高
 */
function getZoomSize( $picurl, $w = 200, $h=200 ) {
    
    if (!$size = getimagesize($picurl)) return false;
    if ( $size[0] <= $w && $size[1] <= $h ) { // 当图片的尺寸小于缩放时，返回原图尺寸
        
        return $data = $size;
    
    }
    
    if ( $size[0] * $h / $w > $size[1] ) { // 尺寸超标
        
        $data[0] = $h * $size[0] / $size[1];
        $data[1] = $h;
    
    } else {
    
        $data[0] = $w;
        $data[1] = $w * $size[1] / $size[0];
        
    }
    
    return $data;
    
}

/**
 * 指定一边，获得别一边的缩放尺寸
 *
 * @param   array   $param  相关参数 
 *                          $param['picurl'] => 图片地址
 *                          $param['width']  => 指定缩放的宽度
 *                          $param['height'] => 指定缩放的高度
 * @return  array           缩放后的尺寸
 */
function getThumbSize($param) {
    $w = empty($param['width'])  ? 0 : intval($param['width']);
    $h = empty($param['height']) ? 0 : intval($param['height']);
    if (!$size = getimagesize($param['picurl'])) return false;
    if ($w > 0 && $h == 0) {
        if ($w < $size[0]) {
            $data[0] = $w;
            $data[1] = $w * $size[1] / $size[0];
        } else {
            $data = $size;
        }
    } else if ($w == 0 && $h > 0) {
        if ($h < $size[1]) {
            $data[0] = $h * $size[0] / $size[1];
            $data[1] = $h;
        } else {
            $data = $size;
        }
    } else {
        if ( $size[0] <= $w && $size[1] <= $h ) { // 当图片的尺寸小于缩放时，返回原图尺寸
            return $data = $size;
        }

        if ( $size[0] * $h / $w > $size[1] ) { // 尺寸超标
            $data[0] = $h * $size[0] / $size[1];
            $data[1] = $h;
        } else {
            $data[0] = $w;
            $data[1] = $w * $size[1] / $size[0];
        }
    }
    $data[0] = floor($data[0]);
    $data[1] = floor($data[1]);
    return $data;
}

/**
  * 获得字符首拼
  *
  * @param   string  $str    要获得首拼的字符
  * @return  string  返回首拼
  */
function getFirstChar($str) {
    $asc=ord(substr($str,0,1));
    // 非中文
    if ($asc<160) {
        if ($asc>=48 && $asc<=57){
            return chr($asc); //数字
        } elseif ( $asc>=65 && $asc<=90 ){
            return chr($asc);   // A–Z
        } elseif ($asc>=97 && $asc<=122){
            return chr($asc-32); // a–z
        } else {
            return '#'; //其他
        }
    } else {
        $fchar = $str{0};
        if( $fchar >= ord('a') and $fchar <= ord('Z') ) return strtoupper($str{0});
        $s = iconv('UTF-8','gb2312', $str);
        $asc = ord( $s{0} ) * 256 + ord( $s{1} ) - 65536;
        if( $asc >= -20319 and $asc <= -20284 ) return 'A';
        if( $asc >= -20283 and $asc <= -19776 ) return 'B';
        if( $asc >= -19775 and $asc <= -19219 ) return 'C';
        if( $asc >= -19218 and $asc <= -18711 ) return 'D';
        if( $asc >= -18710 and $asc <= -18527 ) return 'E';
        if( $asc >= -18526 and $asc <= -18240 ) return 'F';
        if( $asc >= -18239 and $asc <= -17923 ) return 'G';
        if( $asc >= -17922 and $asc <= -17418 ) return 'I';
        if( $asc >= -17417 and $asc <= -16475 ) return 'J';
        if( $asc >= -16474 and $asc <= -16213 ) return 'K';
        if( $asc >= -16212 and $asc <= -15641 ) return 'L';
        if( $asc >= -15640 and $asc <= -15166 ) return 'M';
        if( $asc >= -15165 and $asc <= -14923 ) return 'N';
        if( $asc >= -14922 and $asc <= -14915 ) return 'O';
        if( $asc >= -14914 and $asc <= -14631 ) return 'P';
        if( $asc >= -14630 and $asc <= -14150 ) return 'Q';
        if( $asc >= -14149 and $asc <= -14091 ) return 'R';
        if( $asc >= -14090 and $asc <= -13319 ) return 'S';
        if( $asc >= -13318 and $asc <= -12839 ) return 'T';
        if( $asc >= -12838 and $asc <= -12557 ) return 'W';
        if( $asc >= -12556 and $asc <= -11848 ) return 'X';
        if( $asc >= -11847 and $asc <= -11056 ) return 'Y';
        if( $asc >= -11055 and $asc <= -10247 ) return 'Z'; 
        return null;
    }
}

/**
 * 获得访问者IP
 */
function getOnlineIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } else if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else if (!empty($_SERVER['REMOTE_ADDR'])){
        $ip = $_SERVER['REMOTE_ADDR'];
    } else {
        $ip = '0.0.0.0';
    }
    return $ip;
}

/**
 * 获得分页数据
 *
 * @param   array   $param  页码信息
 *                  $param['offset']    => 一页显示多少条记录
 *                  $param['all_items'] => 总记录数
 *                  $param['page']      => 当面页码
 *                  $param['show_page'] => 翻页的时候显示多少页
 * @return  array   返回带有分页信息的数组
 */
function pagination($param = array()) {
    $param['all_page']  = ceil($param['all_items'] / $param['offset']);
    $param['page']      = $param['page'] > $param['all_page'] ? $param['all_page'] : $param['page'];
    $param['page']      = $param['page'] > 0 ? $param['page'] : 1;
    $start              = ($param['page'] - 1) * $param['offset'];
    $param['start']     = $start > $param['all_items'] ? $param['all_items'] : $start;
    $last_page          = $param['page'] - 1;
    $next_page          = $param['page'] + 1;
    $param['last_page'] = $last_page > 0 ? $last_page : 1;
    $param['next_page'] = $next_page > $param['all_page'] ? $param['all_page'] : $next_page;
    if ($param['show_page'] > 1 && $param['all_page'] > 1) {
        // 翻页的时候，显示多少可选择的页码
        $show_page  = $param['show_page'] > $param['all_page'] ? $param['all_page'] : $param['show_page'];
        // 去掉一页，因为当前这一页应该显示在页面中间
        $show_page  = $show_page - 1;
        // 前半段数字
        $mid_1      = ceil($show_page/2);
        // 后半段数字
        $mid_2      = $show_page - $mid_1;

        $start_show_page    = $param['page'] - $mid_1;
        $end_show_page      = $param['page'] + $mid_2;
        
        $param['start_show_page']   = $start_show_page > 1 ? $start_show_page : 1;
        $param['end_show_page']     = $end_show_page > $param['all_page'] ?  $param['all_page'] : ($end_show_page < $show_page + 1 ? $show_page + 1 : $end_show_page);
        // 距离第一页的距离
        $param['first_page_distance']   = $param['start_show_page'] - 1;
        // 距离最后一页的距离
        $param['last_page_distance']    = $param['all_page'] - $param['end_show_page'];

        $param['start_show_page']   = $param['last_page_distance'] == 0 ? $param['start_show_page'] - abs($param['all_page'] - $end_show_page) : $param['start_show_page'];
        
        $param['start_show_page']   = $param['start_show_page'] > 1 ? $param['start_show_page'] : 1;
        
        // 显示页面的极限值，用于smarty的循环
        $param['limit_show_page']   = $param['end_show_page'] + 1;
        
    }
    return $param;
}

/**
 * 读取目录下的所有文件
 *
 * @param   string  $dir    目录名称
 * @param   int     $dep    深度
 * @return  array           目录下的所有文件
 */
function getDirFileTree($dir, $dep = 0) {
    if (is_dir($dir)) {
        $dirStrLen = strlen($dir) - 1;
        if (strrpos($dir, '/') != $dirStrLen) {
            $dir .= '/';
        }
        if ($dh = opendir($dir)) {
            while (($file = readdir($dh)) !== false){
                $ft = filetype($dir.$file);
                if ($ft == 'dir' && $file != '.svn' && $file != '.' && $file != '..') {
                    $dirTree[] = getDirFileTree($dir.$file, $dep+1);
                } else if ($ft == 'file') {
                    $dirTree[] = $dir.$file;
                }
            }
            $dirTree['dirname'] = $dir;
            $dirTree['depth']   = $dep;
            closedir($dh);
            return $dirTree;
        }
    }
}

/**
 * 获得上级目录
 *
 * @param   string      $param      目录
 * @return  string                  上一级目录
 */
function getLastDir($param) {
    $path       = empty($param) ? getcwd() : trim($param);
    $path       = str_replace('\\', '/', $path);
    $dir_arr    = explode('/', $path);
    array_pop($dir_arr);
    return implode(DIRECTORY_SEPARATOR, $dir_arr);
}

/**
 * 获得memcache缓存
 *
 * @param   string      $mem_key    memcache的key
 * @return  mix                     memcache里面存放的值
 */
function getCacheFromMem($mem_key) {
    global $memcache;
    return $memcache->get($mem_key);
}

/**
 * 设置memcache缓存
 *
 * @param   string      $mem_key    memcache的key
 * @param   mix         $value      要存储的值
 * @param   int         $time       缓存生效的时间
 * @return  bool                    设置成功则返回true，否则返回false
 */
function setCacheToMem($mem_key, $value, $time) {
    global $memcache;
    $time = $time > 0 ? $time : MEM_TIME;
    if ($memcache->set($mem_key, $value, false, $time)) {
        return true;
    } else {
        return false;
    }
}

/**
 * 获得ttserver缓存
 *
 * @param   string      $tt_key     ttserver的key
 * @return  mix                     ttserver里面存放的值
 */
function getCacheFromTT($tt_key) {
    global $ttserver;
    return $ttserver->get($tt_key);
}

/**
 * 设置ttserver缓存
 *
 * @param   string      $tt_key     ttserver的key
 * @param   mix         $value      要存储的值
 * @return  bool                    设置成功则返回true,否则返回false
 */
function setCacheToTT($tt_key, $value) {
    global $ttserver;
    if ($ttserver->set($tt_key, $value)) {
        return true;
    } else {
        return false;
    }
}

/**
 * 字符串转换成二进制
 *
 * 将字符串先转换成ASCII码对应的数字，之后再用ASCII的数字转换成二进制
 * ASCII码最大的是254,转换成二进制最大是8位
 *
 * @param   string      $data   字符串源数据
 *                              $data = fread(fopen($img, 'rb'), filesize($img));
 * @return  bin                 二进制数据
 */
function strToBin ($data) { 
    $len = strlen($data); 
    $ret = '';
    for ($i = 0; $i < $len; $i++) {
        $val    = substr($data, $i, 1);
        $asc    = ord($val);
        $ret   .= sprintf("%08b", $asc);
    }
    return $ret; 
}

/**
 * 二进制转换字符串
 *
 * @param   bin         $data   二进制源数据
 * @return  string              字符串数据
 */
function binToStr($data){
    $len    = strlen($data);
    $ret    = '';
    for ($i = 0; $i < $len; $i += 8) {
        $val    = substr($data, $i, 8);
        $asc    = bindec($val);
        $ret   .= chr($asc);
    }
    return $ret;
}
