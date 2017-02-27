<?php
require_once __DIR__ . '/../../tools/include.php';

$username=$_POST['username'];

$query = "SELECT COUNT(id) AS usercount FROM wallet_user WHERE username = '$username'";
$result = $dbwalletconn->query($query);
while($row = $result->fetch_assoc())
{
	$usercount=$row['usercount'];
}	

if ($usercount==0)
{
	echo "0";
}
else
{
	echo "1";
}
?>