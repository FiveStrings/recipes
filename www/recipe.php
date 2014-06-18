<?php
$redirect = false;
if (isset($_POST['comment'])) {
	$db->addComment($_POST);
	$redirect = true;
}
if (isset($_GET['deleteCommentID'])) {
	$db->deleteComment($_GET['deleteCommentID']);
	$redirect = true;
}
if (isset($_GET['deleteRecipeID'])) {
	if ($_SESSION['recipe']['user']['admin'] || $_SESSION['recipe']['user']['userID'] == $db->getRecipeCreatorID($_GET['deleteRecipeID'])) {
		$db->deleteRecipe($_GET['deleteRecipeID']);
		require_once('header.inc.php');
		print "Recipe has been deleted.";
		require_once('footer.inc.php');
		exit;
	} else {
		require_once('header.inc.php');
		print "You do not have permission to delete this recipe.";
		require_once('footer.inc.php');
		exit;
	}
}

if (!isset($_GET['recipeID'])) {
	header('Location: ?p=home');
	exit;
}
if ($redirect) {
	header("Location: ?p=recipe&recipeID=$_GET[recipeID]");
	exit;
}

$recipe = $db->getRecipe($_GET['recipeID']);
if (count($recipe) == 0) {
	header('Location: ?p=home');
	exit;
}

$find = array('/`/', '%(\s*)([\d.]+)/(\d+)(\s)%', "/\n/");
$replace = array('&deg;', '$1<sup>$2</sup>&frasl;<sub>$3</sub>$4', '<br>');

//$recipe['name'] = str_replace("\'", "'", $recipe['name']);
//$recipe['description'] = str_replace("\'", "'", $recipe['description']);

$page['title'] = $recipe['name'];

if (isset($_REQUEST['multi'])) $multi = $_REQUEST['multi'];
else $multi = 1;

if (!isset($recipe['ingredients'])) $ingredients = '';
else {
	$multiBatch = '';
	if ($multi != 1) {
		switch ($multi) {
			case .5:
				$multiBatch = ' (Half Batch)';
				break;
			case 2:
				$multiBatch = ' (Double Batch)';
				break;
			case 3:
				$multiBatch = ' (Triple Batch)';
				break;
			default:
				$multiBatch = " (Multiplied by $multi)";
		}
	}
	$editIngredients = array();
	$ii = 1;
	$ingredients = <<<INGREDIENTSEND
		</tr><tr>
			<td colspan="2">
				<span class="bold">Ingredients$multiBatch:</span>
				<ul>
INGREDIENTSEND;
	foreach ($recipe['ingredients'] as $ingredient) {
		$int = $ingredient['intQuantity'];
		$num = $ingredient['numerator'];
		$den = $ingredient['denominator'];
		$editIngredients .= "<input type=\"hidden\" name=\"intQty[$ii]\" value=\"$int\"><input type=\"hidden\" name=\"numQty[$ii]\" value=\"$num\"><input type=\"hidden\" name=\"denQty[$ii]\" value=\"$den\"><input type=\"hidden\" name=\"ingredient[$ii]\" value=\"$ingredient[name]\">";
		if (isset($num) && isset($den)) {
			$num += $int * $den;
			if ($multi > 1) $num *= $multi;
			else if ($multi < 1) $den *= 1 / $multi;
			$frac = reduce($num, $den);
			$int = $frac[0];
			$num = $frac[1];
			$den = $frac[2];
		} else $int *= $multi;
		$qty = '';
		if ($int) $qty .= $int;
		if ($int && $num) $qty .= ' ';
		if ($num) $qty .= "$num/$den";

		$ingredients .= preg_replace($find, $replace, "<li>$qty $ingredient[name]</li>");
		$ii++;
	}
	$ingredients .= '</ul></td></tr>';
	$ingredients .= <<<BATCHMODIFIERS
		<tr>
			<td colspan="2">
				<span class="bold">Change Quantities:</span><br>
				<form action="?p=recipe&recipeID=$recipe[recipeID]" id="multiForm" method="post">
					<input id="multi" value="$multi" type="hidden" name="multi">
					<a href="#" onclick="document.getElementById('multi').value = .5; document.getElementById('multiForm').submit(); return false;">Half Batch</a> / 
					<a href="#" onclick="document.getElementById('multi').value =  1; document.getElementById('multiForm').submit(); return false;">Normal Batch</a> / 
					<a href="#" onclick="document.getElementById('multi').value =  2; document.getElementById('multiForm').submit(); return false;">Double Batch</a> / 
					<a href="#" onclick="document.getElementById('multi').value =  3; document.getElementById('multiForm').submit(); return false;">Triple Batch</a>
				</form>
			</td>
		</tr>
BATCHMODIFIERS;
}

$recipeDay = date('M d, Y', $recipe['dateAdded']);

