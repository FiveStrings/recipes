<?php
if (isset($_GET['pictureID'])) {
	header('Content-type: image/jpeg');
	print $db->getImage($_GET['pictureID']);
}
?>
