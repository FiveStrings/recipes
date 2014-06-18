<?php
$page['title'] = 'Add New Recipe';

$error = '';

if (isset($_POST['formAction']) && $_POST['formAction'] == 'editSubmit') {
	if (strlen($_POST['recipeName']) == 0 || strlen($_POST['instructions']) == 0) {
		$error = '<div class="red">You must provide a name and instructions.</div>';
	} else {
		$_POST['picture'] = isset($_FILES['picture']) ? $_FILES['picture'] : false;
		$db->updateRecipe($_POST);
		header("Location: ?p=recipe&recipeID=$_POST[recipeID]");
		exit();
	}
}
if (isset($_POST['formAction']) && $_POST['formAction'] == 'submit') {
	if (strlen($_POST['recipeName']) == 0 || strlen($_POST['instructions']) == 0) {
		$error = '<div class="red">You must provide a name and instructions.</div>';
	} else {
		$_POST['picture'] = isset($_FILES['picture']) ? $_FILES['picture'] : false;
		$recipeID = $db->addRecipe($_SESSION['recipe']['user']['userID'], $_POST);
		emailAdmin('New Recipe Added!', "A new recipe has been added!\n\nRecipe: $_POST[recipeName]\n\nLink: http://recipe.flyingcowofdoom.com/?p=recipe&recipeID=$recipeID");
		header('Location: ?p=add&success='.urlencode($_POST['recipeName']));
		exit();
	}
}

if (isset($_POST['recipeID']) && isset($_POST['formAction']) && $_POST['formAction'] == 'edit') {
	$formAction = 'editSubmit';
	$recipeID = $_POST['recipeID'];
	$buttonText = 'Save Changes';
	$picTip = 'To change the picture, upload a new one here. Otherwise, leave this box alone. Please try to keep the image size below 500x500 pixels.';
	$existPicture = $_POST['pictureID'] > 0 ? "Existing picture: <input type=\"hidden\" name=\"pictureID\" value=\"$_POST[pictureID]\"><img width=\"50\" src=\"?p=image&pictureID=$_POST[pictureID]\"><br>Check here to delete existing pic: <input type=\"checkbox\" name=\"deletePicture\">" : '';
} else {
	$formAction = 'submit';
	$recipeID = '';
	$buttonText = 'Add Recipe';
	$picTip = 'You can upload a picture for your recipe here. Please try to keep the image size below 500x500 pixels.';
	$existPicture = '';
}

$ingredientCount = isset($_POST['ingredientCount']) ? $_POST['ingredientCount'] : 10;
$ingredientRows = '';
for ($ii = 1; $ii <= $ingredientCount; $ii++) {
	$intQty = (isset($_POST['intQty'][$ii])) ? $_POST['intQty'][$ii] : '';
	$numQty = (isset($_POST['numQty'][$ii])) ? $_POST['numQty'][$ii] : '';
	$denQty = (isset($_POST['denQty'][$ii])) ? $_POST['denQty'][$ii] : '';
	$ingredient = (isset($_POST['ingredient'][$ii])) ? $_POST['ingredient'][$ii] : '';
	$ingredientRows .= "<tr><td>$ii</td><td><input type=\"text\" name=\"intQty[$ii]\" maxlength=\"4\" size=\"2\" value=\"$intQty\"> <input type=\"text\" name=\"numQty[$ii]\" maxlength=\"2\" size=\"2\" value=\"$numQty\"> / <input type=\"text\" name=\"denQty[$ii]\" maxlength=\"2\" size=\"2\" value=\"$denQty\"></td><td><input type=\"text\" name=\"ingredient[$ii]\" size=\"30\" maxlength=\"128\" value=\"$ingredient\"></td></tr>";
}

$categorySelect = '';
$categories = $db->getCategoryList();
if (count($categories)) {
	$categorySelect = '<br><select onchange="addCategory(this.options[this.selectedIndex].value);" id="availableCategories"><option value=\"\">Add From Existing Category</option>';
	foreach ($categories as $catID=>$cat) $categorySelect .= "<option value=\"$cat[name]\">$cat[name]</option>";
	$categorySelect .= '</select>';
}

