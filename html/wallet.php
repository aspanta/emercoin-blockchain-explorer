<?php
error_reporting(E_ALL); 
ini_set("display_errors", 1); 
if (!isset($_SESSION['username'])) {
	include ("./usrmgmt/login.php");
} else {
	if (isset($_SERVER['REQUEST_URI'])) {
		$URI=explode('/',$_SERVER['REQUEST_URI']);
		if ($URI[1]=="wallet") {
			$type="overview";
			if (isset($URI[2])) {
				$type=urldecode($URI[2]);
				if ($type=="") {
					$type="overview";
				}
			}
		}
	}	
	if ($type=="overview") {
		include ("./walletfiles/wallet_overview.php");
	}
	if ($type=="transactions") {
		include ("./walletfiles/wallet_transaction.php");
	}
	if ($type=="addressbook") {
		include ("./walletfiles/wallet_addressbook.php");
	}
	if ($type=="send") {
		include ("./walletfiles/wallet_send.php");
	}
	if ($type=="receive") {
		include ("./walletfiles/wallet_receive.php");
	}
	if ($type=="settings") {
		include ("./walletfiles/wallet_settings.php");
	}
	if ($type=="nvs") {
		include ("./walletfiles/wallet_nvs.php");
	}
	if ($type=="verifyme") {
		include ("./walletfiles/verifyme.php");
	}
}
?>
