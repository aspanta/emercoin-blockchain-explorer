<?php
session_start();
error_reporting(E_ALL);
ini_set("display_errors", 1);
if (!isset($_SESSION['username'])) {
	header("Location: /wallet");
	exit;
}
require_once __DIR__ . '/../../tools/include.php';
$userid=$_SESSION['userid'];
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
$username=$_SESSION['username'];

$curpasswd="wc464mn6s4we6".$_POST['currentpassword']."s46sa35";
$salt=md5("4sn67m8SDT!".strtolower($username));
$curpassword=crypt($curpasswd, '$6$rounds=17500$'.$salt);

$query = "SELECT pw FROM wallet_user
		WHERE username = '$username'";
$result = $dbwalletconn->query($query);
	while($row = $result->fetch_assoc()) {
	$pw=$row['pw'];
}
$passwordvalid="0";
if ($curpassword==$pw)
{
	$passwordvalid="1";
}

if ($passwordvalid=="0") {
	echo "1";
	exit;
}

if (isset($_SESSION['username']) && isset($_POST['email']) && isset($_POST['password']))
{
	$email=$_POST['email'];
	$passwd="wc464mn6s4we6".$_POST['password']."s46sa35";
	$salt=md5("4sn67m8SDT!".strtolower($username));
	$password=crypt($passwd, '$6$rounds=17500$'.$salt);
	if ($_POST['changepassword']==1 && $_POST['changeemail']==1) {
		$query = "UPDATE wallet_user
		SET email='$email', pw='$password'
		WHERE username='$username'";
	}
	if ($_POST['changeemail']==1 && $email!="") {
		$mailcheck=(generateRandomString(32));
		$to      = $email;
		$subject = 'Please confirm your e-mail address';
		$message = 'Hi '.$username.',
You have just changed your e-mail address at '.$blockchainurl.'.
Please verify your e-mail address by clicking on the following link:
https://'.$blockchainurl.'/wallet/verifyme/'.$mailcheck.'

Best regards,
mintr.org Admin';
		$headers = 'From: admin@mintr.org';

		mail($to, $subject, $message, $headers);
		$query = "UPDATE wallet_user
		SET email='$email', mailcheck='$mailcheck'
		WHERE username='$username'";
	}
	if ($_POST['changepassword']==0 && $_POST['changeemail']==1 && $email=="") {
		$query = "UPDATE wallet_user
		SET email='$email', mailcheck=''
		WHERE username='$username'";
	}
	if ($_POST['changepassword']==1 && $_POST['changeemail']==0) {
		$query = "UPDATE wallet_user
		SET pw='$password'
		WHERE username='$username'";
	}
	if ($dbwalletconn->query($query) === TRUE) {
		$_SESSION['email']=$email;
		echo "0";
	} else {
		echo "Update query failed.";
	}
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
