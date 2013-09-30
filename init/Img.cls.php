<?php
/**
 * ********************************************
 * Description   : 图片操作类
 * Filename      : Img.class.php
 * Create time   : 2013-09-29 14:32:09
 * Last modified : 2013-09-29 14:49:05
 * License       : BSD, GPL
 * ********************************************
 */
class Img {

    private static $_instance = null;

    /**
     * 获得实例化连接
     */
    public static function getInstance(){
        if (self::$_instance === null){
            self::$_instance = new Imagick();
        }   
        return self::$_instance;
    }

    /**
     * 初始化函数
     */
    public function __construct() {
        return self::getInstance();
    }

    /**
     * 验证图片
     *
     * @param   array   $param  参数, 其中 level => 匹配程度. 0 => 图片内容相同, 1-5 => 相似, 5 - 10 一般相似
     * @return  bool
     */
    public function check($param) {
        $files  = $param['files'];
        if ( count($files) >= 2 ) {
            $file_hash_0    = $this->hash($files[0]);
            $file_hash_1    = $this->hash($files[1]);
            $len    = strlen($file_hash_0);
            if ( $len != strlen($file_hash_1) ) {
                return false;
            } else {
                $level  = isset($param['level']) ? intval($param['level']) : 0;
                $param  = array (
                    'val_1' => $file_hash_0,
                    'val_2' => $file_hash_1,
                );
                $num    = $this->getHashDiffNum($param);
                return $num > $level ? false : true;
            }
        }
        return false;
    }

    /**
     * 找出hash值有多少不同
     *
     * @param   array   $param  参数
     * @return  int
     */
    public function getHashDiffNum($param) {
        $val_1  = $param['val_1'];
        $val_2  = $param['val_2'];
        $len_1  = strlen($val_1);
        $len_2  = strlen($val_2);
        $len    = $len_1 > $len_2 ? $len_1 : $len_2;
        $diff   = 0;
        for ($i = 0; $i < $len; $i++) {
            if ($i < $len_1 && $i < $len_2) {
                if ( $val_1{$i} != $val_2{$i}) {
                    $diff++;
                }
            } else {
                $diff++;
            }
        }
        return $diff;
    }

    /**
     * 获得图片的hash值
     *
     * @param   string  $file  参数
     * @return  string
     */
    public function hash($file) {
        $w  = $h = 8;
        $param  = array (
            'file'          => $file,
            'size'          => $w.'x'.$h,
            'color_space'   => imagick::COLORSPACE_GRAY
        );
        $this->thumbImage($param);
        $color_arr  = array();
        $sum        = 0;
        $img    = self::getInstance();
        for ($i = 0; $i < $w; $i++) {
            for ($j = 0; $j < $h; $j++) {
                $pixel  = $img->getImagePixelColor($i, $j);
                $c_arr  = $pixel->getColor();
                $color_arr[$i][$j]  = $c_arr['g'];
                $sum   += $c_arr['g'];
            }
        }
        $avg    = intval($sum / ($w * $h));
        $ret    = '';
        for ($i = 0; $i < $w; $i++) {
            for ($j = 0; $j < $h; $j++) {
                $ret    .= $color_arr[$i][$j] >= $avg ? 1 : 0;
            }
        }
        return $ret;
    }

    /**
     * 设置图片颜色类型
     *
     * @param   int     $val    颜色的值
     * @return  bool
     */
    public function setImageColorSpace($val) {
        /**
         * 参数说明
         * 
         * 0 - UndefinedColorspace    
         * 1 - RGBColorspace    
         * 2 - GRAYColorspace    
         * 3 - TransparentColorspace    
         * 4 - OHTAColorspace    
         * 5 - LABColorspace    
         * 6 - XYZColorspace    
         * 7 - YCbCrColorspace    
         * 8 - YCCColorspace    
         * 9 - YIQColorspace    
         * 10 - YPbPrColorspace    
         * 11 - YUVColorspace    
         * 12 - CMYKColorspace    
         * 13 - sRGBColorspace    
         * 14 - HSBColorspace    
         * 15 - HSLColorspace    
         * 16 - HWBColorspace
         */
        return self::getInstance()->setImageColorSpace($val);
    }

    /**
     * 生成缩略图
     *
     * @param   array   $param  参数
     * @return  bool
     */
    public function thumbImage($param) {
        self::getInstance()->readImage($param['file']);
        if (isset($param['color_space'])) {
            $this->setImageColorSpace($param['color_space']);
        }
        $size   = explode('x', $param['size']);
        $width  = isset($size[0]) ? intval($size[0]) : 0;
        $height = isset($size[1]) ? intval($size[1]) : 0;
        self::getInstance()->thumbnailImage($width, $height);
    }

    /**
     * 读取图片
     *
     * @param   string  $file   要读取的图片路径
     * @return  bool
     */
    public function readImage($file) {
        if ( file_exists($file) ) {
            self::getInstance()->readImage($file);
            return true;
        } else {
            return false;
        }
    }

    /**
     * 写入图片
     *
     * @param   string  $file   要写入图片的路径
     * @return  bool
     */
    public function writeImage($file) {
        self::getInstance()->writeImage($file);
    }

    /**
     * 浏览器输出图片
     */
    public function browserImage() {
        header("Content-type: image/".strtolower(self::getInstance()->format));
        echo self::getInstance();
    }

    /**
     * 清除图片资源
     *
     * @return bool
     */
    public function destroy() {
        self::getInstance()->clear();
        self::getInstance()->destory();
        return true;
    }
}
