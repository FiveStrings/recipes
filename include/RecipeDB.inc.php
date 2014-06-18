<?php
require_once('Database.inc.php');

class RecipeDB extends Database {
	public static function singleton($dsn, $options) {
		if (!isset(parent::$instance)) parent::$instance = new RecipeDB($dsn, $options);
		return parent::$instance;
	}

	public function login($username, $password) {
		$userInfo = $this->fetchRow('SELECT * FROM user WHERE username = ? AND (password = MD5(?) OR password = ?)', array($username, $password, $password));
		return array('valid'=>count($userInfo) > 1, 'info'=>$userInfo);
	}

	public function addUser($post) {
		$userTaken = $this->fetchOne('SELECT count(*) FROM user WHERE username = ?', $post['username']);
		if ($userTaken > 0) return false;
		$vals = array(
			'username'=>$post['username'],
			'password'=>md5($post['password']),
			'displayName'=>$post['displayName'],
			'email'=>$post['email'],
			'dateAdded'=>time(),
			'admin'=>isset($post['admin']) ? '1' : '0',
			); 
		return $this->insert('user', $vals);
	}

	public function updateUser($post) {
		$userInfo = $this->login($post['username'], $post['password']);
		if ($userInfo['valid']) {
			$userID = $userInfo['info']['userID'];
			$this->exec('UPDATE user SET displayName = ?, email = ? WHERE userID = ?', array($post['displayName'], $post['emailAddress'], $userID));

			$return = true;
			if (strlen($post['newPassword'])) {
				if ($post['newPassword'] == $post['newPassword2']) {
					$this->exec('UPDATE user SET password = MD5(?) WHERE userID = ?', array($post['newPassword'], $userID));
				} else $return = -1;
			}
			$userInfo = $this->fetchRow('SELECT * FROM user WHERE userID = ?', $userID);
		} else $return = false;
		return array('success'=>$return, 'info'=>$userInfo);
	}

	public function getUserDisplayName($userID) {
		return $this->fetchOne('SELECT displayName FROM user WHERE userID = ?', $userID);
	}

	public function cleanupCategories() {
		$this->exec('DELETE FROM category_recipe_join WHERE recipeID NOT IN (SELECT recipeID FROM recipe)');
		$this->exec('DELETE FROM category WHERE categoryID NOT IN (SELECT categoryID FROM category_recipe_join)');
	}

	public function cleanupPictures() {
		$this->exec('DELETE FROM picture WHERE pictureID NOT IN (SELECT pictureID FROM recipe)');
	}

	public function getCategoryList() {
		$this->cleanupCategories();
		$cats = $this->fetchAll('SELECT *,count(j.recipeID) as `count` FROM category,category_recipe_join j WHERE category.categoryID = j.categoryID GROUP BY category.categoryID ORDER BY category.name');
		$return = array();
		foreach ($cats as $cat) $return[$cat['categoryID']] = $cat;
		return $return;
	}

	public function getCategoryName($categoryID) {
		return $this->fetchOne('SELECT name FROM category WHERE categoryID = ?', $categoryID);
	}

	public function getRecipeList($mode = 'all', $id = false) {
		switch ($mode) {
			case 'all':
				$recipes = $this->fetchAll('SELECT recipe.*, user.displayName from recipe,user WHERE user.userID = recipe.userID ORDER BY recipe.name');
				break;
			case 'category':
				$recipes = $this->fetchAll('SELECT recipe.*, user.displayName from recipe,category_recipe_join j,user WHERE j.categoryID = ? AND recipe.recipeID = j.recipeID AND user.userID = recipe.userID ORDER BY name', $id);
				break;
			case 'user':
				$recipes = $this->fetchAll('SELECT recipe.*, user.displayName from recipe, user WHERE recipe.userID = ? AND user.userID = recipe.userID ORDER BY recipe.name', $id);
				break;
			case 'search':
				$recipes = $this->fetchAll('SELECT recipe.*, user.displayName from recipe, user, ingredient WHERE (recipe.name RLIKE ? OR recipe.description RLIKE ? OR recipe.instructions RLIKE ? OR ingredient.name RLIKE ?) AND ingredient.recipeID = recipe.recipeID AND user.userID = recipe.userID ORDER BY recipe.name', array($id, $id, $id, $id));
				break;
		}
		$return = array();
		foreach ($recipes as $recipe) {
			$recipe['name'] = $this->unquote($recipe['name']);
			$recipe['description'] = $this->unquote($recipe['description']);
			$return[$recipe['recipeID']] = $recipe;
		}
		return $return;
	}

