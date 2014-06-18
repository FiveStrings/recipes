<?php
ini_set('display_errors', 1);
@session_start();

$_PATHS['base'] 	= dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR;
$_PATHS['www']			= $_PATHS['base'] . 'www' . DIRECTORY_SEPARATOR;
$_PATHS['include']		= $_PATHS['base'] . 'include' . DIRECTORY_SEPARATOR;

set_include_path(ini_get('include_path') . PATH_SEPARATOR . '/home/flyingcowofdoom/PEAR' . PATH_SEPARATOR . $_PATHS['include'] . PATH_SEPARATOR . $_PATHS['www']);

require_once('RecipeDB.inc.php');
$dsn = array(
	'phptype' => 'mysqli',
	'username' => 'flyingcowofdoom',
	'password' => 'gunslinger1',
	'hostspec' => 'localhost',
	'database' => 'fcod_recipes',
	);
$options = array(
	'portability' => MDB2_PORTABILITY_ALL ^ MDB2_PORTABILITY_FIX_CASE,
	);
$db = RecipeDB::singleton($dsn, $options);

$validPages = array('login', 'logout', 'signup', 'mobile');

if (!isset($_SESSION['recipe']['user']) && !in_array($_GET['p'],$validPages)) {
	$_SESSION['redirectURL'] = $_SERVER['REQUEST_URI'];
	header('Location: ?p=login');
	exit;
}

function reduce($numerator, $denominator) {
	//print "Reducing $numerator / $denominator";

	for ($ii = $denominator; ($ii > 1) && !($numerator % $ii == 0 && $denominator % $ii == 0); $ii--);
	$numerator = $numerator / $ii;
	$denominator = $denominator / $ii;

	$int = 0;
	while ($numerator > $denominator) {
		$numerator -= $denominator;
		$int++;
	}

	if ($numerator == 1 && $denominator == 1) {
		$int++;
		//print " to $int<br>";
		return array($int,0,0);
	}
	//print " to $int and $numerator / $denominator<br>";
	return array($int,$numerator,$denominator);
}

function imagecopyresampledbicubic(&$dst_image, &$src_image, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h)  {
	// we should first cut the piece we are interested in from the source
	$src_img = ImageCreateTrueColor($src_w, $src_h);
	imagecopy($src_img, $src_image, 0, 0, $src_x, $src_y, $src_w, $src_h);

	// this one is used as temporary image
	$dst_img = ImageCreateTrueColor($dst_w, $dst_h);

	ImagePaletteCopy($dst_img, $src_img);
	$rX = $src_w / $dst_w;
	$rY = $src_h / $dst_h;
	$w = 0;
	for ($y = 0; $y < $dst_h; $y++)  {
		$ow = $w; $w = round(($y + 1) * $rY);
		$t = 0;
		for ($x = 0; $x < $dst_w; $x++)  {
			$r = $g = $b = 0; $a = 0;
			$ot = $t; $t = round(($x + 1) * $rX);
			for ($u = 0; $u < ($w - $ow); $u++)  {
				for ($p = 0; $p < ($t - $ot); $p++)  {
					$c = ImageColorsForIndex($src_img, ImageColorAt($src_img, $ot + $p, $ow + $u));
					$r += $c['red'];
					$g += $c['green'];
					$b += $c['blue'];
					$a++;
				}
			}
			ImageSetPixel($dst_img, $x, $y, ImageColorClosest($dst_img, $r / $a, $g / $a, $b / $a));
		}
	}

	// apply the temp image over the returned image and use the destination x,y coordinates
	imagecopy($dst_image, $dst_img, $dst_x, $dst_y, 0, 0, $dst_w, $dst_h);

	// we should return true since ImageCopyResampled/ImageCopyResized do it
	return true;
}

function emailAdmin($subject, $message) {
	global $db;
	require_once('Mail.php');
	$mail = Mail::factory('smtp', array('host'=>'mail.flyingcowofdoom.com', 'auth'=>true, 'username'=>'recipes+flyingcowofdoom.com', 'password'=>'recipesaregood'));
	$headers['To'] = $db->getOption('adminEmail');
	$headers['From'] = 'RecipeWeb <recipes@flyingcowofdoom.com>';
	$headers['Subject'] = $subject;
	$mail->send($headers['To'], $headers, $message);
}
?>
