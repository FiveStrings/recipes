<?php
$_SESSION = array();
setcookie('RecipeWebUserName', '', time()-42000);
setcookie('RecipeWebPassword', '', time()-42000);
if (isset($_COOKIE[session_name()])) setcookie(session_name(), '', time()-42000, '/');
session_destroy();
$page['title'] = 'Logged Out';
require_once('header.inc.php');
print <<<LOGOUTPAGEEND
<h1>You are now logged out.</h1>
<a href="?p=login">Log in again</a>
LOGOUTPAGEEND;
require_once('footer.inc.php');
?>
