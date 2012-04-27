<?php
/**
 * 登录验证
 */
session_start();
if ( empty($_SESSION['uid']) ) {
    $_SESSION['last_url'] = empty ($_SERVER['QUERY_STRING']) ? 'index-index.html' : $_SERVER['QUERY_STRING'];
    include_once('login.php');
}
