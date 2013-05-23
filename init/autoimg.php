<?php
/**
 * ********************************************
 * Description   : 图片自动生成程序
 * Filename      : autoimg.php
 * Create time   : 2013-04-08 17:30:01
 * Last modified : 2013-04-09 00:49:29
 * License       : MIT, GPL
 * ********************************************
 */

$img        = $_SERVER['REQUEST_URI'];
$scriptpath = $_SERVER['DOCUMENT_ROOT'];
$toimg      = $scriptpath.$img;

if (preg_match("/([^\.]+\.(png|jpg|jpeg|gif))_([\d]*)x?([\d]*)\.\\2/i", $toimg, $m )) {
    thumbnail($m);
} else {
    die('图片不合法');
}

/**
 * 生成缩略图
 *
 * @param   array   $imgarr 源图片信息数组
 */
function thumbnail($imgarr) {
    ob_start ();//开始截获输出流
    $srcimg     = $imgarr[1];
    $toimg      = $imgarr[0];
    if (!$imageinfos = getimagesize ( $srcimg )) return false;
    if ($imageinfos [2] == 1) {
        $im = imagecreatefromgif ( $srcimg );
    } elseif ($imageinfos [2] == 2) {
        $im = imagecreatefromjpeg ( $srcimg );
    } elseif ($imageinfos [2] == 3) {
        $im = imagecreatefrompng ( $srcimg );
    }

    if (isset ( $im )) {
        $ext        = $imgarr[2];
        $param      = array (
            'width' => $imgarr[3],
            'height'=> $imgarr[4],
            '_w'    => $imageinfos[0],
            '_h'    => $imageinfos[1]
        );

        $size   = getThumbSize($param);

        $dst_img = imagecreatetruecolor($size[0], $size[1]);

        imagesavealpha($dst_img, true);
        $trans_colour = imagecolorallocatealpha($dst_img, 0, 0, 0, 127);
        imagefill( $dst_img, 0, 0, $trans_colour );

        imagecopyresampled( $dst_img, $im, 0, 0, 0, 0, $size[0], $size[1], $imageinfos [0], $imageinfos [1] );

        header ( 'content-type:'.$imageinfos['mime'] );
        if ($imageinfos[2] == 1) {
            imagegif($dst_img);
        } elseif ($imageinfos[2] == 2) {
            imagejpeg ( $dst_img, null, 90 );//输出文件流，90--压缩质量，100表示最高质量。
        } else {
            imagepng($dst_img);
        }

        @imagedestroy ( $im );
        @imagedestroy ( $dst_img );
    } else {
        echo @file_get_contents ( $srcimg );
    }
    $content = ob_get_contents ();//获取输出流
    ob_end_flush ();//输出流到网页,保证第一次请求也有图片数据放回
    @file_put_contents ( $toimg, $content );//保存文件
}

/**
 * 指定一边，获得别一边的缩放尺寸
 *
 * @param   array   $param  相关参数 
 *                          $param['width']  => 指定缩放的宽度
 *                          $param['height'] => 指定缩放的高度
 * @return  array           缩放后的尺寸
 */
function getThumbSize($param) {
    $w      = empty($param['width'])  ? 0 : intval($param['width']);
    $h      = empty($param['height']) ? 0 : intval($param['height']);
    $size[0]= empty($param['_w'])     ? 0 : intval($param['_w']);
    $size[1]= empty($param['_h'])     ? 0 : intval($param['_h']);
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
