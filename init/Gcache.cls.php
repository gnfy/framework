<?php
/**
 * ********************************************
 * Description   : 模板缓存引擎类
 * Filename      : Gcache.cls.php
 * Create time   : 2011-09-30 17:50:08
 * Last modified : 2011-10-01 11:39:03
 * License       : MIT, GPL
 * ********************************************
 */
class Gcache {
    // 缓存时间
    public $cache_time = 0;

    /**
     * @功能：显示方法
     * @参数：$f => 要显示的模板文件, $__display_data => 要显示的数据, $__c_time 缓存时间
     * @返回：无
     */
    function display($f, $__display_data = '', $__c_time = 0) {

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
        if ( $this->cache_time > 0 && $__c_time >= 0) {
            $cache_file = $this->getCacheFile( $temp );
            if ( $this->isCaching($temp, $__c_time) == false ) {
                ob_start();
                include_once($temp);
                $html = ob_get_contents();
                ob_end_flush();        
                $this->makeCacheFile($html, $cache_file);
            } else {
                include_once $cache_file;
            }
        } else {
            include_once $temp;
        }
        exit;// 显示时，断点
    }

    /**
     * 生成缓存文件
     *
     * @param   string      $html       要缓存的文件内容
     * @param   string      $file_path  缓存的目录
     */
    function makeCacheFile( $html, $file_path ) {
        $f = pathinfo($file_path);
        $this->makeDir( $f['dirname'] );
        file_put_contents($file_path, $html, LOCK_EX);
    }

    /**
     * 获得缓存文件路径
     *
     * @param   string      $file_path  模板文件
     * @param   string      $cache_path 缓存目录(默认为空)
     */
    function getCacheFile($file_path, $cache_path = '') {
        $f          = pathinfo($file_path);
        $hash_file  = str_replace('-', '', crc32($_SERVER['SCRIPT_URI'])).$f['filename'].'.html';
        if ( empty($cache_path) ) {
            $cache_dir  = $f['dirname'].'/cache/';
            $mid_path   = substr($hash_file, 0, 2);
            $file_path  = $cache_dir.$mid_path.'/';
        } else {
            $cache_dir  = strrpos( $cache_path, '/' ) === false ? $cache_path.'/' : $cache_path;
            $file_path  = $cache_dir;
        }
        return $file_path.$hash_file;
    }

    /**
     * 检查缓存是否有效
     *
     * @param   string      $file_path  模板文件
     * @param   int         $l_time     缓存有效时间(默认0)
     * @param   string      $cache_path 缓存目录(默认为空)
     * @return  boolean                 若缓存有效，则返回true,否则false
     */
    function isCaching($file_path, $l_time = 0, $cache_path = '') {
        $cache_time = $l_time > 0 ? $l_time : $this->cache_time;
        $cache_file = $this->getCacheFile($file_path, $cache_path);
        $f_time     = file_exists( $cache_file ) ? filemtime( $cache_file ) : 0;
        return time() - $f_time < $cache_time;
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

}
