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

$name="";
if (isset($_POST['name'])) {
	$name=$_POST['name'];
	if (strlen($name)>50) {
		echo "Name is too long. Use max. 50 characters";
		exit;
	}
} else {
	echo 1;
	exit;		
}
$address="";
if (isset($_POST['address'])) {
	$address=$_POST['address'];
	if (strlen($address)>50) {
		echo "Address is too long. Use max. 50 characters";
		exit;
	}
} else {
	echo 1;
	exit;		
}

$userid=$_SESSION['userid'];

try {
	$checkAddress=$emercoin->validateaddress($address);
	$addressStatus=0;

	if ($checkAddress["isvalid"]) {
		$addressStatus=1;
		if ($checkAddress["ismine"]) {
			$addressStatus=2;
		}
	}

	$query = "INSERT INTO wallet_addressbook
		(userid, name, address, valid)
		VALUES
		('$userid', '$name', '$address', '$addressStatus')";	
	if ($dbwalletconn->query($query) === TRUE) {
		echo "0";
		exit;
	} else {
		echo "1";
		exit;
	}
} catch (Exception $e) {
	walletlog($dbwalletconn, time(), 'error', 'New Addressbook: Creation failed', $userid, null, null);
	echo "1";
	exit;
}

function walletlog($dbwalletconn, $time, $category, $log, $userid, $txid, $addressid) {
	$insert_query = "INSERT INTO wallet_log
		(time, category, log, userid, txid, addressid)
		VALUES
		('$time', '$category', '$log', '$userid', '$txid', '$addressid')";	
	$dbwalletconn->query($insert_query);
}

function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

$randomString=md5($userid.generateRandomString());
$_SESSION['randomString']=$randomString;
?>