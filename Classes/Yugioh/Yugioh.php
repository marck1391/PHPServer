<?Php
include("Yugioh/Game.php");
include("Yugioh/Player.php");
class Yugioh extends SocketServer{
	var	$p;//Parent (Server)
	var $cdb;//Cards DB
	var $pdb;//Player DB

	var $games = array();
	var $players = array();
	var $playersInGame = array();

	function Yugioh($parent){
		$this->p = $parent;
		$this->p->log(P_COLOR."info: Starting Yugioh Service".RST_COLOR);
		$this->cdb = new Sqlite3("Classes/Yugioh/cards_es.cdb");
		$this->pdb = new Sqlite3("Classes/Yugioh/players.db");
	}
	
	function cmd($d){
		if($d[1]=="getPlayers"){
			$dc = $this->pdb->getVars("*", "players");
			$players = "";
			foreach ($dc as $row) {
				$players .= $row["name"].",".$row["id"]."|";
			}
			$game = new Game(sizeof($this->games));
			//$game->player[] = $d[2];
			//$game->player[] = $d[3];
			$this->games[] = $game;
			$gameID = sizeof($this->games)-1;
			return "showPlayers|$gameID|".$players;
		}else if($d[1]=="login"){
			$player = new Player($d[3]);
			$player->gameID = $d[2];

			$this->players[] = $player;

			$playerID = sizeof($this->players)-1;
			$player->id = $playerID;
			$player->status = 1;//loggedin

			$game = $this->games[$d[2]];
			$game->players[] = $player;


			return "logged|".$d[3]."|";
		}else if($d[1]=="userReady"){
		}else if($d[1]=="getPlayerDecks"){
			//2 Player1 id
			$dc = $this->pdb->getVars("*", "decks", "where player_id=".$d[2]);
			$decks = "";
			foreach ($dc as $row) {
				$decks .= $row["name"].",".$row["id"]."|";
			}
			return $decks;
		}
	}

	function stop(){
		$this->p->log(P_COLOR."info: Stoping Yugioh Service".RST_COLOR);
		$this->p = null;
		$this->cdb = null;
		$this->pdb = null;

		$this->games = array();
		$this->players = array();
		$this->playersInGame = array();
	}
}
?>