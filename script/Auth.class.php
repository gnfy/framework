<?php
/**
 * 用户认证类
 * 走ifeng sso认证
 */

class Auth {
	
	const NO_NAME = "用户名不得为空";
	const ILLEGAL = "非法操作";
	const NOT_EXISTS = "用户名或密码错误";
	
	var $sso;
	/**
	 * 构造函数
	 * 实例化了SSO对象
	 *
	 */
	function __construct(){
		if(!defined(IFENG_SSO_SYSTEM_BBS)) define(IFENG_SSO_SYSTEM_BBS,4);
		$this->sso = IFengSystem::getInstance('IFengSso', IFENG_SSO_SYSTEM_BBS);
	}
	
	
	
	/**
	 * 进行登录过程
	 * 1.检查用户名
	 * 2.进行认证，通过后分2种情况处理：
	 * 		a.不存在本地用户 则尝试创建
	 * 		b.存在本地用户 则装载用户
	 *
	 * @param string $username
	 * @param string $password
	 * @return 返回自定义常量
	 */
	public  function processLogin($username,$password,$remember){
		global $gUser;
		#用户名为空
		if ( '' == $username ) {
			return self::NO_NAME;
		}
		#已登录
		if($gUser->getName() === $username){
			return true;
		}
		#认证通过则实例化用户
		if($this->authenticate($username,$password)){
			#根据用户名新建了一个USER实例
			$u = User::newFromName($username);
			if( !($u instanceof User ) ) {
				global $log;
				$log->error(__METHOD__."Can't get User instance \n");
				return self::ILLEGAL ;
			}
			#不存在本地用户则尝试新建，存在则获取实例
			if ( 0 == $u->getID() ) {
				if(!$this->attemptAutoCreate( $u ))return self::ILLEGAL;
			} else {
				$u->load();
			}
			$gUser = $u;
			#在确认登录之前更新登录ip和时间
			$gUser->refresh();
			$expire = 0;
			if($remember=='yes')$expire = time()+60*60*24*30;
			$gUser->setCookies($expire);//写入cookie
			return true;
		}else {
			return self::NOT_EXISTS ;
		}
	}
	
	/**
	 * 通过SSO进行认证
	 *
	 * @param 用户名 $username
	 * @param 密码 $password
	 * @return 如果通过验证返回true
	 */
	private  function authenticate( $username, $password ) {
		# 调用sso进行认证
		return $this->sso->login($username,'',$password);
	}
	/**
	 * 试图创建本地用户
	 *
	 * @param User $user
	 * @return unknown
	 */
	private function attemptAutoCreate( $user ){
		if(!$this->userExists( $user->getName()))return false;
		$user = $this->initUser($user);
		if(($user instanceof User)&&$user->getID()!== 0)return true;
		return false;
	}
	/**
	 * 判断用户名是否存在
	 *
	 * @param unknown_type $name
	 * @return unknown
	 */
	function userExists($name){
		return $this->sso->isUserExists($name);
	}
	/**
	 * 本函数必须在使用了sso->login成功之后才能进行
	 *
	 * @param unknown_type $user
	 */
	private function initUser($user){
		#检查是否已登录
		if($this->sso->verify()){
			$arr = $this->sso->UserInfo();
			$arr['user']['n'] = strtolower($arr['user']['n']);
			$u = $user->insertNew($arr['user']);
			return $u;
		}
		return false;
	}
	/**
	 * 当cookie端已登录并通过sso认证，则创建user对象
	 *
	 * @param User $user
	 * @return 通过认证返回true;
	 */
	function verify($user){
		if($this->sso->verify()){
			//认证通过
			$ret = $this->sso->UserInfo();
			$ssoName = $ret['user']['n'];
			$uid = $user->idFromName($ssoName);
			#本地无用户 则创建
			if ( !$uid ) {
				$user =  $this->initUser( $user );
			} else {
				$user = User::newFromId($uid);
				$user->load();
			}
			if(!$user) return false;
			if($user->locked == 1){
				$user->logout();
				alert_page("您的用户已被锁定，请联系我们","http://baike.ifeng.com/");
				return false;
			}
			$user->setCookies();//写入cookie
			$user->refresh();
			return true;
		}else{
			return false;
		}
	}
	/**
	 * 同步登出
	 *
	 */
	function SynLogout(){
		$this->sso->logout();
		setcookie('sid',"",time()-3600,'/',"ifeng.com");
	}
}