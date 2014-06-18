<?php

print <<<FOOTERPAGEEND
	</body>
</html>
FOOTERPAGEEND;
//if (isset($_SESSION['recipe']['user']) && $_SESSION['recipe']['user']['admin']) {
if (0) {
	print "<div id=\"sessionVar\" class=\"hidden\"><pre>";
	print_r($_SESSION);
	print_r($_SERVER);
	print "</pre><a onclick=\"document.getElementById('sessionVar').className='hidden';\">Hide Session</a></div><a onclick=\"document.getElementById('sessionVar').className='';\">Show Session</a>";
}
?>
