<?php
require_once('config.inc.php');
$mode = 'all';
$recipeList = $db->getRecipeList('all');

//print "<PRE>";
//print_r($recipeList);

$recipeTitleRows = '';
$lastInitial = '';
foreach ($recipeList as $recipeID=>$recipe) {
	$initial = substr($recipe['name'], 0, 1);
	if ($lastInitial != $initial) $recipeTitleRows .= "<li class=\"sep\">$initial</li>";
	$recipeTitleRows .= <<<TITLEROWEND
		<li class="arrow"><a href="#recipe$recipeID">$recipe[name]</a></li>
TITLEROWEND;
	$lastInitial = $initial;
}

$find = array('/`/', '%(\s*)([\d.]+)/(\d+)(\s)%', "/\n/");
$replace = array('&deg;', '$1<sup>$2</sup>&frasl;<sub>$3</sub>$4', '<br>');
$multi = 1;

$recipeDivs = '';
foreach ($recipeList as $recipeID=>$header) {
	$recipe = $db->getRecipe($recipeID);
	$ingredients = "";

	if (isset($recipe['ingredients'])) {
		$ingredients = "<div class=\"recipeIngredients\"><h1>Ingredients</h1><ul class=\"rounded\">";
		$ii = 1;
		foreach ($recipe['ingredients'] as $ingredient) {
			$int = $ingredient['intQuantity'];
			$num = $ingredient['numerator'];
			$den = $ingredient['denominator'];
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
		$ingredients .= "</div></ul>";
	}
	$instructions = '<div class="recipeInstructions"><h1>Instructions</h1>'.preg_replace($find, $replace, $recipe['instructions']).'</div>';
	$recipeDate = date('M d, Y', $recipe['dateAdded']);
	$recipeDivs .= <<<RECIPEDIVEND
		<div id="recipe$recipeID">
			<div class="toolbar">
				<h1>Recipe Detail</h1>
				<a href="#" class="back">Back</a>
			</div>
			<div class="recipeName">$recipe[name]</div>
			<div class="recipeDescription">$recipe[description]</div>
			$ingredients
			$instructions
			<div class="info">
				Recipe submitted on $recipeDate by {$header['displayName']}
			</div>
		</div>
RECIPEDIVEND;
}
			//<div class="recipeIngredients">$ingredients</div>
			//<div class="recipeInstructions">$instructions</div>


print <<<PAGEEND
<html>
    <head>
        <meta charset="UTF-8" />
        <title>Recipes</title>
        <style type="text/css" media="screen">@import "../../jqtouch/jqtouch.min.css";</style>
        <style type="text/css" media="screen">@import "../../themes/jqt/theme.min.css";</style>
        <script src="../../jqtouch/jquery.1.3.2.min.js" type="text/javascript" charset="utf-8"></script>
        <script src="../../jqtouch/jqtouch.min.js" type="application/x-javascript" charset="utf-8"></script>
        <script type="text/javascript" charset="utf-8">
            var jQT = new $.jQTouch({
                icon: 'jqtouch.png',
                addGlossToIcon: false,
                startupScreen: 'jqt_startup.png',
                statusBar: 'black',
                preloadImages: [
                    '../../themes/jqt/img/back_button.png',
                    '../../themes/jqt/img/back_button_clicked.png',
                    '../../themes/jqt/img/button_clicked.png',
                    '../../themes/jqt/img/grayButton.png',
                    '../../themes/jqt/img/whiteButton.png',
                    '../../themes/jqt/img/loading.gif'
                    ]
            });
        </script>
        <style type="text/css" media="screen">
            body.fullscreen #home .info {
                display: none;
            }
            #about {
                padding: 100px 10px 40px;
                text-shadow: rgba(255, 255, 255, 0.3) 0px -1px 0;
                font-size: 13px;
                text-align: center;
                background: #161618;
            }
            #about p {
                margin-bottom: 8px;
            }
            #about a {
                color: #fff;
                font-weight: bold;
                text-decoration: none;
            }
			.recipeName {
				font-size: 16pt;
				text-align: center;
				padding: 5px;
				margin-bottom: 5px;
				border-bottom: 1px solid rgba(255,255,255,.2);
				font-weight: bold;
			}
			.recipeDescription {
				font-size: 10pt;
				padding: 5px 10px;
				margin-bottom: 5px;
				border-bottom: 1px solid rgba(255,255,255,.2);
			}
			.recipeInstructions {
				font-size: 12pt;
				padding: 5px 15px;
			}
			.recipeIngredients h1, .recipeInstructions h1 {
				color: #989898;
				font-size: 11pt;
				text-align: center;
				font-weight: normal;
				text-decoration: underline;
			}
			.recipeIngredients ul li {
				font-size: 10pt;
				color: #fff;
				padding: 5px;
			}
			ul {
				margin-bottom: 0;
			}
        </style>
    </head>
    <body>
        <div id="about" class="selectable">
                <p><img src="jqtouch.png" /></p>
                <p><strong>FCOD Recipes</strong><br />Version 1.0<br />By Chris Barranco</p>
                <p><br /><br /><a href="#" class="grayButton goback">Close</a></p>
        </div>
        <div id="home" class="current">
            <div class="toolbar">
                <h1>Recipes</h1>
                <a class="button slideup" id="infoButton" href="#about">About</a>
            </div>
            <ul class="plastic">
				$recipeTitleRows
			</ul>
		</div>
		$recipeDivs
PAGEEND;

/*
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
}*/
?>
