<?php
$loginMessage = '';
if ((isset($_COOKIE['RecipeWebUserName']) && isset($_COOKIE['RecipeWebPassword'])) || (isset($_POST['formAction']) && $_POST['formAction'] == 'login')) {
	if (isset($_COOKIE['RecipeWebUserName'])) $_POST['username'] = $_COOKIE['RecipeWebUserName'];
	if (isset($_COOKIE['RecipeWebPassword'])) $_POST['password'] = $_COOKIE['RecipeWebPassword'];
	$userInfo = $db->login($_POST['username'], $_POST['password']);
	if ($userInfo['valid']) {
		$_SESSION['recipe']['user'] = $userInfo['info'];
		if (isset($_POST['rememberMe'])) {
			setcookie('RecipeWebUserName', $userInfo['info']['username'], time()+2592000);
			setcookie('RecipeWebPassword', $userInfo['info']['password'], time()+2592000);
		}
		$url = '?p=home';
		if (isset($_SESSION['redirectAfterLogin'])) $url = $_SESSION['redirectAfterLogin'];
		header("Location: $url");
		exit;
	}
	$loginMessage = '<span class="red">Invalid user name or password!</span>';
}

$page['title'] = 'Login';
require_once('header.inc.php');

print <<<LOGINPAGEEND
<form method="post">
<input type="hidden" name="formAction" value="login">
$loginMessage
<table>
	<tr>
		<td colspan="2">Login:</td>
	</tr>
	<tr>
		<td>Username:</td>
		<td><input type="text" name="username" maxlength="16"></td>
	</tr>
	<tr>
		<td>Password:</td>
		<td><input type="password" name="password" maxlength="16"></td>
	</tr>
	<tr>
		<td colspan="2" class="textRight"><input type="checkbox" name="rememberMe"> Remember Me</td>
	</tr>
	<tr>
		<td colspan="2" class="textRight"><input type="submit" value="Login"></td>
	</tr>
</table>
<a href="?p=signup">Create an account</a><br><br>
If you have forgotten your password, you're a shmuck.  Email Christopher and he might help you.
LOGINPAGEEND;

require_once('footer.inc.php');
?>
