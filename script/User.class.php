<?php
!defined('IN_BK') && exit('Access Denied');
/**
 * 用户类
 */
define( 'BK_USER_VERSION', 2 );
class User {
	 //------------------------   
    //   
    //  数据   
    //   
    //------------------------   
	/**
	 * 需要存入memcache的数据
	 */
	static private $mCacheVars = array(
		// user table
		'id',
		'name',
		'point',
		'rank',
		'new_num',
		'edit_num',
		'reg_time',
		'reg_ip',
		'last_ip',
		'last_time',
		'token',
		'word',
		'locked',
		// 需要换算得到
		'avatar'
	);
	/**成员变量 */
	private $id,$name,$rank,$token;
	public 	$reg_time,$reg_ip,$last_ip,$last_time,
			$new_num,$edit_num,$point,
			$word,$avatar,$locked,$lock_time;
	
	/**用于载入user对象*/
	private $mDataLoaded;
	private $mFrom;
	
	/** user表	 */
	private $dbname;
	/***/

	
	
	
	
	
	
    //------------------------   
    //   
    //  简单初始化一个user对象   
    //   
    //------------------------   
	/**
	 * 针对匿名用户 使用轻量级的构造函数
	 * 对其他种类的用户使用User::newFrom*工厂函数来构造
	 * @see newFromName()
	 * @see newFromId()
	 * @see newFromSession()
	 */
	public function User() {
		global $db_prefix;
		$this->dbname = $db_prefix."user";
		$this->clearInstanceCache( 'defaults' );
	}	
	/**
	 * 使用cookie或session中的数据创建一个新的用户对象 .
	 * 如果登录证书无效，则创建的是一个匿名的对象
	 *
	 * @return user对象
	 */
	static function newFromSession() {
		$user = new User;
		$user->mFrom = 'session';
		return $user;
	}
	/**
	 * 使用id创建一个新的用户对象 .
	 *
	 * @return user对象
	 */
	static function newFromId($id) {
		if($id){
			$user = new User;
			$user->id = intval($id);
			$user->mFrom = 'id';
			return $user;
		}
		return false;
	}
	/**
	 * 根据用户名创建一个用户对象
	 *
	 * @param string $name
	 */
	static function newFromName($name){
		if ($name) {
			$u = new User;
			$u->name = (string)$name;
			$u->mFrom = 'name';
			return $u;
		}
		return false;
	}
	/**
	 * 清理缓存
	 * @param string $reloadFrom  载入用户的方法
	 */
	private function clearInstanceCache( $reloadFrom = false ) {
		$this->id = null;
		$this->name = null;
		$this->banned = 0;

		if ( $reloadFrom ) {
			$this->mDataLoaded = false;
			$this->mFrom = $reloadFrom;
		}
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
    //------------------------   
    //   
    //  获取对象数据   
    //   
    //------------------------   
	/**
	 * 获取用户名
	 * 1.当mDataLoaded为true时进行load
	 * 2.当mFrom不为name时，进行load
	 */
	public function getName() {
		if ( !$this->mDataLoaded && $this->mFrom == 'name' ) {
			return $this->name;
		} else {
			$this->load();
			return $this->name;
		}
	}
	/**
	 * 根据用户名得到id
	 *
	 * @param string $name
	 * @return unknown
	 */
	static function idFromName( $name ) {
		global $db,$db_prefix;
		$name = mysql_escape_string(trim($name));
		#调用本方法时并没有实例化对象，因此不能使用$this->dbname
		$dbname = $db_prefix.'user';
		$s = $db->getOne("SELECT id FROM $dbname WHERE name='$name' ");
		if ( $s === false ) {
			return 0;
		} else {
			return (int)$s;
		}
	}
	/**
	 * 获取用户头像
	 *
	 * @return 头像地址
	 */
	private function getAvatar(){
		if( $this->id === null || !$this->mDataLoaded ) {
			$this->load();
		}
		if($this->id == 0)return false;
		return $this->avatar = "http://app.finance.ifeng.com/sso_avatar.php?username=$this->name";
		 
	}
	/**
	 * 获取用户id
	 * 如果id不为null则直接返回id
	 * 否则load之后返回id
	 * @return unknown
	 */
	public function getId() {
		if( $this->id === null || !$this->mDataLoaded ) {
			$this->load();
		}
		return $this->id;
	}
	
	/**
	 * 获取用户等级信息
	 * 对当前等级进行一次检查，查询等级表
	 * 如果与等级规则不一致则更新用户记录
	 *
	 * @return array eg ('id'=>0,'rank'=>'列兵')
	 */
	public function getRank($ret=''){
		if( $this->id === null || !$this->mDataLoaded ) {
			$this->load();
		}
		if($this->id == 0) return false;
		$rank = $this->Rankname($this->point);
		if(!$rank||!is_array($rank)){
			global $log;
			$log->error(__METHOD__.":Unrecognised Rank user_id:$this->id point:$this->point");
			return false;
		}
		if($rank['id'] !== $this->rank){
			global $db;
			$this->rank = $rank['id'];
			$arr = array('rank');
			$this->update($arr);
		}
		if(empty($ret))return $rank;
		return $rank[$ret];
	}
	
	
	
	
    //------------------------   
    //   
    //  装载用户对象 
    //   
    //------------------------   
	/**用mFrom给出的源为对象装载user表 数据
	 *  mFrom的形式 ，例如session
	 * 加载用户信息, 根据 mFrom 字段 ; 
	 */
	public function load() {
		if ( $this->mDataLoaded ) {
			return;
		}
		$this->mDataLoaded = true;
		switch ( $this->mFrom ) {
			case 'defaults':
				$this->loadDefaults();
				break;
			case 'name':
				$this->id = self::idFromName( $this->name );
				if ( !$this->id ) {
					# 不存在的用户则使用一个占位对象
					$this->loadDefaults( $this->name );
				} else {
					$this->loadFromId();
				}
				break;
			case 'id':
				$this->loadFromId();
				break;
			case 'session':
				$this->loadFromSession();
				break;
			default:
				global $log;
				$log->error( "Unrecognised value for User->mFrom: \"{$this->mFrom}\"" );
				exit("ILLEGAL User");
		}
	}
	
	private function loadFromSession() {
		#当ifeng域下的Cookie['sid]不存在时，可以认为ifeng端已经登出，作为匿名用户
		if(!$_COOKIE['sid']){
//			$this->logout();
			$this->loadDefaults();
			return true;
		}
		#存在token的记录，则试图本地认证，否则从sso登录
		if( $_COOKIE['BK_User_token']){
			#先根据sid创建用户对象
			$c_name = strtolower(urldecode(substr($_COOKIE["sid"], 32)));
			$this->clearInstanceCache('id');
			$this->id = $this->idFromName($c_name);
			$this->load();
			if($this->id == 0) 	{
				$this->logout();
				return false;	
			}
			if($this->locked == 1 && $this->lock_time>getTime()) 	{
				$this->logout();
				alert_page("您的用户已被冻结至".$this->lock_time,"http://baike.ifeng.com/");
				return false;	
			}elseif ($this->locked == 1){
				//到期解冻
				if($this->unblock()==false){
					return false;
				}
			}
			if($this->token !== $_COOKIE['BK_User_token']){
				#比对失败 匿名用户
				$this->logout();
				$this->loadDefaults();
				return false;
			}else{
				#从cookie登录距离上次登录时间超过1小时 则刷新一次登录ip和登录时间;
				if(time()-strtotime($this->last_time) >= 3600)$this->refresh();
				return true;
			}
		}else{
			#不存在Cookie，则从sso认证
			global $gAuth;
			return $gAuth->verify($this);
		}
	}
	/**
	 * 匿名用户载入
	 *
	 * @param  $name
	 */
	private function loadDefaults( $name = false ) {
		$this->id = 0;
		$this->name = $name;
	}
	/**
	 * 根据id值获取用户信息
	 * 先尝试从memcache中获取信息
	 * 失败则从数据库中调取
	 *
	 * @return unknown
	 */
	private function loadFromId() {
		global $gMemc;
		if ( $this->id == 0 ) {
			$this->loadDefaults();
			return false;
		}
		# 尝试使用memcache
		$key = MemcKey( 'user', 'id', $this->id );
		$data = $gMemc->get( $key );
		
		if ( !is_array( $data ) || $data['mVersion'] !== BK_USER_VERSION ) {
			# 过期或者数据类型不正确 则从DB载入
			$data = false;
		}
		if ( !$data ||$data['locked']==1) {
			# 从 DB中载入
			if ( !$this->loadFromDatabase() ) {
				# 如果不能根据ID载入 则定义为匿名用户
				return false;
			}
			$this->saveToCache();
		} else {
			# 从memcache得到数据
			foreach ( self::$mCacheVars as $name ) {
				$this->$name = $data[$name];
			}
		}
		return true;
	}
	/**
	 * 从数据库装载用户
	 *
	 * @return 得到用户返回true,反之返回false
	 */
	private function loadFromDatabase() {
		global $db;
		$this->id = intval( $this->id );
		/** 匿名用户 */
		if( !$this->id ) {
			$this->loadDefaults();
			return false;
		}
		#查询user表
		$s = $db->getRow("SELECT * FROM $this->dbname WHERE id=$this->id");//获取该id下用户的全部数据

		if ( $s !== false ) {
			# 初始化用户表数据
			$this->loadFromRow( $s );
			return true;
		} else {
			# 获取失败 匿名用户
			$this->id = 0;
			$this->loadDefaults();
			return false;
		}
	}
	/**
	 * 从用户表中一行的数据初始化对象
	 *
	 * @param array $row 
	 */
	private function loadFromRow( $row ) {
		$this->mDataLoaded = true;
		if ( isset( $row['id'] ) ) {
			$this->id = $row['id'] ;
		}
		$this->name = $row['name'];
		$this->point = $row['point'];
		$this->rank = $row['rank'];
		$this->new_num = $row['new_num'];
		$this->edit_num = $row['edit_num'];
		$this->reg_ip = $row['reg_ip'];
		$this->reg_time = $row['reg_time'];
		$this->word = $row['word'];
		$this->last_ip = $row['last_ip'];
		$this->last_time = $row['last_time'];
		$this->token = $row['token'];
		$this->locked = $row['locked'];
		$this->lock_time = $row['lock_time'];
		#头像地址获取
		$this->avatar = $this->getAvatar();
	}
	
	
	
	
	//------------------------   
    //   
    //  缓存和cookie
    //   
    //------------------------   
	/**
	 * 将用户数据保存在缓存中
	 */
	private function saveToCache() {
		global $gMemc;
		$this->load();
		if ( $this->id==0||$this->id == null) {
			// 匿名用户不需要缓存
			return;
		}
		$data = array();
		foreach ( self::$mCacheVars as $name ) {
			$data[$name] = $this->$name;
		}
		#装入memcache
		$data['mVersion'] = BK_USER_VERSION;
		$key = MemcKey( 'user', 'id', $this->id );
		$gMemc->set( $key, $data );
	}
	/**
	 * 设置登录cookie
	 *
	 * @param unknown_type $expire 过期时间
	 * @return unknown
	 */
	public function setCookies($expire=0){
		if(!$this->id || !$this->mDataLoaded )return false;
		setcookie('BK_User_token', $this->token, $expire, '/','ifeng.com');
	}
	/**
	 * 清理cookie
	 *
	 * @param string $name
	 */
	private  function clearCookie( $name ) {
		setcookie( "$name", '', time()-86400 ,'/','ifeng.com');
	}
	
	
	//------------------------   
    //   
    //  数据库写操作
    //   
    //------------------------  
	/**
	 * 插入一个新用户
	 * 插入成功后则返回新建用户
	 *
	 * @param array $arr 从sso获取的用户数据
	 * @return unknown
	 */
    public function insertNew($arr){
		global $log,$db;
		if(!is_array($arr)||$arr['id']==0){
			$log->error(__METHOD__.": WRONG　PARAM! $arr");
			return false;
		}
		#如果当前用户对象经过加载后不为匿名用户则不插入新数据
		if( $this->id === null || !$this->mDataLoaded ) {
			$this->load();
		}
		if($this->id != 0){
			return $this;
		}
		#插入数据
		$u_arr = array(
					'id' => $arr['id'],
					'name' => $arr['n'],
					'reg_time' => str_replace(".0","",$arr['rt']),
					'reg_ip' => $arr['rip'],
					'token' => $this->generateToken($arr['n'])	
				);
		if($db->insert($u_arr,$this->dbname)){
			#返回新建的用户对象
			$this->clearInstanceCache('id');
			$this->id = $u_arr['id'];
			$this->load();
			return $this;
		}else{
			$log->error(__METHOD__.":INSERT FAILED ".implode('-',$u_arr));
			return false;
		}
	}
	/**
	 * 更新用户的最后登录ip、时间
	 *
	 * @return 成功更新返回true,否则返回false
	 */
	public function refresh(){
		global $db;
		if($this->id === null || !$this->mDataLoaded ){
			$this->load();	
		}
		if($this->id == 0) return false;
		$ip = getIp();
		$time = getTime();
		$set = array(
				'last_ip' => $ip,
				'last_time' =>	$time	
			);
		$where = "id = $this->id";
		return $db->update($set,$where,$this->dbname);
	}
	
	/**
	 * 更新用户记录
	 *
	 * @param array $arr 必须是一维数组，数组的值为user表需要更新的字段名，且当前对象必须有该值
	 * @return unknown
	 */
	private function update($arr){
		$this->load();
		if($this->id==null||$this->id==0)return false;
		if(empty($arr)||!is_array($arr))return false;
		if(count($arr, COUNT_RECURSIVE)!==count($arr))return false;
		foreach ($arr as $name){
			if($this->$name===null)	return false;
			$data[$name] = $this->$name;
		}
		
		global $db;
		$where = "id=$this->id";
		if($db->update($data,$where,$this->dbname)){	
			$this->saveToCache();
			return true;
		}
		return false;
	}
	
	/**
	 * 用户个人设置
	 *
	 * @param string $word 个人简介
	 * @return unknown
	 */
	public function personal_set($word){
		if(!$this->id) return false;
		$this->word = $word ? $word : "";
		$arr = array('word');
		return  $this->update($arr);
		return false;
	}
	

	/**
	 * 用户解封
	 *
	 * @return unknown
	 */
	private function unblock(){
		$this->load();
		if(!$this->id)return false;
		$this->lock_time = "0000-00-00 00:00:00";
		$this->locked = 0;
		$arr = array('lock_time','locked');
		return $this->update($arr);
	}
	
	
	//------------------------   
    //   
    //  其他函数
    //   
    //------------------------ 
	/**
	 * 生成token
	 *
	 * @param string $salt
	 * @return string 每个用户创建时有唯一的token
	 */
    private function generateToken( $salt = '' ) {
		$token = dechex( mt_rand() ) . dechex( mt_rand() );
		return md5( $token . $salt );
	}
    
	/**
	 * 登出
	 *
	 */
    public function logout() {
    	global $gAuth;
    	$gAuth->SynLogout();
    	$this->clearInstanceCache( 'defaults' );
    	$this->clearCookie( 'BK_User_token' );
    }
    /**
     * 根据得分获取对应的rank
     *
     * @param int $point 实际获得的积分
     * @return array 或者 false;
     */
    private function Rankname($point){
    	global $db,$db_prefix;
    	$rank_db = $db_prefix."rank";
    	$sql = "SELECT id,rank FROM $rank_db WHERE min<=$point AND max>=$point";
    	return $db->getRow($sql);
    }
    
    
}