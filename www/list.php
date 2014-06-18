<?php
$mode = 'all';
if (isset($_GET['mode'])) $mode = $_GET['mode'];
switch ($mode) {
	case 'all':
		$page['title'] = 'Recipe List';
		$recipeList = $db->getRecipeList('all');
		break;
	case 'category':
		$catName = ucwords($db->getCategoryName($_GET['categoryID']));
		$recipeList = $db->getRecipeList('category', $_GET['categoryID']);
		$page['title'] = "Recipes for Category: $catName";
		break;
	case 'user':
		$displayName = $db->getUserDisplayName($_GET['userID']);
		$recipeList = $db->getRecipeList('user', $_GET['userID']);
		$page['title'] = "Recipes submitted by: $displayName";
		break;
	case 'search':
		$page['title'] = "Search for '$_GET[search]'";
		$recipeList = $db->getRecipeList('search', $_GET['search']);
		break;
}

if (count($recipeList))  {

	$recipes = '<table cellspacing="0" cellpadding="0" id="recipeList">';
	$recipes .= '<tr class="quickJump"><td colspan="3"><!--QUICKJUMP--></td></tr>';
	$recipes .= '<tr class="headerRow"><td>Recipe</td><td>Description</td><td>Submitted By</td></tr>';
	$letters = array();
	$lastLetter = false;
	$ii = 0;
	foreach ($recipeList as $recipeID=>$recipe) {
		$recipe['description'] = str_replace("\'", "'", $recipe['description']);
		$recipe['name'] = str_replace("\'", "'", $recipe['name']);
		$letter = substr($recipe['name'], 0, 1);
		if ($lastLetter != $letter) {
			$recipes .= "<tr class=\"sectionHeader\"><td colspan=\"3\"><a name=\"$letter\">$letter</td></tr>";
			$letters[] = $letter;
			$lastLetter = $letter;
			$ii = 0;
		}

		if ($_SESSION['recipe']['user']['userID'] == $recipe['userID']) $recipe['displayName'] = 'You';
		$userCell = "";
		//$class = $ii > 0 ? 'bottomBorder' : '';
		$recipes .= "<tr class=\"recipe\"><td><a href=\"?p=recipe&recipeID=$recipeID\">$recipe[name]</a></td><td class=\"smallFont\">$recipe[description]</td><td><a href=\"?p=list&mode=user&userID=$recipe[userID]\">$recipe[displayName]</a></td></tr>";
		$ii++;
	}
	$recipes .= '</table>';

	$letterLinks = 'Quick Jump: ';
	$delim = '';
	foreach ($letters as $letter) {
		$letterLinks .= "$delim<a href=\"#$letter\">$letter</a>";
		$delim = '&nbsp;&nbsp;';
	}

	$recipes = str_replace('<!--QUICKJUMP-->', $letterLinks, $recipes);
} else {
	$recipes = 'No recipes were found!';
}


require_once('header.inc.php');
print $recipes;
require_once('footer.inc.php');
?>
