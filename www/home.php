<?php
$page['title'] = 'Home Page';
$count = $db->getRecipeCount();
require_once('header.inc.php');
print <<<HOMEPAGEEND
	<h2>Welcome to RecipeWeb!</h2>
	This site is a work in progress and you may experience bugs while using it.
	However, I am backing up the database regularly so don't be afraid of putting in your recipes.
	I designed it to be very plain and simple to use, but if you need help let me know at 
	(<a href="mailto:chris.barranco@gmail.com">chris.barranco@gmail.com</a>). Currently, there are $count recipes in the database, but I am adding more recipes every day!
HOMEPAGEEND;
require_once('footer.inc.php');
?>