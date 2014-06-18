<?php
$page['title'] = 'Category List';

$cats = $db->getCategoryList();

$output = '<table cellspacing="0" cellpadding="0" id="recipeList">';
$letters = array();
$lastLetter = false;
foreach ($cats as $catID=>$cat) {
	$cat['name'] = ucwords($cat['name']);
	$letter = substr($cat['name'], 0, 1);
	if ($lastLetter != $letter) {
		$output .= "<tr><td class=\"sectionHeader\"><a name=\"$letter\">$letter</td></tr>";
		$letters[] = $letter;
		$lastLetter = $letter;
	}
	$output .= "<tr><td class=\"recipe\"><a href=\"?p=list&mode=category&categoryID=$catID\">$cat[name]</a> - $cat[count] recipes</td></tr>";
}
$output .= '</table>';

$letterLinks = '';
$delim = '';
foreach ($letters as $letter) {
	$letterLinks .= "$delim<a href=\"#$letter\">$letter</a>";
	$delim = '&nbsp;&nbsp;';
}


require_once('header.inc.php');
print "$letterLinks<hr>$output";
require_once('footer.inc.php');
?>
