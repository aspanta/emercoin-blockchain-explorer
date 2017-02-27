<?php
error_reporting(E_ALL); 
ini_set("display_errors", 1); 
session_start();
if (isset($_POST['username']) && isset($_POST['password']))
{
	if ($_POST['captcha']!=$_SESSION['captcha']) {
		echo "3";
		exit;
	}
	require_once __DIR__ . '/../../tools/include.php';
	$username=$_POST['username'];
	$pw=$_POST['password'];
	$query = "SELECT COUNT(id) AS usercount FROM wallet_user WHERE username = '$username'";
	$result = $dbwalletconn->query($query);
	$usercount="";
	while($row = $result->fetch_assoc())
	{
		$usercount=$row['usercount'];
	}
	
	if ($usercount==0)
	{
		require_once __DIR__ . '/../../tools/include.php';
		$newaddress=$emercoin->getnewaddress("transfer");
		if ($newaddress!="") {
			$passwd="wc464mn6s4we6".$pw."s46sa35";
			$salt=md5("4sn67m8SDT!".strtolower($username));
			$password=crypt($passwd, '$6$rounds=17500$'.$salt);
			$query = "INSERT INTO wallet_user
					(username, pw, emcsslauth)
					VALUES
					('$username', '$password', '0')";
			if ($dbwalletconn->query($query) === TRUE) {
				$last_id = $dbwalletconn->insert_id;
				$query = "INSERT INTO wallet_address 
					(userid, address)
					VALUES
					('$last_id', '$newaddress')";	
				$dbwalletconn->query($query);
				$time=time();
				$query = "INSERT INTO wallet_balance
					(userid, balance, time, coinsec)
					VALUES
					('$last_id', '0', '$time', '0')";	
				$dbwalletconn->query($query);
				echo '0';
			} else {
				echo "2";
			}
		} else {
			echo "2";
		}
	}
	else
	{
		echo "1";
	}
}
else
{
	echo "2";
}
?>