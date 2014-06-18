<?php
$pageDefaults = array(
	'cssFile' => '',
	'title' => '',
	'menu' => '',
	'js' => '',
	);
foreach ($pageDefaults as $var=>$val) if (!isset($page[$var])) $page[$var] = $val;

if (strlen($page['js'])) $page['js'] = "<script type=\"text/javascript\">$page[js]</script>";

$adminLink = $_SESSION['recipe']['user']['admin'] ? '<a class="menuItem" href="?p=admin">Admin</a>' : '';

if (strlen($page['title'])) $page['inlineTitle'] = ' - '.$page['title'];

if (isset($_SESSION['recipe']['user'])) {
	$page['menu'] = <<<PAGEMENUEND
		<table cellspacing="0" cellpadding="0" id="headerMenu">
			<tr>
				<td>
					<a class="menuItem" href="?p=home">Home</a>
					<a class="menuItem" href="?p=list">All Recipes</a>
					<a class="menuItem" href="?p=list&mode=user&userID={$_SESSION['recipe']['user']['userID']}">My Recipes</a>
					<a class="menuItem" href="?p=categories">Categories</a>
				</td>
			</tr><tr>
				<td>
					<a class="menuItem" href="?p=add">Add Recipe</a>
					<a class="menuItem" href="?p=user">User Profile</a>
					$adminLink
					<a class="menuItem" href="?p=logout">Logout</a>
				</td>
			</tr><tr>
				<td class="padLeft">
					Search: 
					<form name="searchForm action="index.php" method="get">
						<input type="hidden" name="p" value="list">
						<input type="hidden" name="mode" value="search">
						<input type="text" size="10" maxlength="16" name="search">
						<input type="submit" value="Search">
					</form>
				</td>
			</tr><tr>
				<td class="padLeft">
					You are logged in as {$_SESSION['recipe']['user']['displayName']}
				</td>
			</tr>
		</table>
		<hr>
PAGEMENUEND;
}

print <<<HEADERPAGEEND
<!DOCTYPE HTML PUBLIC "-//W3C/DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
	<head>
		<style type="text/css">
			$page[cssFile]
			@import 'css/main.css';
		</style>
		$page[js]
		<title>$page[title]</title>
	</head>
	<body>
	<h1>RecipeWeb$page[inlineTitle]</h1>
	$page[menu]
HEADERPAGEEND;
?>