$formValues = array(
	'recipeName' => '',
	'description' => '',
	'categories' => '',
	'instructions' => '',
	//'notes' => '',
	);
foreach ($formValues as $value=>$default) $formValues[$value] = (isset($_POST[$value])) ? $db->unquote($_POST[$value]) : $default;

$page['js'] = <<<JSEND
	function addCategory(cat) {
		var delim = '';
		if (document.getElementById('categories').value.length > 0) {
			delim = ', ';
		}
		document.getElementById('categories').value = document.getElementById('categories').value + delim + cat;
	}

	function addMoreIngredients() {
		document.getElementById('formAction').value = 'addMore';
		document.getElementById('ingredientCount').value = parseInt(document.getElementById('ingredientCount').value) + 5;
		document.getElementById('addForm').submit();
	}
JSEND;

require_once('header.inc.php');
if (isset($_GET['success'])) print "<div class=\"green bold\">".$db->unquote(urldecode($_GET['success']))." has successfully been added to the database!</div><br>";
print <<<ADDNEWRECIPE
$error
	<form id="addForm" method="post" enctype="multipart/form-data">
		<input type="hidden" id="formAction" name="formAction" value="$formAction">
		<input type="hidden" id="recipeID" name="recipeID" value="$recipeID">
		<input type="hidden" id="ingredientCount" name="ingredientCount" value="$ingredientCount">
		<table id="addRecipeTable" cellspacing="0" cellpadding="0">
			<tr>
				<td>
					<div class="bold">Recipe Name:</div>
					<input type="text" name="recipeName" maxLength="255" size="50" value="$formValues[recipeName]">
				</td>
				<td class="smallFont">Look for helpful tips in this column!</td>
			</tr><tr>
				<td>
					<div class="bold">Description:</div>
					<textarea name="description" rows="3" cols="50">$formValues[description]</textarea>
				</td>
				<td class="smallFont">Type brief description of the finished product in here.</td>
			</tr><tr>
				<td>
					<div class="bold">Upload Picture:</div>
					<input type="file" name="picture">
					$existPicture
				</td>
				<td class="smallFont">$picTip</td>
			</tr><tr>
				<td>
					<div class="bold">Categories (separate multiple categories with commas):</div>
					<input type="text" name="categories" id="categories" maxLength="255" size="50" value="$formValues[categories]">
					$categorySelect
				</td>
				<td class="smallFont">You can choose from the list of existing categories or you can just type in your own. New categories will be created automatically!</td>
			</tr><tr>
				<td>
					<div class="bold">Ingredients:</div>
					<table class="noBorder" cellpadding="0" cellspacing="0">
						<tr class="headerRow">
							<td class="textCenter">#</td>
							<td class="textCenter" colspan="1">Quantity</td>
							<td class="textCenter">Ingredient</td>
						</tr>
						$ingredientRows
						<tr>
							<td colspan="3"><a href="#" onclick="addMoreIngredients();">Add more rows...</a></td>
					</table>
					<td class="smallFont">The quantity boxes represent a whole number and a fraction.  For a whole number quantity, like "1 egg" just put "1" in the first box.  For a fractional quantity, like "1/2 tsp" put "1" in the second box and "3" in the third box.  For a mixed quantity, like "2 3/4 cups", put "2" in the first box, "3" in the second, and "4" in the third. Always put the unit of measurement and the actual ingredient in the large, "ingredient" box.</td>
				</td>
			</tr><tr>
				<td>
					<div class="bold">Instructions:</div>
					<textarea name="instructions" rows="6" cols="50">$formValues[instructions]</textarea>
				</td>
				<td class="smallFont">Type in the steps one needs to follow to prepare the dish here.<br><br>If you want to type the degree symbol (as in 300&deg;F), type in a ` character. That's the key one to the left of the number 1, called a back-tick. It will be replaced with the degree symbol. Also, if you need to type a fraction, type it as in 1/2 with no spaces. It will automatically be converted to a fraction.</div>
			</tr><tr>
				<td colspan="2">
					<input type="submit" value="$buttonText"> <input type="button" value="Cancel" onclick="history.go(-1);">
				</td>
			</tr>
		</table>
ADDNEWRECIPE;
require_once('footer.inc.php');
?>
