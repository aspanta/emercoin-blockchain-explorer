<?php 
session_start();
if (!isset($_SESSION['username'])) {
	echo "1";
	exit;
}
if ($_SESSION['randomString']!=$_POST['connid']) {
	echo "1";
	exit;
}

if (isset($_POST['label'])) {
	$label=$_POST['label'];
	if (strlen($label)>50) {
		echo "1";
		exit;
	}
}

if (isset($_POST['addressid'])) {
	$addressid=$_POST['addressid'];
} else {
	echo "1";
	exit;
}

$userid=$_SESSION['userid'];
require_once __DIR__ . '/../../tools/include.php';


	$query = "UPDATE wallet_address 
		SET label='$label'
		WHERE userid='$userid' AND id='$addressid'";	
	if ($dbwalletconn->query($query) === TRUE) {
		echo "0";
	} else {
		echo "1";
	}

?>