	public function getRecipeCount() {
		return $this->fetchOne('SELECT count(*) FROM recipe');
	}

	public function getRecipe($recipeID) {
		$recipeInfo = $this->fetchRow('SELECT * FROM recipe WHERE recipeID = ?', $recipeID);
		$recipeInfo['name'] = $this->unquote($recipeInfo['name']);
		$recipeInfo['description'] = $this->unquote($recipeInfo['description']);
		$recipeInfo['instructions'] = $this->unquote($recipeInfo['instructions']);

		$ingredients = $this->fetchAll('SELECT * FROM ingredient WHERE recipeID = ?', $recipeID);
		$categories = $this->fetchAll('SELECT * FROM category_recipe_join j, category c WHERE j.recipeID = ? AND c.categoryID = j.categoryID', $recipeID);
		$recipeInfo['user'] = $this->fetchRow('SELECT * FROM user WHERE userID = ?', $recipeInfo['userID']);
		$comments = $this->fetchAll('SELECT comment.*,user.displayName FROM comment,user WHERE comment.recipeID = ? AND user.userID = comment.userID', $recipeID);
		foreach ($ingredients as $ingredient) {
			$ingredient['name'] = $this->unquote($ingredient['name']);
			$recipeInfo['ingredients'][$ingredient['ingredientID']] = $ingredient;
		}

		foreach ($categories as $category) $recipeInfo['categories'][$category['categoryID']] = $category;

		$recipeInfo['comments'] = array();

		foreach ($comments as $comment) {
			$comment['comment'] = $this->unquote($comment['comment']);
			$recipeInfo['comments'][$comment['commentID']] = $comment;
		}

		return $recipeInfo;
	}

	public function addRecipe($userID, $post) {
		$pictureID = isset($_POST['pictureID']) ? $_POST['pictureID'] : '0';
		if ($post['picture']) {
			$file = $post['picture'];
			if (is_uploaded_file($file['tmp_name'])) {
				/*list($width, $height) = getimagesize($file['tmp_name']);
				if ($width > 300 || $height > 300) {
					$ratio = $width / $height;
					$newWidth = $newHeight = 300;
					if ($ratio < 1) $newWidth = 300 * $ratio;
					else $newHeight = 300 / $ratio;

					$newImage = imagecreatetruecolor($newWidth, $newHeight);
					$image = imagecreatefromjpeg($file['tmp_name']);
					imagecopyresampledbicubic($newImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
					imagejpeg($newImage, $file['tmp_name']);
				}*/
				$pictureID = $this->exec('INSERT INTO picture (picture) VALUES (?)', file_get_contents($file['tmp_name']));
			}
		}
		$recipeID = $this->exec('INSERT INTO recipe (name, description, instructions, dateAdded, userID, pictureID) VALUES (?, ?, ?, UNIX_TIMESTAMP(), ?, ?)', array($_POST['recipeName'], $_POST['description'], $_POST['instructions'], $userID, $pictureID));
		$cats = $this->getCategoryList();
		$existingCategories = array();
		foreach ($cats as $catID=>$cat) $existingCategories[$cat['name']] = $catID;
		$categories = explode(', ', $_POST['categories']);
		foreach ($categories as $cat) {
			if (strlen($cat)) {
				$categoryID = (isset($existingCategories[$cat])) ? $existingCategories[$cat] : $this->exec('INSERT INTO category (name) VALUES(?)', strtolower($cat));
				$this->exec('INSERT INTO category_recipe_join (categoryID, recipeID) VALUES (?, ?)', array($categoryID, $recipeID));
			}
		}
		foreach ($_POST['ingredient'] as $id=>$ingredient) {
			if (strlen($ingredient)) {
				$int = isset($_POST['intQty'][$id]) ? $_POST['intQty'][$id] : '';
				$num = isset($_POST['numQty'][$id]) ? $_POST['numQty'][$id] : '';
				$den = isset($_POST['denQty'][$id]) ? $_POST['denQty'][$id] : '';
				$this->exec('INSERT INTO ingredient (recipeID, intQuantity, numerator, denominator, name) VALUES (?, ?, ?, ?, ?)', array($recipeID, $int, $num, $den, $ingredient));
			}
		}
		return $recipeID;
	}

