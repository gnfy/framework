<?php
/**
 ************************************
 * 数据库初始化类
 ************************************
 */
class cls_DB {
    
    // 数据表
    public $table;
    // SQL
    public $sql;
    // 是否显示SQL
    public $sql_debug = false;
    // 静态化数据库实例
    private static $instance = array();
    // 当前连接
    private $dbh;
    // 数据库配置标识
    public $flag = 'master';
    // 数据库节点
    public $node = 0;
    // 上一次执行的sql
    private $lastsql;

    /**
     * @功能：构造函数
     * @参数：$flag => 0 表示要连接的配置
     */
    function __construct($flag = 'master', $node = 0) {
        $this->flag = $flag;
        $this->node = $node;
        $this->connect();
    }

    /**
     * 获得数据库连接的静态方法
     *
     * @param   int $flag   数据库资源配置符
     * @param   int $node   数据库节点
     * @return              数据库资源操作符
     */
    public static function getDB($flag = 'master', $node = 0) {
        if (self::$instance[$flag][$node] == null) {
            $class_name =  __CLASS__;
            self::$instance[$flag][$node] = new $class_name ($flag, $node);
        } else {
            include getConfig('db');

            $_db = $db_config[$flag][$node];

            // 重新选择数据库
            if ($_db['dbname']) {
                $obj = self::$instance[$flag][$node];
                $obj->selectDB($_db['dbname']);
            }

        }
        return self::$instance[$flag][$node];
    }
    
    /**
     * @功能：数据库连接
     * @访问：私有
     * @返回: 数据库句柄
     */
    public function connect() {

        include getConfig('db');

        $_db = $db_config[$this->flag][$this->node];
        if ( is_array($_db) && !empty($_db) ) {
            $this->dbh = mysql_connect($_db['host'], $_db['user'], $_db['password']) or die ('connect false: '.mysql_error());
            mysql_query('SET NAMES '.$_db['charset'], $this->dbh);
            if ($_db['dbname']) {
                $this->selectDB($_db['dbname']);
            }
            if (isset($_db['debug'])) {
                $this->sql_debug = $_db['debug'];
            }
        }
        if ( !is_resource($this->dbh) ) {
            die ('no database connect');
        }
    }

    /**
     * 选择数据库
     *
     * @param   string  $db_name    数据库名称
     * @return  bool
     */
    private function selectDB($db_name) {
        $ret = false;
        if ($db_name) {
            $this->mysqlPing($this->dbh);
            $ret = mysql_select_db($db_name, $this->dbh) or die ('Can\'t select db: '.mysql_error());
        }
        return $ret;
    }

    /** 
     * mysqlPing 数据库服务器状态，若断开，则重连
     *
     * @param   resource    $link   mysql数据库连接
     */
    function mysqlPing($link) {
        if (!mysql_ping($link)) {
            mysql_close($link);
            $this->connect();
        }   
    }
    
    /**
     * 生成SQL
     *
     * @param   mix     $data   要生成SQL的参数 
     * @param   int     $type   1：写入，2：替换，3：删除，4：修改，5：查询(单条), 6：查询(多条)
     * @return  mix     生成好的SQL,或报错
     */
    private function makeSql($data, $type) {
        $t = strtolower($type);
        $sql = '';
        if ($t == 'insert') {
            $sql = empty($this->sql) ? 'INSERT INTO '.$this->table.' ('.implode(',',$data['key']).')VALUES('.implode(',',$data['val']).');' : $this->sql;
        } else if ($t == 'replace') {
            $sql = 'REPLACE INTO '.$this->table.' ('.implode(',', $data['key']).')VALUES('.implode(',', $data['val']).');';
        } else if ($t == 'delete') {
            $sql = empty($this->sql) ? 'DELETE FROM '.$this->table.$this->get_where($data) : $this->sql;
        } else if ($t == 'update') {
            $sql = empty($this->sql) ? 'UPDATE '.$this->table.' SET '.implode(',', $data['val']).$this->get_where($data['where']) : $this->sql;
        } else if ($t == 'getone') {
            $sql = empty($this->sql) ? 'SELECT '.$data['fields'].' FROM '.$this->table.$this->get_where($data['where']).$data['groupby'].$data['orderby'].' LIMIT 1' : $this->sql;
        } else if ($t == 'getall') {
            $sql = empty($this->sql) ? 'SELECT '.$data['fields'].' FROM '.$this->table.$this->get_where($data['where']).$data['groupby'].$data['orderby'].$data['limit'] : $this->sql;
        } else if ($t == 'begin') {
            $sql = 'BEGIN';
        } else if ($t == 'commit') { 
            $sql = 'COMMIT';
        } else if ($t == 'autocommit') {
            $sql = 'SET AUTOCOMMIT = '.intval($data);
        } else if ($t == 'rollback') {
            $sql = 'ROLLBACK';
        } else if ($t == 'close') {
            $sql = 'CLOSE';
        } else {
            die('参数不正确！');
        }
        if ($this->sql_debug == true) {
            echo $sql."<hr />\r\n";
        }
        $this->lastsql = $sql;
        $this->mysqlPing($this->dbh);
        return $sql;
    }

