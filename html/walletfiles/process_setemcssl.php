<?php 
session_start();
if (!isset($_SESSION['username'])) {
	echo "1";
	exit;
}
$userid=$_SESSION['userid'];
require_once __DIR__ . '/../../tools/include.php';
$query = "SELECT sessionid FROM wallet_user 
		WHERE id = '$userid'";
$sessionid="";
$result = $dbwalletconn->query($query);
	while($row = $result->fetch_assoc()) {
		$sessionid=$row['sessionid'];
	}
if ($sessionid!=session_id()) {
	session_destroy();
	echo "127";
	exit;
}

if (isset($_POST['emcsslauth']) && isset($_POST['key'])) {
	$emcsslauth=$_POST['emcsslauth'];
	$key=$_POST['key'];
	$query = "UPDATE wallet_user
		SET emcssl='$key', emcsslauth='$emcsslauth' WHERE id = '$userid'";		
	if ($dbwalletconn->query($query) === TRUE) {
		$_SESSION['emcsslauth']=$emcsslauth;
		echo "0";
	} else {
		echo "1";
	}
}
?>