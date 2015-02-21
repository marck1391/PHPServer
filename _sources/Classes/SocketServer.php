<?php
class SocketServer{
	var $ip;
	var $port;
	var $masterSocket;
    
	private $currentSockets;
    
	function init($ip, $port){
		$this->ip = $ip;
		$this->port = $port;
		
		$this->initSocket();
        echo "Servidor iniciado con exito...\n"
		$this->currentSockets = array();
		
		$this->currentSockets[] = $this->masterSocket;	
		echo "Esperando conexion...\n"
		$this->listenToSockets();
	}
	
	private function initSocket(){
		//---- Start Socket creation for PHP 5 Socket Server -------------------------------------
        if (($this->masterSocket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) < 0) {
            echo "socket_create() failed, reason: " . socket_strerror($this->masterSocket) . "\n";
        }
        socket_set_option($master, SOL_SOCKET,SO_REUSEADDR, 1);
        if (($ret = socket_bind($this->masterSocket, '192.168.2.2', 65500)) < 0) {
            echo "socket_bind() failed, reason: " . socket_strerror($ret) . "\n";
        }
        if (($ret = socket_listen($this->masterSocket, 5)) < 0) {
            echo "socket_listen() failed, reason: " . socket_strerror($ret) . "\n";
        }
	}
    
    public function endSocket(){
        socket_close($this->currentSockets);
        socket_close($this->masterSocket);
    }
    
    public function sendMessage($sockets, $socket, $message){
		//$message .= "\0";
        array_shift($sockets);
		if(!is_array($sockets))
			$sockets = array($sockets);

        foreach($sockets as $client) {
            if($client === NULL)
				continue;
            socket_write($client, "$socket wrote: $message.");
            echo "Escribiendo datos Cliente: $client.\n";
        }
	}
    
    private function listenToSockets(){
        while (true) {
            $changed_sockets = $this->$currentSockets;
            $num_changed_sockets = socket_select($changed_sockets, $write = NULL, $except = NULL, NULL);
    
            foreach($changed_sockets as $socket) {
                if ($socket == $this->masterSocket) {
                    if (($client = socket_accept($this->masterSocket)) < 0) {
                        echo "socket_accept() failed: reason: " . socket_strerror($msgsock) . "\n";
                        continue;
                    } else {
                        array_push($this->currentSockets, $client);
                        socket_getpeername($client, $newClientAddress);
                        socket_write($client, '<cross-domain-policy><allow-access-from domain="*" to-ports="*"/></cross-domain-policy>'.chr(0x00));
                        socket_write($client, "Aceptado. Tu IP e$newClientAddress.<br>", 1024);
                        echo "Conexión aceptada consss: Cliente $client (IP:$newClientAddress)\n";
                    }
                } else {
                    $bytes = @socket_recv($socket, $buffer, 2048, 0);
                    if ($bytes == 0) {
                        $index = array_search($socket, $this->currentSockets);
                        unset($this->currentSockets[$index]);
                        socket_close($socket);
                        echo "Conexion cerrada con: ".$socket."\n";
                    }elseif($bytes!=0&$buffer=="<policy-file-request/>".chr(0x00)){
                        echo "Policy file request from: ($client)\n";
                    } else {
                        echo "Recibiendo datos de: ".$socket."\n";
                        $this->sendMessage($allclients, $socket, $buffer);
                        $buffer = ereg_replace("[ \t\r]","",$buffer);
                        //exec($buffer);
                    }
                }
            }
        }
    }

?>