    /**
     * 获得上一次执行的SQL
     */
    public function getLastSql() {
        return $this->lastsql;
    }
    
    /**
     * @功能：写入数据
     * @参数：$param => 要写入的数组,$key => 字段，$val => 值
     * @返回：若写入成功，则返回最后一次写入的ID，否则返回0
     */
    function insert( $param = array() ) {
        $key = array();
        $val = array();
        foreach ( $param as $k => $v ) {
            $key[] = '`'.$k.'`';
            $val[] = "'".mysql_escape_string($v)."'";
        }
        $data['key'] = $key;
        $data['val'] = $val;
        $sql    = $this->makeSql($data, 'insert');
        $data   = null;
        if ( mysql_query($sql, $this->dbh) ) {
            return mysql_insert_id();
        } else {
            return 0;
        }
    }

    /**
     * replace 方法
     *
     * @param   array   $param  要写入数组, $key => 字段, $val => 值
     * @return  int     若replace成功，则返回最后一次写入ID,否则返回0
     */
    function replace($param = array()) {
        $key = array();
        $val = array();
        foreach ( $param as $k => $v ) {
            $key[] = '`'.$k.'`';
            $val[] = "'".mysql_escape_string($v)."'";
        }
        $data['key'] = $key;
        $data['val'] = $val;
        $sql = $this->makeSql($data, 'replace');
        $data = null;
        //$sql = 'REPLACE INTO '.$this->table.' ('.implode(',', $key).')VALUES('.implode(',', $val).');'; // 构造SQL
        if (mysql_query($sql, $this->dbh))
            return mysql_insert_id();
        else
            return 0;
    }
    
    /**
     * @权限：私有
     * @功能：构造条件
     * @参数：$param => 条件,最小单元a=b
     * @返回：返回字符串
     */
    private function get_where( $param = '' ) {
        $where = '';
        if ( is_array($param) ) {
            foreach ( $param as $v ) {
                if (!empty($v)) {
                    $where = empty ($where) ? $v : $where.' AND '.$v;
                }
            }
        } else {
            $where = $param;
        }
        return  empty ($where) ? '' : ' WHERE '.$where;
    }
    
    /**
     * @功能：删除数据
     * @参数：$param => 删除条件(最小单位：a=b)
     * @返回：若删除成功，则返回影响的行数，否则返回false
     */
    function del( $param = '') {
        
        //$sql = empty($this->sql) ? 'DELETE FROM '.$this->table.$this->get_where($param) : $this->sql;

        $sql    = $this->makeSql($param, 'delete');
        $param  = null;
            
        if ( mysql_query($sql, $this->dbh) ) {
            return mysql_affected_rows();
        } else {
            return false;
        }
        
    }
    
    /**
     * @功能：修改数据
     * @参数：$param => 要修改的数据,$key => 字段，$val => 值, $where => 修改条件
     * @返回：若修改成功，则返回影响的行数,否则返回false
     */
    function update( $param = '', $where = '') {
        
        $val = array();
        foreach ( $param as $k => $v ) {
            $val[] = '`'.$k."` = '".mysql_escape_string($v)."'";
        }
        $data = array (
                'val'   => $val,
                'where' => $where
            );
        //$sql = empty($this->sql) ? 'UPDATE '.$this->table.' SET '.implode(',', $val).$this->get_where($where) : $this->sql;
        $sql    = $this->makeSql($data, 'update');

        $data   = null;
        $param  = null;
        
        if ( mysql_query($sql, $this->dbh) ) {
            return mysql_affected_rows();
        } else {
            error_log(mysql_error());
            return false;
        }
        
    }

    
    /**
     * @功能：查询数据(单条)
     * @参数：$param => 查询条件
     * @返回：如果查询成功，则返回一维数组，否则返回空
     */
    function getOne( $param = '') {
        
        $fields  = empty ( $param['fields'] )  ? ' * ' : $param['fields'];
        $orderby = empty ( $param['orderby'] ) ? '' : ' ORDER BY '.$param['orderby'];
        $groupby = empty ( $param['groupby'] ) ? '' : ' GROUP BY '.$param['groupby'];
        
        //$sql = empty($this->sql) ? 'SELECT '.$fields.' FROM '.$this->table.$this->get_where($param['where']).$orderby.' LIMIT 1' : $this->sql;

        $data = array (
                'fields'    => $fields,
                'orderby'   => $orderby,
                'groupby'   => $groupby,
                'where'     => $param['where']
            );

        $sql    = $this->makeSql($data, 'getone');

        $data   = null;
        $param  = null;

        if ( $result = mysql_query($sql, $this->dbh) ) {
            return mysql_fetch_assoc($result);
        } else {
            return ''; //? WHY RETURN EMPTY STRING?
        }
        
    }

