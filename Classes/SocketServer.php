<?php
include("db.php");
include("Sqlite.php");
include("Config.php");
include("Service.php");
include("Yugioh.php");
include("FTP.php");

class SocketServer{
	var $ip;
	var $port;
	var $masterSocket;
    var $currentSockets = array();
	var $users = array();
	var $maxConnection;
	var $services = array();
	var $config;
	var $db;//Server DB

	var $admin_socket;

	var $admins = array();
	var $mods = array();

	var $debug = true;
	var $run = true;

    
	function SocketServer($ip, $port){
		$this->config = new Config("config.ini");
		$this->db = new Sqlite3("lib/db/server.db");
		$this->ip = $this->config->ip;
		$this->port = $this->config->port;
		$this->initSocket();
		$this->loadServices();
		$this->run();

	}
	
	function initSocket(){
		//---- Start Socket creation for PHP 5 Socket Server -------------------------------------
		$this->log("Iniciando servidor...");
        if (($this->masterSocket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) < 0) {
            $this->log("socket_create() failed, reason: " . socket_strerror($this->masterSocket));
        }
		$this->log("Iniciando servidor en ".$this->ip.":".$this->port);
        socket_set_option($this->masterSocket, SOL_SOCKET,SO_REUSEADDR, 1);
        if (($ret = socket_bind($this->masterSocket, $this->ip, $this->port)) < 0) {
            $this->log("socket_bind() failed, reason: " . socket_strerror($ret));
        }
        if (($ret = socket_listen($this->masterSocket, 5)) < 0) {
            $this->log("socket_listen() failed, reason: " . socket_strerror($ret));
        }
		$this->currentSockets[] = $this->masterSocket;
		$this->log("\033[0;32mServidor iniciado con exito\033[0;37m");
		
	}
    
    function endSocket(){
    	//TODO: Terminar conexion con determinados usuarios (whitelist|admins|admins&mods|all)
		$this->log("event: stop server");
        socket_close($this->currentSockets);
        socket_close($this->masterSocket);
    }

    function parseRequest($socket, $data){
    	$currentUser = $this->getUser($socket);
		if($this->debug) $this->log("event: data received: ".$data);
		//TODO: Identificar usuario para saber si esta conectado event: connection established con $status en User class
		if($data=="<policy-file-request/>".chr(0x00)){
			socket_write($socket, '<cross-domain-policy><allow-access-from domain="*" to-ports="*"/></cross-domain-policy>'.chr(0x00));
		}else{
			$dd = substr($data, 0, 3);
			$d = explode("|", str_replace(chr(0), "", $data));
			if($currentUser->status!=2){

				if($d[0]=="login"){

					if(!isset($d[1])||!isset($d[2])){
						$currentUser->ci++;
						//$this->sendUser($socket, "loginError|"); //Evitar estos mensajes por seguridad
					}else{
						$userData = $this->db->getVars("*", "users", "where name='".$d[1]."'");
						if($d[1]!=$userData[0]["name"]||$d[2]!=$userData[0]["pass"]){
							$currentUser->ci++;
							//$this->sendUser($socket, "loginError|"); //Evitar estos mensajes por seguridad
						}else{
							$currentUser->type = $userData[0]["type"];
							$currentUser->status = 2;

							if($currentUser->type==1){
								$this->admin_socket = $socket;
							}
							$this->sendUser($socket, "loggedIn");
						}
					}
				}else if($dd=="GET"){//HTTPRequest
						$this->sendHTTP($socket, time());
						$this->disconnect($socket);
				}else{
					$currentUser->ci++;//Aumentan alertas por cada error de logeo al llegar a 5 desconecta
					//$this->sendUser($socket, "needLogin|"); //Evitar estos mensajes por seguridad
				}
				if($currentUser->ci==1) $this->disconnect($socket);//Arreglar problema // checar
			}else{
				if($d[0]=="control"){
					//Color error "FF3E3E"
					//TODO: Pasar los mensajes que se mandan a codigos para que el cliente los interprete
					$cmd = explode(" ", $d[1]);
					if($currentUser->type==1){
						if($cmd[0]=="logout"){
							$this->sendAdmin($this->admin_socket, "logout");
							$this->disconnect($this->admin_socket);
							$this->admin_socket = null;
						}else if($cmd[0]=="reload"){
							if(isset($cmd[1])&&isset($cmd[2])){
								if($cmd[1]=="service"){
									if(isset($this->services[$cmd[2]])){
										$serviceName = $cmd[2];
										$this->services[$serviceName]->stop();
										$this->services[$serviceName] = null;//Delete service
										
										$service = new $serviceName($this);

										$this->services[$serviceName] = $service;//Add new service

										$this->sendAdmin($this->admin_socket, "reloaded|$serviceName");
									}else
										$this->sendAdmin($this->admin_socket, "noExistService|".$cmd[2]);
								}else
									$this->sendAdmin($this->admin_socket, "invalidArgument".$cmd[1]);
							}else{
								$this->sendAdmin($this->admin_socket, "invalidArgumentSize");
							}
						}else if($cmd[0]=="stop"){
							$this->stop();
						}else if($cmd[0]=="debug"){
							if($this->debug){
								$this->debug = false;
								$this->sendAdmin($this->admin_socket, "debugOff");
							}else{
								$this->debug = true;
								$this->sendAdmin($this->admin_socket, "debugOn");
							}
						}else{
							$this->sendAdmin($this->admin_socket, "invalidCommand");
						}
					}
				}else if($d[0]=="yugioh"){
					$msg = $this->services["yugioh"]->cmd($d);
					$this->allMsg($msg.chr(0));
				}else if($d[0]=="ftp"){
					$msg = $this->services["FTP"]->cmd($d, $data);
					$this->sendUser($socket ,$msg.chr(0));
				}else{
					$this->disconnect($socket);
				}
			}
		}
	}

