<?php 
session_start();
if (!isset($_SESSION['username'])) {
	echo "10";
	exit;
}
if ($_SESSION['randomString']!=$_POST['connid']) {
	echo "10";
	exit;
}

if (isset($_POST['address'])) {
	$address=$_POST['address'];
	if (strlen($address)>50) {
		echo "Address is too long. Use max. 50 characters";
		exit;
	}
}

if (isset($_POST['addressid'])) {
	$addressid=$_POST['addressid'];
} else {
	echo "10";
	exit;
}

$userid=$_SESSION['userid'];
require_once __DIR__ . '/../../tools/include.php';
try {
	$checkAddress=$emercoin->validateaddress($address);
	$addressStatus=0;

	if ($checkAddress["isvalid"]) {
		$addressStatus=1;
		if ($checkAddress["ismine"]) {
			$addressStatus=2;
		}
	}

	$query = "UPDATE wallet_addressbook 
		SET address='$address',
		valid='$addressStatus'
		WHERE userid='$userid' AND id='$addressid'";	
	if ($dbwalletconn->query($query) === TRUE) {
		echo $addressStatus;
	} else {
		echo "10";
	}
	
} catch (Exception $e) {
	walletlog($dbwalletconn, time(), 'error', 'Update Addressbook: Address change failed', $userid, null, null);
	echo "10";
	exit;
}

function walletlog($dbwalletconn, $time, $category, $log, $userid, $txid, $addressid) {
	$insert_query = "INSERT INTO wallet_log
		(time, category, log, userid, txid, addressid)
		VALUES
		('$time', '$category', '$log', '$userid', '$txid', '$addressid')";	
	$dbwalletconn->query($insert_query);
}
?>