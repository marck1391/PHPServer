<?Php
class Service extends SocketServer{
	var	$p;
	function Service($parent){
		$this->p = $parent;
	}
	
	function onOpen(){
		$this->p->log("event: Function from service");//Al primer intento tiene que recibir datos de cuenta de usuario
	}
	function onData($data){}
	function onClose(){}
}
?>