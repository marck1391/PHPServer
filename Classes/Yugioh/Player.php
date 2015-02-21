<?php
class Player{
	var $id;
	var $userID;
	var $name;

	var $gameID;
	var $ready = false;
	var $status = 0;

	function Player($id){
		$this->userID = $id;

		$this->gameID = 0;
		$this->ready = false;
	}
}
?>