    /**
     * 统计记录数
     *
     * @param   string  $where 统计条件
     * @return  int     记录数
     */
    function countAll($where = '') {
        $param = array (
                'fields' => 'count(*) as num',
                'where'  => $where
            );
        $this->clearSQL();
        $rs = $this->getOne($param);
        if ($rs)
            return $rs['num'];
        else
            return 0;
    }


    /**
     * @功能：查询多条记录
     * @参数：$param => 查询条件
     *        $return_type => 返回类型, 1 => 数组，2 => mysql 资源操作符
     * @返回：如果查询成功，则返回二维数组，否则返回空
     */
    function getAll( $param = '', $return_type = 1 ) {
    
        $row = array();
        $rs  = array();
        $fields = empty ( $param['fields'] ) ? ' * ' : $param['fields'];
        
        $start      = empty ( $param['start'] ) ? 0 : $param['start'];
        $limit      = empty ( $param['offset'] ) ? '' : ' LIMIT '.$start.', '.$param['offset'];
        $orderby    = empty ( $param['orderby'] ) ? '' : ' ORDER BY '.$param['orderby'];
        $groupby    = empty ( $param['groupby'] ) ? '' : ' GROUP BY '.$param['groupby'];
        
        //$sql = empty($this->sql) ? 'SELECT '.$fields.' FROM '.$this->table.$this->get_where($param['where']).$orderby.$limit : $this->sql;

        $data = array (
                'fields'    => $fields,
                'start'     => $start,
                'limit'     => $limit,
                'orderby'   => $orderby,
          	'groupby'   => $groupby,
                'where'     => empty($param['where']) ? '' : $param['where']
            );

        $sql = $this->makeSql($data, 'getall');
        $data = null;
        $param = null;

        if ( $result = mysql_query($sql, $this->dbh) ) {
            if ($return_type == 1) {
                while( $row = mysql_fetch_assoc($result) ) {
                    $rs[] = $row;
                }
            } else {
                $rs = $result;
            }
            return $rs;
            
        } else {
            return mysql_error();
        }
        
    }

    /**
     * 数据库回滚－操作开始
     *
     * @param   int     $param  参数, 1 => 表示自动提交，0 => 表示非自动提交
     * @return  null
     */
    function begin($param) {
        $sql    = $this->makeSql('', 'begin');
        mysql_query($sql, $this->dbh);
        $p      = intval($param);
        $sql    = $this->makeSql($p, 'autocommit');
        mysql_query($sql, $this->dbh);
    }

    /**
     * 数据库回滚－操作提交
     *
     * @param   null
     * @return  null
     */
    function commit() {
        $sql    = $this->makeSql('', 'commit');
        mysql_query($sql, $this->dbh);
    }

    /**
     * 数据库回滚－回滚操作
     *
     * @param   null
     * @param   null
     */
    function rollback() {
        $sql    = $this->makeSql('', 'rollback');
        mysql_query($sql, $this->dbh);
    }

    /**
     * 数据库回滚－关闭操作
     *
     * @param   null
     * @param   null
     */
    function close() {
        $sql    = $this->getSQl('', 'close');
        mysql_query($sql, $this->dbh);
    }
    
    /**
     *
     * 清除已设定的sql
     * 
     * @param   无
     * @return  无
     */
    function clearSQL() {
        $this->sql = '';
    }
    
    /**
     *
     * 设定数据表
     *
     * @param   string $table 数据表
     * @return  无
     */
    function setTable($table = '') {
        $this->table = $table;
    }

    /**
     *
     * 清除已设定的数据表
     *
     * @param   无
     * @return  无
     */
    function clearTable() {
        $this->table = '';
    }

    /**
     * 清除所有的数据库连接
     *
     * @param   无
     * @return  无
     */
    function clearDBConnect() {
        self::$instance = null;
    }

    /**
     *
     * 清除所有设定
     * @param   无
     * @return  无
     */
    function clear() {
        $this->clearSQL();
        $this->clearTable();
        $this->clearDBConnect();
    }

}
