<?php 
session_start();
if (!isset($_SESSION['username'])) {
	echo "1";
	exit;
}
//error_reporting(E_ALL); 
//ini_set("display_errors", 1); 
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


$name=$_POST['name'];

$query = "SELECT name FROM wallet_nvs WHERE userid = '$userid' AND name = '$name'";
$result = $dbwalletconn->query($query);
$namefound=0;
while($row = $result->fetch_assoc()) {
	$namefound=1;
}
if ($namefound==1) {
	echo "You already own this name";
	exit;
}

try {
	$getinfo=$emercoin->getinfo();
	$blocks=($getinfo['blocks']-6);
} catch (Exception $e) {
	walletlog($dbwalletconn, time(), 'error', 'Claim Name: get block count failed', $userid, null, null);
	echo "1";
	exit;
}

try {
	$name_show=$emercoin->name_show($name);
} catch (Exception $e) {
	//walletlog($dbwalletconn, time(), 'error', 'Reg Name: Availability check failed', $userid, null, null);
	//echo "Availability check failed - Please try again later";
	//exit;
}
if (!isset($name_show['address'])) {
	echo "3";
	exit;
} else {
	$query = "SELECT address FROM wallet_address WHERE userid = '$userid'";
	$result = $dbwalletconn->query($query);
	$addressmatch=0;
	while($row = $result->fetch_assoc()) {
		$address=$row['address'];
		if ($name_show['address']==$address) {
			$addressmatch=1;
			//write nvs entry into db
			$query = "INSERT INTO wallet_nvs
			(userid,name,registered_at)
			VALUES
			('$userid', '$name', '$blocks')";
			if ($dbwalletconn->query($query) === TRUE) {
				echo "0";
			} else {
				walletlog($dbwalletconn, time(), 'error', 'Claim Name: insert into db failed', $userid, null, null);
				echo "1";
				exit;
			}
		}
	}
	if ($addressmatch==0) {
		echo "2";
		exit;
	}
}

?>