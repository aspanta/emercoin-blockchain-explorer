<?php
session_start();
session_regenerate_id();
error_reporting(E_ALL); 
ini_set("display_errors", 1); 
require_once __DIR__ . '/../../tools/include.php';
function emcssl_validate($emercoin) {
	try {
		error_reporting(E_ALL);
		if(!array_key_exists('SSL_CLIENT_CERT', $_SERVER))
		  return "No certificate presented, or missing flag +ExportCertData";

		if(!array_key_exists('SSL_CLIENT_I_DN_UID', $_SERVER))
		  return "This certificane is not belong to any cryptocurrency";

		if($_SERVER['SSL_CLIENT_I_DN_UID'] != 'EMC')
		  return "Wrong blockchain currency - this is not EmerCoin blockchain certificate";

		// Generate search key, and retrieve NVS-value 
		$key = str_pad(strtolower($_SERVER['SSL_CLIENT_M_SERIAL']), 16, 0, STR_PAD_LEFT);
		if($key[0] == '0') 
		  return "Wrong serial number - must not start from zero";
		$key = "ssl:" . $key;
		$nvs = $emercoin->name_show($key);
		if($nvs['expires_in'] <= 0)
		  return "NVS record expired, and is not trustable";

		// Compute certificate fingerprint, using algo, defined in the NVS value
		list($algo, $emc_fp) = explode('=', $nvs['value']);
		$crt_fp = hash($algo, 
					   base64_decode(
						 preg_replace('/\-+BEGIN CERTIFICATE\-+|-+END CERTIFICATE\-+|\n|\r/',
						   '', $_SERVER['SSL_CLIENT_CERT'])));

		return ($emc_fp == $crt_fp)? '$' . $nvs['address'] : "False certificate provided";

	  } catch(Exception $e) {
		return "Cannot extract from NVS key=$key"; // Any mmcFE error - validation fails
	}
} // emcssl_validate


if (isset($_POST['username']) && isset($_POST['password']) && isset($_POST['emcsslauth'])) {
	$username=$_POST['username'];
	$pw=$_POST['password'];
	$passwd="wc464mn6s4we6".$pw."s46sa35";
	$salt=md5("4sn67m8SDT!".strtolower($username));
	$password=crypt($passwd, '$6$rounds=17500$'.$salt);
	$emcsslauth=$_POST['emcsslauth'];
	if ($emcsslauth==0) {
	$query = "SELECT emcsslauth FROM wallet_user
		WHERE username='$username'";
		$result = $dbwalletconn->query($query);
		while($row = $result->fetch_assoc()) {
			$emcsslauth=$row['emcsslauth'];
		}
	}
$isvalid=0;
	if ($emcsslauth>0) {
		if (isset($_SERVER['SSL_CLIENT_M_SERIAL'])) {
			$validate=emcssl_validate($emercoin);
			if ($validate[0]=="$" && $validate[1]=="E") {
				$key = str_pad(strtolower($_SERVER['SSL_CLIENT_M_SERIAL']), 16, 0, STR_PAD_LEFT);
				if ($emcsslauth==1 || $emcsslauth==2) {
					$query = "SELECT id, username, email, emcssl, pw FROM wallet_user WHERE emcssl = '$key' AND emcsslauth ='$emcsslauth'";
				} else if ($emcsslauth==3) {
					$query = "SELECT id, username, email, emcssl, pw FROM wallet_user WHERE  username = '$username' AND pw = '$password' AND emcssl = '$key' AND emcsslauth ='$emcsslauth'";
				}
			} else {
				echo "1";
				exit;
			} 
		} else {
			if ($emcsslauth==1) {
				$query = "SELECT id, username, email, emcssl, pw FROM wallet_user WHERE username = '$username' AND pw = '$password' AND emcsslauth ='$emcsslauth'";
			} else {
				echo "1";
				exit;
			}
		}
	} else {
		$query = "SELECT id, username, email, emcssl, pw FROM wallet_user WHERE username = '$username' AND pw = '$password' AND emcsslauth ='$emcsslauth'";
	}
	$result = $dbwalletconn->query($query);
	while($row = $result->fetch_assoc()) {
		$db_id=$row['id'];
		$db_username=$row['username'];
		$db_password=$row['pw'];
		$db_email=$row['email'];
		$db_emcssl=$row['emcssl'];
	}
	if (isset($db_username)) {
		$sessionid=session_id();
		$query = "UPDATE wallet_user
			SET sessionid='$sessionid' WHERE id = '$db_id'";
		$dbwalletconn->query($query);
		$_SESSION['username']=$db_username;
		$_SESSION['userid']=$db_id;
		$_SESSION['email']=$db_email;
		$_SESSION['emcsslauth']=$emcsslauth;
		$key="";
		$ssluser="";
		if (isset($_SERVER['SSL_CLIENT_M_SERIAL'])) {
			$validate=emcssl_validate($emercoin);
			if ($validate[0]=="$" && $validate[1]=="E") {
				$isvalid=1;
				$key = str_pad(strtolower($_SERVER['SSL_CLIENT_M_SERIAL']), 16, 0, STR_PAD_LEFT);
				$ssluser=strtolower($_SERVER['SSL_CLIENT_S_DN_CN']);
			}
		}
		$_SESSION['emcsslisvalid']=$isvalid;
		$_SESSION['emcssl']=$key;
		$_SESSION['sslusername']=$ssluser;
		function generateRandomString($length = 10) {
			$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
			$charactersLength = strlen($characters);
			$randomString = '';
			for ($i = 0; $i < $length; $i++) {
				$randomString .= $characters[rand(0, $charactersLength - 1)];
			}
			return $randomString;
		}
		$randomString=md5($db_id.generateRandomString());
		$_SESSION['randomString']=$randomString;
		
		echo "0";
		exit;
	} else {
		echo "1";
		exit;
	}
} else {
	echo "1";
	exit;
}	
?>