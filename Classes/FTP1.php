<?Php
class FTP extends SocketServer{
	var	$p;//Parent (Server)

	var $homedir = "";

	function FTP($parent){
		$this->p = $parent;
		$this->p->log(P_COLOR."info: Starting FTP Service".RST_COLOR);
		//$this->cdb = new Sqlite3("Classes/lib/db.cdb");
	}
	
	function cmd($d, $e){
		if($d[1]=="uploadFile"){
			if(sizeof($d)<6){
				return "use > uploadFile|dir|filename|overwrite|filehash|filedata";
			}
			//uploadFile|dir|filename|overwrite|filehash|filedata
			$filedata = str_replace($d[0]."|".$d[1]."|".$d[2]."|".$d[3]."|".$d[4]."|".$d[5]."|", "", str_replace(chr(0), "", $e));
			$hash = md5($filedata);
			$filedata = str_replace("\n", "\r\n", $filedata);
			$filedata = str_replace("\\\\", "\\", $filedata);
			$ow = false;

			if($d[4]=="true"){
				$ow = true;
			}

			if(getcwd()!=$d[2]){
				echo "Cambiando directorio.\n";
				chdir($d[2]);
			}

			if(file_exists($d[3])){
				if(!$ow)
					return "errorFileExists";
			}
			if($d[5]==$hash){
				$f = fopen($d[3], "w");
				fwrite($f, $filedata);
				fclose($f);
				return "done";
			}else
				return "errorHash";
			
		}else if($d[1]=="downloadFile"){
			//downloadFile|name
			$name = $d[2];
			if(sizeof($d)<3){
				return "use > downloadFile|name";
			}

			if(!file_exists($name)){
					return "errorFileNotFound";
			}
			$f = file_get_contents($name);
			return "savefile|$name|$f";

		}else if($d[1]=="readfile"){
			//downloadFile|name
			$name = $d[2];
			if(sizeof($d)<3){
				return "use > readfile|name";
			}

			if(!file_exists($name)){
					return "errorFileNotFound";
			}
			$f = file_get_contents($name);
			$f = str_replace("\n", "\r\n", $f);
			return "showfile|$name|$f";

		}else if($d[1]=="getcwd"){
			return "cd|".getcwd();
		}else if($d[1]=="setcwd"){
			if(chdir($d[2])){
				return "cd|".getcwd();
			}else
				return "cderror";
			
		}else if($d[1]=="ls"){
			$list = scandir($d[2]);
			$dirs = "";
			$files = "";
			for ($i=2; $i < sizeof($list); $i++) { 
				if(is_dir($list[$i])){
					$dirs .= $list[$i].",";
				}else{
					$files .= $list[$i].",";
				}
			}
			$dirs = substr($dirs, 0, -1);
			$files = substr($files, 0, -1);
			return "ls|$dirs|$files";
		}else if($d[1]=="getHashData"){
			return md5($d[2]);
		}else{
			return "commandNotFound";
		}
	}

	function stop(){
		$this->p->log(P_COLOR."info: Stoping FTP Service".RST_COLOR);
		$this->p = null;
	}
}
?>