<?Php
class SQLite3{
    var $db;
    var $file;
    var $link;
    var $sqls = array();

    function SQLite3($file=null) {
        $this->file = $file;
        $this->connect();
    }

    function connect(){
    	$this->link = new PDO("sqlite:".$this->file);
    }

    function close(){
        $this->link = null;
    }

    function create($table, $params, $options=null){
        $i = 0;
        $vars = "";
        foreach($params as $var=>$value){
            $i++;
            $vars .= "'$var' $value".(($i==sizeof($params))? '': ', ');
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
        return $this->exec($sql);
    }

    function insert($into, $params){
        $i=0;
        $vals = "";
        foreach($params as $var=>$value){
            $i++;
            //$vars .= "$var".(($i==sizeof($params))? '': ', ');
            $vals .= "$value".(($i==sizeof($params))? '': ', ');
        }
        $sql = sprintf("INSERT INTO %s VALUES (%s);", $into, $vals);
        return $this->exec($sql);
    }

    function set($in, $params, $options){
        $i = 0;
        $vars = "";
        foreach($params as $var=>$value){
            $i++;
            $vars .= "'$var' = $value".(($i==sizeof($params))? '': ', ');
        }
        $sql = sprintf("UPDATE %s SET %s %s", $in, $vars, $options);
        return $this->exec($sql);
    }

    function exec($sql){
        $num = $this->link->exec($sql);
        $this->sqls[] = $sql;
        return $num;
    }

    function query($sql){
    	$sql = $this->link->query($sql);
        $this->sqls[] = $sql;
        return $sql;
    }

    function getVar($var, $table, $options=null){
        $sql = sprintf("SELECT %s FROM %s %s LIMIT 1", $var, $table, $options);
        $result = $this->query($sql);
        $r = $result->fetch(PDO::FETCH_ASSOC);
        if($var=="*") return $r;
        else return $r[$var];
    }

    function getVars($vars, $table, $options=null, $onerow=false){
        $sql = sprintf("SELECT %s FROM %s %s", $vars, $table, $options);
        return $this->result($this->query($sql), false, $onerow);
    }

    function search($vars, $table, $options=null){
        $sql = sprintf("SELECT %s FROM %s LIKE%s", $vars, $table, $options);
        $p = $this->result($this->query($sql));
        return $this->result($p);
    }
	
	function result($resource, $string=false, $onerow=false){
        $array = array();
    	if($string){
            $result = $resource->fetch(PDO::FETCH_ASSOC);
            return $result;
        }
        while($result = $resource->fetch(PDO::FETCH_ASSOC)){
            $array[] = $result;
        }
        /*if($onerow) return $array[0];
        else return $array;*/
        if(sizeof($array)>0)
            return $array;
        else return null;
    }
}
?>