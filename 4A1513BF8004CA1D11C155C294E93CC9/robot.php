<?php
$dir = substr(strrchr(dirname(__FILE__), "/"), 1);
define("CONTENTUSER", $dir);
$url = "";
if (!defined("ROBOT")) {
	define("ROBOT",true);
}
if (isset($_GET["check"])) {
	echo "present";
	exit();
}
if (isset($_GET["site"])) {
	$site = $_GET["site"];
} elseif (isset($_POST["site"])) {
	$site = $_POST["site"];
} else {
	exit();
}
if (isset($_GET["cat"])) {
	$cat = $_GET["cat"];
} elseif (isset($_POST["cat"])) {
	$cat = $_POST["cat"];
} else {
	exit();
}

@include_once(dirname(__FILE__) . "/init.php");
$classContentSystem = new class_ContentSystem();
$return = $classContentSystem->get_db_tasks('', $site, '', $cat);
if ($return > 0) {
	echo "__ContentSystemUpdateBase OK ContentSystemUpdateBase__";
}
?>