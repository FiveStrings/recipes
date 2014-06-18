<?php
require_once('config.inc.php');
$file = isset($_GET['p']) ? $_GET['p'] : 'home';
if (file_exists("$file.php")) {
	require_once("$file.php");
	exit();
}
?>
