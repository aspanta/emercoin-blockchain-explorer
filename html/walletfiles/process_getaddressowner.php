<?php
error_reporting(E_ALL); 
ini_set("display_errors", 1); 
session_start();
if (!isset($_SESSION['username'])) {
	echo "1";
	exit;
}

	if (!empty($_COOKIE["lang"])) {
		$lang=$_COOKIE["lang"];
		require("../lang/".$lang.".php");
	} else {
		setcookie("lang","en",time()+(3600*24*14), "/");
		require("../lang/en.php");
	}

$response=array();
$userid=$_SESSION['userid'];
$username=lang('SYSTEM_SYSTEM');
require_once __DIR__ . '/../../tools/include.php';
if (isset($_POST['address'])) {
	$address=$_POST['address'];
	$query = "SELECT ad.userid, user.username 
	FROM wallet_address AS ad
	INNER JOIN wallet_user as user ON user.id=ad.userid
	WHERE address='$address'";
	$result = $dbwalletconn->query($query);
	while($row = $result->fetch_assoc()) {
		$username=$row['username'];
		$dbuserid=$row['userid'];
		if ($dbuserid==$userid) {
			$username=lang('YOU_YOU');
		}
	}
}
if (isset($userid)) {
	echo $username;
} else {
	echo "1";
}
?>