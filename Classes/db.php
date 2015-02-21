<?Php
class msDB{
    var $host = DB_HOST;
    var $user = DB_USER;
    var $pass = DB_PASSWORD;
    var $db = DB_NAME;
    var $auto_connect;
    var $link;
    var $sqls = array();
    function msDB ($host=null, $user=null, $password=null, $db=null, $auto_connect=false) {
        if(func_num_args()==1&&is_bool($host))
            $this->auto_connect = $host;
        if(func_num_args()>2){
            $this->host = isset($host)? $host:$this->host;
            $this->user = isset($user)? $user:$this->user;
            $this->pass = isset($password)? $password:$this->pass;
            $this->db = isset($db)? $db:$this->db;
            $this->auto_connect = isset($auto_connect)? $auto_connect:$this->auto_connect;
        }
        if($this->auto_connect) $this->connect();
    }
    function connect(){
    	$this->link = mysql_connect($this->host, $this->user, $this->pass);
        $this->select($this->db);
    }
    function close(){
        $this->free();
    	mysql_close($this->link);
    }
    function select($db){
    	mysql_select_db($db,$this->link);
    }
    function create($table, $params, $options=null){
        foreach($params as $var=>$value){
            $i++;
            $vars .= "$var $value".(($i==sizeof($params))? '': ', ');
        }
        $sql = sprintf("CREATE TABLE %s (%s)%s;", $table, $vars, $options);
        $this->query($sql);
    }
    function drop($table){
        $sql = sprintf("DROP TABLE %s", $table);
        $this->query($sql);
    }
    function alter($table, $action, $field){
        $sql = sprintf("ALTER TABLE %s %s %s", $table, $action, $field);
        $this->query($sql);
    }
    function delete($from, $options){
        $sql = sprintf("DELETE FROM %s %s", $from, $options);
        $this->query($sql);
    }
    function insert($into, $params){
        foreach($params as $var=>$value){
            $i++;
            $vars .= "$var".(($i==sizeof($params))? '': ', ');
            $vals .= "$value".(($i==sizeof($params))? '': ', ');
        }
        $sql = sprintf("INSERT INTO %s (%s) VALUES (%s);", $into, $vars, $vals);
        return $this->query($sql);
    }
    function set($in, $params, $options){
        foreach($params as $var=>$value){
            $i++;
            $vars .= "$var = $value".(($i==sizeof($params))? '': ', ');
        }
        $sql = sprintf("UPDATE %s SET %s %s", $in, $vars, $options);
        $this->query($sql);
    }
    function query($sql){
    	$sql = mysql_query($sql, $this->link);
        $this->sqls[] = $sql;
        return $sql;
    }
    function get_var($var, $table, $options=null){
        $sql = sprintf("SELECT %s FROM %s %s LIMIT 1", $var, $table, $options);
        return $this->result($this->query($sql),true);
    }
    function get_vars($vars, $table, $options=null, $onerow=false){
        $sql = sprintf("SELECT %s FROM %s %s", $vars, $table, $options);
        return $this->result($this->query($sql), false, $onerow);
    }
    function search($vars, $table, $options=null){
        $sql = sprintf("SELECT %s FROM %s LIKE%s", $vars, $table, $options);
        $p = $this->result($this->query($sql));
        return $this->result($p);
    }
    function free(){
        foreach($this->sqls as $sql){
            mysql_free_result($sql);
        }
    }
	
	function result($resource, $string=false, $onerow=false){
    	if($string){
            $result = mysql_fetch_array($resource);
            return $result[0];
        }
        while($result = mysql_fetch_array($resource)){
            $array[] = $result;
        }
        if($onerow) return $array[0];
        else return $array;
    }
}
?>