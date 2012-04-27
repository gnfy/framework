<?php
/**
 *********************************
 * 凤凰网用户相关方法
 *
 * Last modify: 2011-08-02 10:21:48
 *********************************
 */

/**
 * 验证ifeng用户登录状态
 *
 * @return  boolean     若登录状态正常，则返回true, 否则返回false
 */
function checkIfengUserStatus() {
    $a      = 16;
    $t      = $_COOKIE['sid'];
    $url    = 'http://uibi.ifeng.com/uibi/gate.jsp?a='.$a.'&t='.base64_encode($t).'&s='.strtoupper(md5('a='.$a.'t='.$t.'testtest'));
    $ret    = file_get_contents($url);
    if (stripos($ret, '<ret>0</ret>') !== false) {
        return true;
    } else {
        return false;
    }
}

/**
 * 查询ifeng用户登录信息
 *
 * @param   int     $type   用户信息类型，1 => 基本信息（默认）, 2 => 所有信息
 * @return  mix     若用户登录状态正常，则返回用户信息；否则返回false
 */
function getIfengUserInfo($type = 1) {
    $a = $type == 1 ? 5 : 6;
    if (checkIfengUserStatus()) {
        $t      = $_COOKIE['sid'];
        $url    = 'http://uibi.ifeng.com/uibi/gate.jsp?a='.$a.'&t='.base64_encode($t).'&s='.strtoupper(md5('a='.$a.'t='.$t.'testtest'));
        $ret    = file_get_contents($url);
        if (stripos($ret, '<ret>0</ret>') !== false) {
            include_once 'SofeeXmlParser.php';
            $xml_parser = new SofeeXmlParser();
			$xml_parser->parseFile($ret);
			$xml_tree = $xml_parser->getTree();
            if ($a == 5) {
                $data = array (
                    'id'        => $xml_tree['uibi']['user']['id']['value'],
                    'username'  => strtolower($xml_tree['uibi']['user']['n']['value']),
                    'email'     => strtolower($xml_tree['uibi']['user']['m']['value']),
                    'name'      => empty($xml_tree['uibi']['user']['rname']['value']) ? strtolower($xml_tree['uibi']['user']['n']['value']) : strtolower($xml_tree['uibi']['user']['rname']['value'])
                );
            } else {
                $data = $xml_tree['uibi']['user'];
            }
            $xml_tree = null;
            return $data;
        } else {
            return false;
        }
    } else {
        return false;
    }
}

/**
 * ifeng用户退出
 *
 * @return  boolean     若成功则返回true,否则返回false
 */
function IfengUserLoginOut() {
    $c_name = 'sid';
    $a      = 6;
    $t      = $_COOKIE[$c_name];
    $url    = 'http://uibi.ifeng.com/uibi/gate.jsp?a='.$a.'&t='.base64_encode($t).'&s='.strtoupper(md5('a='.$a.'t='.$t.'testtest'));
    $ret    = file_get_contents($url);
    if (stripos($ret, '<ret>0</ret>') !== false) {
        setcookie($c_name, '', time() - 3600, '/', '.ifeng.com');
        return true;
    } else {
        return false;
    }
}

/**
 * ifeng用户登录
 *
 * @param   array   $param  登录的信息
 *                  $param['uid']   => 用户名/手机/电子邮件
 *                  $param['pwd']   => 密码
 *                  $param['p']     => 系统识别, 默认 评论(0X0200)
 * @return  boolean 若成功，则返回true,否则,返回false
 */
function IfengUserLogin($param = array()) {
    $a      = 2;
    $uid    = $param['uid'];
    $pwd    = strtoupper(md5($param['pwd']));
    $l      = empty($_COOKIE['ilocid']) ? 0 : $_COOKIE['ilocid'];
    $p      = empty($param['p']) ? 512 : $param['p'];
    $baseurl= 'http://uibi.ifeng.com/uibi/gate.jsp?a='.$a.'&k='.base64_encode($pwd).'&l='.$l.'&p='.$p;
    include_once 'SofeeXmlParser.php';
    $xml_parser = new SofeeXmlParser();
    // 作为用户名来登录
    $sign   = strtoupper(md5('a='.$a.'k='.$pwd.'l='.$l.'n='.$uid.'p='.$p.'testtest'));
    $url    = $baseurl.'&s='.$sign.'&n='.base64_encode($uid);
    $ret    = file_get_contents($url);
    if (stripos($ret, '<ret>0</ret>') !== false) {
        $xml_parser->parseFile($ret);
        $xml_tree   = $xml_parser->getTree();
        $sid        = urldecode(base64_decode($xml_tree['uibi']['token']['value']));
        // 两天内有效
        setcookie('sid', $sid, time() + 172800, '/', '.ifeng.com');
        return true;
    }
    // 作为电子邮件来登录
    $sign   = strtoupper(md5('a='.$a.'k='.$pwd.'l='.$l.'m='.$uid.'p='.$p.'testtest'));
    $url    = $baseurl.'&s='.$sign.'&m='.base64_encode($uid);
    $ret    = file_get_contents($url);
    if (stripos($ret, '<ret>0</ret>') !== false) {
        $xml_parser->parseFile($ret);
        $xml_tree   = $xml_parser->getTree();
        $sid        = urldecode(base64_decode($xml_tree['uibi']['token']['value']));
        // 两天内有效
        setcookie('sid', $sid, time() + 172800, '/', '.ifeng.com');
        return true;
    }
    // 作为手机号码来登录
    $sign   = strtoupper(md5('a='.$a.'k='.$pwd.'l='.$l.'mp='.$uid.'p='.$p.'testtest'));
    $url    = $baseurl.'&s='.$sign.'&mp='.base64_encode($uid);
    $ret    = file_get_contents($url);
    if (stripos($ret, '<ret>0</ret>') !== false) {
        $xml_parser->parseFile($ret);
        $xml_tree   = $xml_parser->getTree();
        $sid        = urldecode(base64_decode($xml_tree['uibi']['token']['value']));
        // 两天内有效
        setcookie('sid', $sid, time() + 172800, '/', '.ifeng.com');
        return true;
    }
    return false;
}
