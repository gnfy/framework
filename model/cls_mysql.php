<?php
/**
 ************************************
 * 数据库初始化类
 ************************************
 */
class cls_mysql {
    
    // 数据表
    public $table;
    // SQL
    public $sql;
    // 是否显示SQL
    public $sql_debug = false;
    // 主库连接
    private static $master_con;
    // 从库连接
    private static $slave_con;

    /**
     * @功能：构造函数
     * @参数：在实例化的时候，可以带上数据表
     */
    function __construct($table = '') {
        empty( $table ) ? '' : $this->table = $table;
        $this->connect();
    }
    
    /**
     * @功能：数据库连接
     * @访问：私有
     * @参数：无
     * @返回: 数据库句柄
     */
    private function connect() {
        
        global $master, $slave;
        
        if ( !is_resource(self::$master_con) ) {
            if ( is_array($master) && !empty($master) ) {
                self::$master_con = mysql_connect($master['host'], $master['user'], $master['pwd']) or die ('connect false: '.mysql_error());
                mysql_query('SET NAMES '.$master['charset'], self::$master_con);
                if ($master['dbname'])
                    mysql_select_db($master['dbname'], self::$master_con)or die ('Can\'t select db: '.mysql_error());
            }
        }

        if ( !is_resource(self::$slave_con) ) {
            if ( is_array($slave) && !empty($slave) ) {
                self::$slave_con = mysql_connect($slave['host'], $slave['user'], $slave['pwd']) or die ('connect false: '.mysql_error());
                mysql_query('SET NAMES '.$slave['charset'], self::$slave_con);
                if ( $slave['dbname'] )
                    mysql_select_db($slave['dbname'], self::$slave_con) or die ('Can\'t select db: '.mysql_error());
            }
        }

        if ( !is_resource(self::$master_con) && !is_resource(self::$slave_con) )
            die ('no database connect');
    }

