<?php
$page['title'] = 'Admin';

$message = '';

if (isset($_POST['mode'])) {
	switch ($_POST['mode']) {
		case 'addUser': 
			$db->addUser($_POST);
			$message = 'User added successfully!<hr>';
			break;
	}
}

require_once('header.inc.php');
print <<<ADMINPAGEEND
$message
<h2>Add User</h2>
<form method="post">
	<input type="hidden" name="mode" value="addUser">
	<table>
		<tr>
			<td>Username:</td>
			<td><input type="text" name="username" size="20" maxLength="16"></td>
		</tr><tr>
			<td>Password:</td>
			<td><input type="text" name="password" size="20" maxLength="16"></td>
		</tr><tr>
			<td>Display Name:</td>
			<td><input type="text" name="displayName" size="30" maxLength="128"></td>
		</tr><tr>
			<td>Email:</td>
			<td><input type="text" name="email" size="50" maxLength="255"></td>
		</tr><tr>
			<td>Admin:</td>
			<td><input type="checkbox" name="admin"></td>
		</tr><tr>
			<td colspan="2"><input type="submit" value="Add"></td>
		</tr>
	</table>
</form>
ADMINPAGEEND;
require_once('header.inc.php');
?>