	function stop(){
		//$this->endSocket();
		$this->log("Stopping server...");
		$this->run = false;
		exit;
	}

	function loadServices()
	{
		//TODO: Carga automatica de plugins desde carpeta plugins con include
		$this->services["Service"] = new Service($this);
		//$this->services["Yugioh"] = new Yugioh($this);
		$this->services["FTP"] = new FTP($this);
	}

	//Test envio HTTP
	function sendHTTP($socket, $msg){
		//TODO: Acortar mensajes
		if($this->debug) $this->log("sendHTTP: ".$msg);
		$msg = $msg;
        socket_write($socket, $msg);
	}

	//Envia mensaje a todos los administradores
	function sendAdmin($socket, $msg){
		//TODO: Acortar mensajes
		if($this->debug) $this->log("sendAdmin: ".$msg);
		$msg = $msg.chr(0);
        socket_write($socket, $msg);
	}
    
    //Envia mensaje a todos
    //TODO: Filtrar por servicio allMsg($msg, $service)
    function allMsg($message){
    	if($this->debug) $this->log("sendAll: ".$message);
        $clients = $this->currentSockets;
        array_shift($clients);//Elimina el masterSocket (Primer elemento del array)
		if(!is_array($clients)) $clients = array($clients);
        foreach($clients as $client) {
            if($client === NULL) continue;
            //str_replace(chr(0), "", $message) <-- Para websocket
            socket_write($client, $message);
        }
	}

	//Envia mensaje a un usuario especifico
	function sendUser($socket, $message){
		if($this->debug) $this->log("sendUser: ".$message);
        socket_write($socket, $message.chr(0));
	}
	
	//Inicia el bucle del servidor para esperar conexiones
    function run(){
        while ($this->run) {
            $changed_sockets = $this->currentSockets;
            $num_changed_sockets = socket_select($changed_sockets, $write = NULL, $except = NULL, NULL);
            foreach($changed_sockets as $socket) {
                if ($socket == $this->masterSocket) {
                    if (($client = socket_accept($this->masterSocket)) < 0) {
                        $this->log("socket_accept() failed: reason: " . socket_strerror($msgsock));
                        continue;
                    } else {
						$this->connect($client);
                    }
                } else {
                    $bytes = @socket_recv($socket, $buffer, 20480, 0);
                    if ($bytes == 0) {
						$this->disconnect($socket);
                    } else {
                        $this->parseRequest($socket, $buffer);
                    }
                }
            }
			//sleep(1);
        }
    }
	
	//Conectar usuario
	function connect($socket){
		$user = new User($socket, $this);
		array_push($this->currentSockets, $socket);
		$this->users[$user->id] = $user;
		if($this->debug) $this->log("event: connection");
	}
	
	//Desconectar usuario
	function disconnect($socket){
		$index = array_search($socket, $this->currentSockets);
		$user = $this->getUser($socket);
		if(array_key_exists($user->id, $this->users)){
			unset($this->users[$user->id]);
		}
		
		unset($this->currentSockets[$index]);
		socket_close($socket);
		if($this->debug) $this->log("event: connection closed");
	}
	
	//Buscar usuario mediante su conexion
	function getUser($socket){
		$found=null;
        foreach($this->users as $user) {
            if($user->socket==$socket) {
                $found=$user;
                break;
            }
        }
        return $found;
	}
	
	//Muestra mensajes en la consola del servidor
	function log($msg){
		echo date("h:i:s")."> $msg\n";
	}
	
	//Pasar funciones a clase Utility
		function ps($str){
			$l = strlen($str);
			$t = "";
			for($i=0;$i<$l;$i++){
				$t .= $str[$i]." = ".ord($str[$i])."\n";
			}
			echo $t."\n";
		}
		
		function addbyte($input) {
			return chr(hexdec($input));
		} 
		function extbyte($input) {
			return dechex(ord($input));
		}
	//-------------------------------------------------------
}

class User extends SocketServer{
	//Datos del servidor
    var $id = null;
    var $socket = null;
    var $handshake = false;//Remover
    
    var $data = array();//Remover
	
	var $p;//Parent (Servidor)

	//Datos de usuario
	var $name;
	var $realID;

	//Acceso al servidor
	var $status = 0;//1 esperando datos de acceso, 2 accesado
	var $type = 0;//0 normal, 1 admin, 2 mod

	//Seguridad
	var $ip = null;
	var $ci = 0; //Intentos de conexion a los 5 desconectar
	var $iddleTime = 0;//TODO: Desconectar a x cantidad de tiempo sin interaccion
	var $lastAction = null;//En conjunto con $iddleTime checar

    function User($socket, $parent) {
		$this->p = $parent;
		$this->socket = $socket;
        $this->id = md5(uniqid(rand(),true));
        socket_getpeername($socket, $ip);
        $this->ip = $ip;
        $this->status = 1;
    }
}
?>