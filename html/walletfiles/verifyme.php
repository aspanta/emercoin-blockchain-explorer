<?php
error_reporting(E_ALL); 
ini_set("display_errors", 1); 
if (!isset($_SESSION['username'])) {
	header("Location: /wallet");
	exit;
}
require_once __DIR__ . '/../../tools/include.php';
$userid=$_SESSION['userid'];
$query = "SELECT sessionid FROM wallet_user 
		WHERE id = '$userid'";
$sessionid="";
$result = $dbwalletconn->query($query);
	while($row = $result->fetch_assoc()) {
		$sessionid=$row['sessionid'];
	}
if ($sessionid!=session_id()) {
	session_destroy();
	echo '<script type="text/javascript">
		window.location = "/wallet"
	</script>';
	exit;
}
$urimailcheck="";
if (isset($_SERVER['REQUEST_URI'])) {
	$URI=explode('/',$_SERVER['REQUEST_URI']);
	if ($URI[1]=="wallet" && $URI[2]=="verifyme") {
		if (isset($URI[3])) {
			$urimailcheck=$URI[3];
		}
	}
}
$mailcheck="123";
$query = "SELECT mailcheck FROM wallet_user 
		WHERE id = '$userid'";
$result = $dbwalletconn->query($query);
while($row = $result->fetch_assoc()) {
	$mailcheck=$row['mailcheck'];
}

if ($mailcheck==$urimailcheck) {
	$query = "UPDATE wallet_user
	SET mailcheck='1'
	WHERE id='$userid'";
	$dbwalletconn->query($query);
}

	echo '<script type="text/javascript">
		window.location = "/wallet/settings"
	</script>';

?>