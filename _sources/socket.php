<?php
error_reporting(E_ALL);
set_time_limit(0);
ob_implicit_flush();

include "Classes/SocketServer.php";
$chat = new SocketServer("192.168.2.2", 65500);
?> 