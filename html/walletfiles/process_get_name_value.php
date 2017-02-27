<?php
session_start();
if (!isset($_SESSION['username'])) {
	echo "1";
	exit;
}
$response=array();
$userid=$_SESSION['userid'];
require_once __DIR__ . '/../../tools/include.php';
if (isset($_POST['name'])) {
	$name=$_POST['name'];
	if ($name=="-") {
		$response['value']="";
		$response['address']="Move to address";
		echo json_encode($response);
		exit;
	}
	$query = "SELECT name FROM wallet_nvs WHERE userid = '$userid' AND BINARY name='$name'";
	$result = $dbwalletconn->query($query);
	while($row = $result->fetch_assoc()) {
		$name_db=$row['name'];
	}
	if (isset($name_db)) {
		$name_show=$emercoin->name_show($name_db);
		$response['value']=$name_show['value'];
		$response['address']=$name_show['address'];
	}
}
echo json_encode($response);
?>