    /**
     * mysql_ping 数据库服务器状态，若断开，则重连
     *
     * @param   resource    $link 
     */
    function mysqlPing($link) {
        if (!mysql_ping($link)) {
            mysql_close(self::$master_con);
            mysql_close(self::$slave_con);
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
    function getSQL($data, $type) {
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
            $sql = empty($this->sql) ? 'SELECT '.$data['fields'].' FROM '.$this->table.$this->get_where($data['where']).$data['orderby'].' LIMIT 1' : $this->sql;
        } else if ($t == 'getall') {
            $sql = empty($this->sql) ? 'SELECT '.$data['fields'].' FROM '.$this->table.$this->get_where($data['where']).$data['orderby'].$data['limit'] : $this->sql;
        } else {
            die('参数不正确！');
        }
        if ($this->sql_debug == true) {
            echo $sql."<hr />\r\n";
        }
        return $sql;
    }
    
    /**
     * @功能：写入数据
     * @参数：$param => 要写入的数组,$key => 字段，$val => 值
     *        $type => 操作库，0 => 主库(默认)，1 => 从库
     * @返回：若写入成功，则返回最后一次写入的ID，否则返回0
     */
    function insert( $param = array(), $type = 0 ) {
        $key = array();
        $val = array();
        foreach ( $param as $k => $v ) {
            $key[] = '`'.$k.'`';
            $val[] = "'".mysql_escape_string($v)."'";
        }
        $data['key'] = $key;
        $data['val'] = $val;
        $sql    = $this->getSQL($data, 'insert');
        $data   = null;
        $res = $type == 0 ? self::$master_con : self::$slave_con;
        $this->mysqlPing($res);
        if ( mysql_query($sql, $res) ) {
            return mysql_insert_id();
        } else {
            return 0;
        }
    }

    /**
     *
     * 写入数据到主库
     *
     * @param array $param 要写入数组，$key => 字段， $val => 值
     * @return int 若写入成功，则返回最后一次写入的ID，否则返回0
     */
    function insert_m ($param = array()) {
        return $this->insert($param, 0);
    }
    
    /**
     *
     * 写入数据到从库
     *
     * @param array $param 要写入数组，$key => 字段， $val => 值
     * @return int 若写入成功，则返回最后一次写入的ID，否则返回0
     */
    function insert_s ($param = array()) {
        return $this->insert($param, 1);
    }

    /**
     * replace 方法
     *
     * @param   array   $param  要写入数组, $key => 字段, $val => 值
     * @param   int     $type   操作库, 0 => 主库(默认), 1 => 从库
     * @return  int     若replace成功，则返回最后一次写入ID,否则返回0
     */
    function replace($param = array(), $type = 0) {
        $key = array();
        $val = array();
        foreach ( $param as $k => $v ) {
            $key[] = '`'.$k.'`';
            $val[] = "'".mysql_escape_string($v)."'";
        }
        $data['key'] = $key;
        $data['val'] = $val;
        $sql = $this->getSQL($data, 'replace');
        $data = null;
        //$sql = 'REPLACE INTO '.$this->table.' ('.implode(',', $key).')VALUES('.implode(',', $val).');'; // 构造SQL
        $res = $type == 0 ? self::$master_con : self::$slave_con;
        $this->mysqlPing($res);
        if (mysql_query($sql, $res))
            return mysql_insert_id();
        else
            return 0;
    }

    /**
     * replace 到主库
     *
     * @param   array   $param  要写入的数组,   $key => 字段,   $val => 值
     * @return  int     若写入成功，则返回最后一次添加的ID，否则返回0
     */
    function replace_m($param = array()) {
        return $this->replace($param, 0);
    }

    /**
     * replace 到从库
     *
     * @param   array   $param  要写入的数组，  $key => 字段,   $val => 值
     * @return  int     若写入成功，则返回最后一次添加的ID，否则返回0
     */
    function replace_s($param = array()) {
        return $this->replace($param, 1);
    }
    
    /**
     * @权限：私有
     * @功能：构造条件
     * @参数：$param => 条件,最小单元a=b
     * @返回：返回字符串
     */
    function get_where( $param = '' ) {
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
     *        $type => 操作库，0 => 主库(默认), 1 ＝> 从库
     * @返回：若删除成功，则返回影响的行数，否则返回false
     */
    function del( $param = '', $type = 0 ) {
        
        //$sql = empty($this->sql) ? 'DELETE FROM '.$this->table.$this->get_where($param) : $this->sql;

        $sql    = $this->getSQL($param, 'delete');
        $param  = null;

        $res = $type == 0 ? self::$master_con : self::$slave_con;
            
        $this->mysqlPing($res);
        if ( mysql_query($sql, $res) ) {
            return mysql_affected_rows();
        } else {
            return false;
        }
        
    }
    
    /**
     *
     * 主库数据删除
     *
     * @param  mix $param 删除条件(最小单位：a=b)
     * @return mix 若删除成功，则返回影响行，否则返回false
     */
    function del_m ($param = '') {
        return $this->del($param, 0);
    }
    
    /**
     *
     * 从库数据删除
     *
     * @param  mix $param 删除条件(最小单位：a=b)
     * @return mix 若删除成功，则返回影响行，否则返回false
     */
    function del_s ($param = '') {
        return $this->del($param, 1);
    }
    
    /**
     * @功能：修改数据
     * @参数：$param => 要修改的数据,$key => 字段，$val => 值, $where => 修改条件
     *        $type => 操作的库，0 => 主库(默认), 1 => 从库
     * @返回：若修改成功，则返回影响的行数,否则返回false
     */
    function update( $param = '', $where = '', $type = 0 ) {
        
        $val = array();
        foreach ( $param as $k => $v ) {
            $val[] = '`'.$k."` = '".mysql_escape_string($v)."'";
        }
        $data = array (
                'val'   => $val,
                'where' => $where
            );
        //$sql = empty($this->sql) ? 'UPDATE '.$this->table.' SET '.implode(',', $val).$this->get_where($where) : $this->sql;
        $sql    = $this->getSQL($data, 'update');

        $data   = null;
        $param  = null;
        
        $res = $type == 0 ? self::$master_con : self::$slave_con;
        
        $this->mysqlPing($res);
        if ( mysql_query($sql, $res) ) {
            return mysql_affected_rows();
        } else {
            return false;
        }
        
    }

    /**
     *
     * 修改数据->主库
     *
     * @param   array   $param  要修改的数据，$key => 字段， $val => 值
     * @param   mix     $where  修改条件
     * @return  mix     若修改成功，则返回影响行数，否则返回false
     */
    function update_m($param = '', $where = '') {
        return $this->update($param, $where, 0);
    }

    /**
     *
     * 修改数据－> 从库
     *
     * @param   array   $param  要修改的数据，$key => 字段，$val => 值
     * @param   mix     $where  修改条件
     * @return  mix     若修改成功，则返回影响行数，否则返回false
     */
    function update_s($param = '', $where) {
        return $this->update($param, $where, 1);
    }
    
    /**
     * @功能：查询数据(单条)
     * @参数：$param => 查询条件
     *        $type => 操作的数据库, 0 => 主库, 1 => 从库(默认)
     * @返回：如果查询成功，则返回一维数组，否则返回空
     */
    function getOne( $param = '', $type = 1 ) {
        
        $fields  = empty ( $param['fields'] )  ? ' * ' : $param['fields'];
        $orderby = empty ( $param['orderby'] ) ? '' : ' ORDER BY '.$param['orderby'];
        
        //$sql = empty($this->sql) ? 'SELECT '.$fields.' FROM '.$this->table.$this->get_where($param['where']).$orderby.' LIMIT 1' : $this->sql;

        $data = array (
                'fields'    => $fields,
                'orderby'   => $orderby,
                'where'     => $param['where'],
                'orderby'   => $orderby
            );

        $sql    = $this->getSQL($data, 'getone');

        $data   = null;
        $param  = null;

        $res = $type == 0 ? self::$master_con : self::$slave_con;
        $this->mysqlPing($res);
        if ( $result = mysql_query($sql, $res) ) {
            return mysql_fetch_assoc($result);
        } else {
            return '';
        }
        
    }

    /**
     *
     * 查询数据(单条) -> 主库
     *
     * @param   array   $param  查询条件
     * @return  mix     如果查询成功，则返回一维数组，否则返回空
     */
    function getOne_m($param = '') {
        return $this->getOne($param, 0);
    }
    
    /**
     *
     * 查询数据(单条) -> 从库
     *
     * @param   array   $param  查询条件
     * @return  mix     如果查询成功，则返回一维数组，否则返回空
     */
    function getOne_s($param = '') {
        return $this->getOne($param, 1);
    }

    /**
     * 统计记录数
     *
     * @param   string  $where 统计条件
     * @param   int     $type  操作的库; 0 => 主库 ,1 => 从库(默认)
     * @return  int     记录数
     */
    function countAll($where = '', $type = 1) {
        $param = array (
                'fields' => 'count(*) as num',
                'where'  => $where
            );
        $this->clearSQL();
        $rs = $this->getOne($param, $type);
        if ($rs)
            return $rs['num'];
        else
            return 0;
    }

    /**
     * 统计主库表中记录数
     *
     * @param   string  $where  统计条件
     * @return  int     记录数
     */
    function countAll_m($where = '') {
        return $this->countAll($where, 0);
    }

    /**
     * 统计从库表中记录数
     *
     * @param   string  $where  统计条件
     * @return  int     记录数
     */
    function countAll_s($where = '') {
        return $this->countAll($where, 1);
    }

    /**
     * @功能：查询多条记录
     * @参数：$param => 查询条件
     *        $type => 操作的数据库，0 => 主库，1 => 从库(默认)
     *        $return_type => 返回类型, 1 => 数组，2 => mysql 资源操作符
     * @返回：如果查询成功，则返回二维数组，否则返回空
     */
    function getAll( $param = '', $type = 1, $return_type = 1 ) {
    
        $row = array();
        $rs  = array();
        $fields = empty ( $param['fields'] ) ? ' * ' : $param['fields'];
        
        $start      = empty ( $param['start'] ) ? 0 : $param['start'];
        $limit      = empty ( $param['offset'] ) ? '' : ' LIMIT '.$start.', '.$param['offset'];
        $orderby    = empty ( $param['orderby'] ) ? '' : ' ORDER BY '.$param['orderby'];
        
        //$sql = empty($this->sql) ? 'SELECT '.$fields.' FROM '.$this->table.$this->get_where($param['where']).$orderby.$limit : $this->sql;

        $data = array (
                'fields'    => $fields,
                'start'     => $start,
                'limit'     => $limit,
                'orderby'   => $orderby,
                'where'     => empty($param['where']) ? '' : $param['where']
            );

        $sql = $this->getSQL($data, 'getall');
        $data = null;
        $param = null;

        $res = $type == 0 ? self::$master_con : self::$slave_con;
        
        $this->mysqlPing($res);
        if ( $result = mysql_query($sql, $res) ) {
            if ($return_type == 1) {
                while( $row = mysql_fetch_assoc($result) ) {
                    $rs[] = $row;
                }
            } else {
                $rs = $result;
            }
            return $rs;
            
        } else {
            return '';
        }
        
    }
    
    /**
     *
     * 查询多条记录 -> 主库
     *
     * @param   array   $param          查询条件
     * @param   int     $return_type    返回类型,1 =>数组，2 => mysql资源操作符
     * @return  mix     如果查询成功，则返回二维数组，否则返回空
     */
    function getAll_m($param = '', $return_type = 1) {
        return $this->getAll($param, 0, $return_type);
    }

    /**
     *
     * 查询多条记录 -> 从库
     *
     * @param   array   $param          查询条件
     * @param   int     $return_type    返回类型,1 =>数组，2 => mysql资源操作符
     * @return  mix     如果查询成功，则返回二维数组，否则返回空
     */
    function getAll_s($param = '', $return_type = 1) {
        return $this->getAll($param, 1, $return_type);
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
     *
     * 清除所有设定
     * @param   无
     * @return  无
     */
    function clear() {
        $this->clearSQL();
        $this->clearTable();
    }

}
