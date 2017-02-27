<?php 
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

$userid=$_SESSION['userid'];
require_once __DIR__ . '/../../tools/include.php';
$output='<option value="-">'.lang("ADDRESS_BOOK").'</option>';
$query = "SELECT name, address FROM wallet_addressbook WHERE userid = '$userid' ORDER BY name";
$result = $dbwalletconn->query($query);
while($row = $result->fetch_assoc()) {
	$name=$row['name'];
	$address=$row['address'];
	$output.='<option value="'.$address.'">'.$name.'</option>';
}
echo $output;
?>