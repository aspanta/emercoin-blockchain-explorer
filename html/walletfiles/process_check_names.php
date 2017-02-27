<?php 
session_start();
if (!isset($_SESSION['username'])) {
	echo "1";
	exit;
}
error_reporting(E_ALL); 
ini_set("display_errors", 1); 
$userid=$_SESSION['userid'];
require_once __DIR__ . '/../../tools/include.php';
$getinfo=$emercoin->getinfo();
$blocks=($getinfo['blocks']-12);
$query = "SELECT name, registered_at FROM wallet_nvs WHERE userid = '$userid'";
$result = $dbwalletconn->query($query);
while($row = $result->fetch_assoc()) {
	$name=$row['name'];
	try {
		$name_show=$emercoin->name_show($name);
		$name_found=0;
		if ($emercoin->name_show($name)) {
			$name_show=$emercoin->name_show($name);
			if ($name_show['expires_in']<=0 && $blocks>$row['registered_at']) {
				//remove outdated names
				$query="DELETE FROM wallet_nvs WHERE userid = '$userid' AND BINARY name = '$name'";
				$dbwalletconn->query($query);
			}
			// Temp disabled!!!
			// $validateaddress=$emercoin->validateaddress($name_show['address']);
			// if ($validateaddress['ismine']==false && $blocks>$row['registered_at']) {
				// //remove names which don't belong to the wallet
				// $query="DELETE FROM wallet_nvs WHERE userid = '$userid' AND name = '$name'";
				// $dbwalletconn->query($query);
			// }
			$name_found=1;
		}
		if ($name_found==0 && $blocks>$row['registered_at']) {
			//remove deleted names
			$query="DELETE FROM wallet_nvs WHERE userid = '$userid' AND BINARY name = '$name'";
			$dbwalletconn->query($query);
		}
	} catch (Exception $e) {
		//
	}
}
$output='<option value="-">-</option>';
$query = "SELECT name, registered_at FROM wallet_nvs WHERE userid = '$userid'";
$result = $dbwalletconn->query($query);
while($row = $result->fetch_assoc()) {
	$name=$row['name'];
	if ($emercoin->name_scan($name)) {
		if ($row['registered_at']<$getinfo['blocks']) {
			$output.='<option value="'.$name.'">'.$name.'</option>';
		} else {
			$output.='<option disabled="disabled">'.$name.' (Unavailable/Pending)</option>';
		}
	} else {
		$output.='<option disabled="disabled">'.$name.' (Unavailable/Pending)</option>';
	}
}
echo $output;
?>