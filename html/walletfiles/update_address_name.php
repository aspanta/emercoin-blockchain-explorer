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

if (isset($_POST['name'])) {
	$name=$_POST['name'];
	if (strlen($name)>50) {
		echo "Name is too long. Use max. 50 characters";
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


	$query = "UPDATE wallet_addressbook 
		SET name='$name'
		WHERE userid='$userid' AND id='$addressid'";	
	if ($dbwalletconn->query($query) === TRUE) {
		echo "0";
	} else {
		echo "1";
	}

?>