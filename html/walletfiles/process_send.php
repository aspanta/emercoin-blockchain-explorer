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

try {
$account_balance=$emercoin->getbalance($userid);
} catch (Exception $e) {
	walletlog($dbwalletconn, time(), 'error', 'Send: get balance failed', $userid, null, null);
	echo "1";
	exit;
}
$query = "SELECT SUM(amount) AS amount, SUM(service_fee) AS service_fee FROM wallet_send_queue WHERE userid = '$userid' AND confirmations IS NULL";
$result = $dbwalletconn->query($query);
while($row = $result->fetch_assoc()) {
	$reserved_balance=($row['amount']+$row['service_fee']);
}
if ($reserved_balance=="") {$reserved_balance=0;}
$account_balance-=$reserved_balance;

$amount=$_POST['amount'];

$address=$_POST['address'];

try {
	$validateaddress=$emercoin->validateaddress($address);
} catch (Exception $e) {
	walletlog($dbwalletconn, time(), 'error', 'Send: validate address failed', $userid, null, null);
	echo "1";
	exit;
}
$isvalid=$validateaddress['isvalid'];
$ismine=$validateaddress['ismine'];
$time=time();
if ((float)bcadd($amount,$withdraw_fee,6)<=$account_balance && $isvalid==true && $ismine==false) {
	$query = "INSERT INTO wallet_send_queue
			(userid,time,address,amount,service_fee)
			VALUES
			('$userid', '$time', '$address', '$amount', '$withdraw_fee')";
	if ($dbwalletconn->query($query) === TRUE) {
		echo "0";
		exit;
	} else {
		echo "1";
		exit;
	}
} else if ((float)bcadd($amount,$send_to_another_account_fee,6)<=$account_balance && $isvalid==true && $ismine==true) {
	$query = "SELECT id, userid FROM wallet_address WHERE address = '$address'";
	$result = $dbwalletconn->query($query);
	while($row = $result->fetch_assoc()) {
		$destination_userid=$row['userid'];
		$destination_addressid=$row['id'];
	}
	$current_time=time();
	$move_to_stake=bcmul($send_to_another_account_fee,$fee_to_stake,6);
	$move_to_service=bcsub($send_to_another_account_fee,$move_to_stake,6);
	try {
		$emercoin->move( $userid, $destination_userid, (float)$amount);
	} catch (Exception $e) {
		walletlog($dbwalletconn, time(), 'error', 'Send: move to destination failed', $userid, null, null);
		echo "1";
		exit;
	}
	try {
		$emercoin->move( $userid, "service", (float)$move_to_service);
	} catch (Exception $e) {
		walletlog($dbwalletconn, time(), 'error', 'Send: move to service failed', $userid, null, null);
		echo "1";
		exit;
	}
	try {
		$emercoin->move( $userid, "stake", (float)$move_to_stake);
	} catch (Exception $e) {
		walletlog($dbwalletconn, time(), 'error', 'Send: move to stake failed', $userid, null, null);
		echo "1";
		exit;
	}
	
	//insert tx in wallet_transaction
		$query = "INSERT INTO wallet_transaction
		(userid, time, address, category, amount, service_fee)
		VALUES
		('$userid', '$time', '$address', 'int_send', '$amount', '$send_to_another_account_fee')";	
		if ($dbwalletconn->query($query) === TRUE) {
			$query = "INSERT INTO wallet_transaction
			(userid, addressid, time, category, amount, confirmations)
			VALUES
			('$destination_userid', '$destination_addressid', '$time', 'int_rec', '$amount', '-1')";
			if ($dbwalletconn->query($query) === TRUE) {
				//
			} else {
				walletlog($dbwalletconn, time(), 'error', 'query failed. internal receive transaction insert failed', $userid, null, $addressid);
				exit;
			}
		} else {
			walletlog($dbwalletconn, time(), 'error', 'query failed. internal send transaction insert failed', $userid, null, $addressid);
			exit;
		}
	
		$current_time=time();
		//get current balance to calculate the new coinsecs
		$query = "SELECT balance, time, coinsec FROM wallet_balance
		WHERE userid = '$userid' ORDER BY id DESC LIMIT 1";
		$result = $dbwalletconn->query($query);
		while($row = $result->fetch_assoc()) {
			$oldbalance=$row['balance'];
			$oldtime=$row['time'];
			$coinsec=$row['coinsec'];
			$new_coinsec=((($current_time-$oldtime)*$oldbalance)+$coinsec);
		}
		//update balance
		try {
			$account_balance=$emercoin->getbalance($userid);
		} catch (Exception $e) {
			walletlog($dbwalletconn, time(), 'error', 'Send: get new balance failed', $userid, null, null);
		}
		$insert_query = "INSERT INTO wallet_balance
				(userid, balance, time, coinsec)
				VALUES
				('$userid', '$account_balance', '$current_time', '$new_coinsec')";	
			if ($dbwalletconn->query($insert_query) === TRUE) {
				//
			} else {
				walletlog($dbwalletconn, time(), 'error', 'query failed. wallet_balace not updated', $userid, null, null);
				exit;
			}	
		//get current balance to calculate the new coinsecs
		$query = "SELECT balance, time, coinsec FROM wallet_balance
		WHERE userid = '$destination_userid' ORDER BY id DESC LIMIT 1";
		$result = $dbwalletconn->query($query);
		while($row = $result->fetch_assoc()) {
			$oldbalance=$row['balance'];
			$oldtime=$row['time'];
			$coinsec=$row['coinsec'];
			$new_coinsec=((($current_time-$oldtime)*$oldbalance)+$coinsec);
		}
		//update balance
		try {
			$account_balance=$emercoin->getbalance($destination_userid);
		} catch (Exception $e) {
			walletlog($dbwalletconn, time(), 'error', 'Send: get new balance failed', $userid, null, null);
		}
		$insert_query = "INSERT INTO wallet_balance
				(userid, balance, time, coinsec)
				VALUES
				('$destination_userid', '$account_balance', '$current_time', '$new_coinsec')";		
			if ($dbwalletconn->query($insert_query) === TRUE) {
				//
			} else {
				walletlog($dbwalletconn, time(), 'error', 'query failed. wallet_balace not updated', $destination_userid, null, null);
				exit;
			}			
	echo "10";
} else {
	echo "1";
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