	public function updateRecipe($post) {
		$pictureID = isset($_POST['pictureID']) ? $_POST['pictureID'] : '0';
		$deletePicture = isset($_POST['deletePicture']) && $_POST['deletePicture'] == 'on';
		if (!$deletePicture && $post['picture']) {
			$file = $post['picture'];
			if (is_uploaded_file($file['tmp_name'])) {
				list($width, $height) = getimagesize($file['tmp_name']);
				/*if ($width > 300 || $height > 300) {
					$ratio = $width / $height;
					$newWidth = $newHeight = 300;
					if ($ratio < 1) $newWidth = 300 * $ratio;
					else $newHeight = 300 / $ratio;

					$newImage = imagecreatetruecolor($newWidth, $newHeight);
					$image = imagecreatefromjpeg($file['tmp_name']);
					imagecopyresampled($newImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
					imagejpeg($newImage, $file['tmp_name']);
				}*/
				$pictureID = $this->exec('INSERT INTO picture (picture) VALUES (?)', file_get_contents($file['tmp_name']));
			}
		}
		if ($deletePicture) $pictureID = 0;
		$recipeID = $_POST['recipeID'];
		$this->exec('UPDATE recipe SET name = ?, description = ?, instructions = ?, pictureID = ? WHERE recipeID = ?', array($_POST['recipeName'], $_POST['description'], $_POST['instructions'], $pictureID, $recipeID));
		$this->exec('DELETE FROM category_recipe_join WHERE recipeID = ?', $recipeID);
		$this->cleanupCategories();
		$cats = $this->getCategoryList();
		$existingCategories = array();
		foreach ($cats as $catID=>$cat) $existingCategories[$cat['name']] = $catID;
		$categories = explode(', ', $_POST['categories']);
		foreach ($categories as $cat) {
			$cat = strtolower($cat);
			if (strlen($cat)) {
				$categoryID = (isset($existingCategories[$cat])) ? $existingCategories[$cat] : $this->exec('INSERT INTO category (name) VALUES(?)', strtolower($cat));
				$this->exec('INSERT INTO category_recipe_join (categoryID, recipeID) VALUES (?, ?)', array($categoryID, $recipeID));
			}
		}
		$this->exec('DELETE FROM ingredient WHERE recipeID = ?', $recipeID);
		foreach ($_POST['ingredient'] as $id=>$ingredient) {
			if (strlen($ingredient)) {
				$int = isset($_POST['intQty'][$id]) ? $_POST['intQty'][$id] : '';
				$num = isset($_POST['numQty'][$id]) ? $_POST['numQty'][$id] : '';
				$den = isset($_POST['denQty'][$id]) ? $_POST['denQty'][$id] : '';
				$this->exec('INSERT INTO ingredient (recipeID, intQuantity, numerator, denominator, name) VALUES (?, ?, ?, ?, ?)', array($recipeID, $int, $num, $den, $ingredient));
			}
		}
		//$this->cleanupPictures();
	}

	public function deleteRecipe($recipeID) {
		$this->exec('DELETE FROM recipe WHERE recipeID = ?', $recipeID);
		$this->exec('DELETE FROM ingredient WHERE recipeID = ?', $recipeID);
		$this->exec('DELETE FROM category_recipe_join WHERE recipeID = ?', $recipeID);
		$this->exec('DELETE FROM comment WHERE recipeID = ?', $recipeID);
		$this->cleanupCategories();
		$this->cleanupPictures();
	}

	public function addComment($post) {
		if (strlen($post['comment'])) {
			$this->exec('INSERT INTO comment (userID, recipeID, dateAdded, comment) VALUES (?, ?, UNIX_TIMESTAMP(), ?)', array($post['userID'], $post['recipeID'], $post['comment']));
			return true;
		}
		return false;
	}

	public function deleteComment($commentID) {
		$this->exec('DELETE FROM comment WHERE commentID = ?', $commentID);
	}

	public function getImage($pictureID) {
		return $this->fetchOne('SELECT picture FROM picture WHERE pictureID = ?', $pictureID);
	}

	public function getOption($optionName) {
		return $this->fetchOne('SELECT value FROM `option` WHERE name = ?', $optionName);
	}

	public function unquote($value) {
		return str_replace("\'", "'", $value);
	}
}