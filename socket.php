<?php
error_reporting(E_ALL);
set_time_limit(0);
ob_implicit_flush();
include "Classes/SocketServer.php";

define("P_COLOR", "\033[1;35m");//Purpura
define("RST_COLOR", "\033[1;0m");//Reset


$chat = new SocketServer("127.0.0.1", 65500);
?> 