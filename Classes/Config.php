<?Php
class Config extends SocketServer{
	var $ip = "192.252.220.146";
	var $port = 65500;
	var	$db_type = "sqlite";
	var $db_file = "sqlite.db";
	var $db_host = "192.252.220.146";
	var $db_table = "server";
	var $db_user = "root";
	var $db_pass = "";
	var $enc_type = "md5";
	var $rsa_public_key = false;
	var $rsa_private_key = false;
	
	function Config($file){
		if(file_exists($file)) $this->parse_ini($file);
		else $this->log("error: config file does not exists");
	}
	
	function parse_ini($file){
		$this->log("info: loading config file");
		$file_lines = file($file);
		foreach($file_lines as $line) {
            if(strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line);
				$opt = trim($key);
				if(!isset($this->$opt)) die(date("d/m/y h:i:s")."> \033[1;31mError en archivo de configuracion: $opt no es una opcion");
				else $this->$opt = trim($value);
            }
        }
	}
}
?>