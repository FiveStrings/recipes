<?php
$page['title'] = 'User Profile';

$changesSaved = '';
if (isset($_POST['password'])) {
	$return = $db->updateUser($_POST);
	if ($return['success'] === true) {
		$_SESSION['recipe']['user'] = $return['info'];
		$changesSaved = 'Your changes have been saved.';
	}
	else if ($return['success'] === false) $changesSaved = '<span class="bold red">Invalid password!.</span>';
	else if ($return['success'] === -1) $changesSaved = '<span class="bold red">New passwords do not match!.</span>';
}

require_once('header.inc.php');
print <<<USERPAGEEND
	$changesSaved
	<form method="post">
		<input type="hidden" name="username" value="{$_SESSION['recipe']['user']['username']}">
		<table>
			<tr>
				<td colspan="2">If you want to change your user information, you must first enter your current password.</td>
			</tr><tr>
				<td>Current Password:</td>
				<td><input type="password" name="password" maxLength="16"></td>
			</tr><tr>
				<td>Displayed Name:</td>
				<td><input type="text" name="displayName" size="50" maxLength="16" value="{$_SESSION['recipe']['user']['displayName']}"></td>
			</tr><tr>
				<td>E-Mail Address:</td>
				<td><input type="text" name="emailAddress" maxLength="255" size="50" value="{$_SESSION['recipe']['user']['email']}"></td>
			</tr><tr>
				<td colspan="2">To change your password, fill in the next two fields.</td>
			</tr><tr>
				<td>New Password:</td>
				<td><input type="password" name="newPassword"></td>
			</tr><tr>
				<td>Verify New Password:</td>
				<td><input type="password" name="newPassword2"></td>
			</tr><tr>
				<td colspan="2"><input type="submit" value="Save Changes"></td>
			</tr>
		</table>
		<br><br>
		<a href="?p=list&mode=user&userID={$_SESSION['recipe']['user']['userID']}">See all recipes you've submitted</a>
USERPAGEEND;
require_once('footer.inc.php');
?>