$adminLinks = '';
if ($_SESSION['recipe']['user']['admin'] || $_SESSION['recipe']['user']['userID'] == $recipe['userID']) {
	$adminLinks = "<br><a onclick=\"document.getElementById('editForm').submit();\" href=\"#\">Edit this Recipe</a> | <a onclick=\"return confirm('Are you sure you want to delete this recipe?');\" href=\"?p=recipe&deleteRecipeID=$recipe[recipeID]\">Delete this Recipe</a>";
}

if ($_SESSION['recipe']['user']['userID'] == $recipe['user']['userID']) $recipe['user']['displayName'] = 'You';
$recipeUserLink = "<a href=\"?p=list&mode=user&userID={$recipe['user']['userID']}\">{$recipe['user']['displayName']}</a>";

$editCategories = '';
$categories = 'none';
$delim = '';
if (isset($recipe['categories'])) {
	$categories = '';
	foreach ($recipe['categories'] as $catID=>$cat) {
		$categories .= "$delim<a href=\"?p=category&categoryID=$catID\">".ucwords($cat['name'])."</a>";
		$editCategories .= $delim.ucwords($cat['name']);
		$delim = ', ';
	}
}

$recipePicture = $recipe['pictureID'] > 0 ? "<img id=\"recipeImage\" src=\"?p=image&pictureID=$recipe[pictureID]\">" : '';

$instructions = preg_replace($find, $replace, $recipe['instructions']);
$description = preg_replace($find, $replace, $recipe['description']);
$multiWarning = $multi != 1 ? "<div class=\"red\">Warning: Ingredient quantites have been changed. The instructions may not reflect this change. Please adjust as necessary.</div>" : '';

$comments = "<br>There are no comments!<br><br>";
if (count($recipe['comments'])) {
	//$comments = '<table class="commentTable">';
	$comments = '';
	foreach ($recipe['comments'] as $commentID=>$comment) {
		$commentDate = date('M d, Y', $comment['dateAdded']);
		$commentTime = date('h:i:s a', $comment['dateAdded']);
		$deleteLink = $_SESSION['recipe']['user']['admin'] || $_SESSION['recipe']['user']['userID'] == $comment['userID'] ? " / <a href=\"?p=recipe&deleteCommentID=$comment[commentID]&recipeID=$recipe[recipeID]\">Delete Comment</a></div>" : '';
		if ($_SESSION['recipe']['user']['userID'] == $comment['userID']) $comment['displayName'] = 'You';
		$userLink = "<a href=\"?p=list&mode=user&userID=$comment[userID]\">$comment[displayName]</a>";
		$byLine = "<div id=\"commentBy\">$commentDate at $commentTime by $userLink$deleteLink";
		//$commentText = str_replace("\\'", "'", $comment['comment']);
		$comments .= <<<COMMENTEND
			<div id="comment">
				$commentText
				<div id="commentBy">$byLine</div>
			</div>
COMMENTEND;
	}
}

require_once('header.inc.php');
print <<<VIEWRECIPE
	<table id="recipeTable" cellspacing="0" cellpadding="0">
		<tr>
			<td class="italic">
				$description
			</td>
			$ingredients
		</tr><tr>
			<td colspan="2">
				<span class="bold">Instructions:</span> <br>
				$multiWarning
				$instructions
			</td>
		</tr><tr>
			<td colspan="2">
				Recipe submitted on $recipeDay by $recipeUserLink<br>
				Categories: $categories
				$adminLinks
			</td>
		</tr><tr>
			<td colspan="2">
				<span class="bold">Comments:</span><br>
				$comments
				<form method="post">
					Post a comment:<br>
					<textarea name="comment" cols="40" rows="4"></textarea><br>
					<input type="hidden" name="userID" value="{$_SESSION['recipe']['user']['userID']}">
					<input type="hidden" name="recipeID" value="{$recipe['recipeID']}">
					<input type="submit" value="Post Comment">
				</form>
			</td>
		</tr>
	</table>
	$recipePicture
	<form class="hidden" id="editForm" method="post" action="?p=add">
		<input type="hidden" name="formAction" value="edit">
		<input type="hidden" name="recipeID" value="$recipe[recipeID]">
		<input type="hidden" name="recipeName" value="$recipe[name]">
		<input type="hidden" name="description" value="$recipe[description]">
		<input type="hidden" name="categories" value="$editCategories">
		<input type="hidden" name="instructions" value="$recipe[instructions]">
		<input type="hidden" name="pictureID" value="$recipe[pictureID]">
		<input type="hidden" name="ingredientCount" value="$ii">
		$editIngredients
	</form>
VIEWRECIPE;
//print "<pre>".print_r($recipe,true)."</pre>";
require_once('footer.inc.php');
?>
