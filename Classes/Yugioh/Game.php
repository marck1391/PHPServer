<?php
class Game{
	var $id;
	var $players = array();
	var $status;

	function Game($id){
		$this->id = $id;
		$status = 0;
	}
}
?>