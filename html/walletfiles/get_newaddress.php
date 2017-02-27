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

$label="";
if (isset($_POST['label'])) {
	$label=$_POST['label'];
	if (strlen($label)>50) {
		echo "1";
		exit;
	}
}

$userid=$_SESSION['userid'];
try {
	$account_balance=$emercoin->getbalance($userid);
} catch (Exception $e) {
	echo "New Address: Get balance failed";
	exit;
}
$query = "SELECT SUM(amount) AS amount, SUM(service_fee) AS service_fee FROM wallet_send_queue WHERE userid = '$userid' AND confirmations IS NULL";
$result = $dbwalletconn->query($query);
while($row = $result->fetch_assoc()) {
	$reserved_balance=($row['amount']+$row['service_fee']);
}
if ($reserved_balance=="") {$reserved_balance=0;}

$account_balance-=$reserved_balance;

$query = "SELECT COUNT(id) AS addresses FROM wallet_address WHERE userid = '$userid'";
$result = $dbwalletconn->query($query);
while($row = $result->fetch_assoc()) {
	$address_count=$row['addresses'];
}

if ($address_count<$max_addresses) {
	try {
		$newaddress=$emercoin->getnewaddress("transfer");
	} catch (Exception $e) {
		walletlog($dbwalletconn, time(), 'error', 'New Address: Get new free address failed', $userid, null, null);
		echo "Get new address failed";
		exit;
	}
} else {
	if ($price_extra_address<=$account_balance) {
		$move_to_stake=bcmul($price_extra_address,$fee_to_stake,8);
		$move_to_service=bcsub($price_extra_address,$move_to_stake,8);
		if ($emercoin->move( $userid, "stake", (float)$move_to_stake)) {
			if ($emercoin->move( $userid, "service", (float)$move_to_service)) {
				try {
					$newaddress=$emercoin->getnewaddress("transfer");
				} catch (Exception $e) {
					walletlog($dbwalletconn, time(), 'error', 'New Address: Get new paid address failed', $userid, null, null);
					echo "Get new address failed";
					exit;
				}
				$current_time=time();
				$query = "INSERT INTO wallet_transaction
				(userid, time, address, category, service_fee)
				VALUES
				('$userid', '$current_time', '$newaddress', 'new_addr', '$price_extra_address')";	
				if ($dbwalletconn->query($query) === TRUE) {
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
						walletlog($dbwalletconn, time(), 'error', 'New Address: Get new balance failed', $userid, null, null);
					}
					$insert_query = "INSERT INTO wallet_balance
							(userid, balance, time, coinsec)
							VALUES
							('$userid', '$account_balance', '$current_time', '$new_coinsec')";	
						if ($dbwalletconn->query($insert_query) === TRUE) {
							//
						} else {
							walletlog($dbwalletconn, time(), 'error', 'New Address: query failed. wallet_balance not updated', $userid, null, null);
							exit;
						}	
				} else {
					walletlog($dbwalletconn, time(), 'error', 'New Address: query failed. new address transaction insert failed', $userid, null, null);
					exit;
				}
			}  else {
				walletlog($dbwalletconn, time(), 'error', 'New Address: move to service failed', $userid, null, null);
				echo "1";
				exit;
			}
		}  else {
			walletlog($dbwalletconn, time(), 'error', 'New Address: move to stake failed', $userid, null, null);
			echo "1";
			exit;
		}
	} else {
		echo "1";
		exit;
	}
}

if (isset($newaddress)) {
	$query = "INSERT INTO wallet_address 
		(userid, address, label)
		VALUES
		('$userid', '$newaddress', '$label')";	
	if ($dbwalletconn->query($query) === TRUE) {
		echo "0";
		exit;
	} else {
		echo "1";
		exit;
	}
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