<?php
//error_reporting(E_ALL);
function request($socket, $msg){
	@socket_write($socket, $msg, strlen($msg));
	while ($out = socket_read($socket, 20480)) {
	    return $out;
	}
}
//ip:port cmd filename localdir remotedir
//Upload
//ip:port upload FILENAME localdir serverdir
if($argc >= 4){
		$a = explode(":", $argv[1]);
		$service_port = $a[1];
		$address = $a[0];
		$cmd = $argv[2];
		$filename = $argv[3];
		$dir = $argv[4];
		$rdir = $argv[5];
}
/* Crear un socket TCP/IP. */
$socket = @socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
if ($socket === false) {
    echo "socketCreateError";
    exit;
}
//Conectar
$result = @socket_connect($socket, $address, $service_port);
if ($result === false) {
    echo "socketConnectError";
    exit;
}

$res = request($socket, "login|M4rk|544544");
if($res!="loggedIn".chr(0)){
	echo "loginError";
	socket_close($socket);
	exit;
}else{
	if($cmd=="download"){
		if(@file_exists($dir)){
			if(@chdir($dir)){
				if(!file_exists($filename)){
					$res = request($socket, "ftp|downloadFile|$filename");
					$resp = explode("|", $res);
					if($resp[0]=="savefile"){
						$rhash = $resp[1];
						$filedata = str_replace($resp[0]."|".$resp[1]."|", "", $res);
						$filedata = str_replace(chr(0), "", $filedata);
						$hash = md5($filedata);
						if($rhash==$hash){
							$fh = fopen($filename,"w");
							fwrite($fh, $filedata);
							fclose($fh);
							echo "done";
							socket_close($socket);
							exit;
						}else{
							echo "hashIncorrecto";
							socket_close($socket);
							exit;
						}
					}else{
						echo $res;
						socket_close($socket);
						exit;
					}
				}else{
					echo "fileExists";
					socket_close($socket);
					exit;
				}
			}else{
				echo "cantAccessFolder";
				socket_close($socket);
				exit;
			}
		}else{
			echo "folderNotFound";
			socket_close($socket);
			exit;
		}
	}else if($cmd=="upload"){
		//uploadFile|dir|filename|overwrite|filehash|filedata
		if(@file_exists($dir)){
			if(@chdir($dir)){
				if(@file_exists($filename)){
					$filedata = @file_get_contents($dir.$filename);
					$hash = md5($filedata);
					$req = "ftp|uploadFile|$rdir|$filename|false|$hash|$filedata";
				}else{
					echo "fileNotFound";
					socket_close($socket);
					exit;
				}
			}else{
				echo "cantAccessFolder";
				socket_close($socket);
				exit;
			}
		}else{
			echo "folderNotFound";
			socket_close($socket);
			exit;
		}
	}else{
		echo "invalidCommand";
		socket_close($socket);
		exit;
	}
}

$res = request($socket, $req);
echo $res;
socket_close($socket);
?>