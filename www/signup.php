<?php

$error = '';
if (isset($_POST['captcha_code'])) {
	require_once($_SERVER['DOCUMENT_ROOT'] . '/securimage/securimage.php');
	$si = new Securimage();
	if ($si->check($_POST['captcha_code']) === true) {
		if ($_POST['password'] == $_POST['password2']) {
			if ($db->addUser($_POST) !== false) {
				emailAdmin('New User!', "New user info:\nUsername: $_POST[username]\nDisplayName: $_POST[displayName]\nEmail: $_POST[email]");
				setcookie('RecipeWebUserName', $_POST['username'], time()+60);
				setcookie('RecipeWebPassword', md5($_POST['password']), time()+60);
				header('Location: ?p=login&create=true');
			} else {
				$error = '<div class="red bold">That login name is already taken! Try again!</div>';
				$_POST['username'] = '';
			}
		} else {
			$error = '<div class="red bold">Passwords entered do not match! Try again!</div>';
			$_POST['password'] = '';
			$_POST['password2'] = '';
		}
	} else $error = '<div class="red bold">You didn\'t type the characters in correctly! Try again!</div>';
}

$defaults = array(
	'username' => '',
	'displayName' => '',
	'email' => '',
	'password' => '',
	'password2' => '',
	);
$formValues = array();
foreach ($defaults as $name=>$value) $formValues[$name] = isset($_POST[$name]) ? $_POST[$name] : $value;

$page['title'] = 'Create Account';

require_once('header.inc.php');
print <<<SIGNUPEND
	<form method="post">
		$error;
		<table id="recipeTable">
			<tr>
				<td>Desired Login Name:<br><div class="smallFont">What you type to login</div></td>
				<td><input type="text" size="20" maxlength="16" name="username" value="$formValues[username]"></td>
			</tr><tr>
				<td>Password:<div class="smallFont">Also what you type to login</div></td>
				<td><input type="password" size="20" maxlength="16" name="password" value="$formValues[password]"></td>
			</tr><tr>
				<td>Verify Password:<div class="smallFont">If I need to explain this, you should get off the internet</div></td>
				<td><input type="password" size="20" maxlength="16" name="password2" value="$formValues[password2]"></td>
			</tr><tr>
				<td>Display Name<div class="smallFont">When you post stuff, everyone else sees your Display Name</div></td>
				<td><input type="text" size="40" maxlength="128" name="displayName" value="$formValues[displayName]"></td>
			</tr><tr>
				<td>Email address<div class="smallFont">We send you lots of spam... after all, this is a recipe website</div></td>
				<td><input type="text" size="60" maxlength="255" name="email" value="$formValues[email]"></td>
			</tr><tr>
				<td>Type the characters as you see them:<br><img id="captcha" src="/securimage/securimage_show.php"><br><a href="#" onclick="document.getElementById('captcha').src = '/securimage/securimage_show.php?' + Math.random(); return false;">I can't see them!</a></td>
				<td><input type="text" name="captcha_code" size="10" maxlength="6"></td>
			</tr><tr>
				<td colspan="2"><input type="submit" value="Create Account!"></td>
			</tr>
		</table>
	</form>
SIGNUPEND;
require_once('footer.inc.php');
?>