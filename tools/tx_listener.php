<?php
require_once __DIR__ . '/include.php';
$emcalltransactions=$emercoin->listtransactions("transfer",9999999);
$emcalltransactionsMain=$emercoin->listtransactions("",9999999);
$counttransactions=count($emcalltransactions);
$counttransactionsMain=count($emcalltransactionsMain);

$query = "SELECT time, txid FROM wallet_transaction
	WHERE category = 'receive' AND confirmations != '-1'
	ORDER BY time DESC";
$result = $dbwalletconn->query($query);
$start_time=7200;
$unconfirmed_tx=array();
if ($result !== false) {
	while($row = $result->fetch_assoc()) {
		$unconfirmed_tx[$row['time']]=$row['txid'];
		$start_time=$row['time'];
	}
}
$start_time=($start_time-7200);

if ($start_time==0) {
	$query = "SELECT time FROM wallet_transaction
	WHERE category = 'receive'
	ORDER BY time DESC
	LIMIT 1";
	$result = $dbwalletconn->query($query);
	while($row = $result->fetch_assoc()) {
		$start_time=$row['time'];
	}
}

$confirmed_db_txid=array();
$query = "SELECT txid FROM wallet_transaction
			WHERE time >= '$start_time' AND confirmations = '-1'";
			$result = $dbwalletconn->query($query);
			while($row = $result->fetch_assoc()) {
				array_push($confirmed_db_txid, $row['txid']);
			}
for ($i=0; $i<=($counttransactions-1); $i++) {
	unset($db_txid);
	$confirmed=0;
	if ($emcalltransactions[$i]["time"]>=$start_time && $emcalltransactions[$i]["category"]=="receive") {
		$address = $emcalltransactions[$i]["address"];
		$amount = $emcalltransactions[$i]["amount"];
		$confirmations = $emcalltransactions[$i]["confirmations"];
		$txid = $emcalltransactions[$i]["txid"];
		$time = $emcalltransactions[$i]["time"];
		//check if tx is confirmed
		if ($confirmations >= $min_confirmations) {
			$confirmations="-1";
		}
		//get user and address id for this incoming transaction
		$query = "SELECT id, userid FROM wallet_address
		WHERE address = '$address'";
		$result = $dbwalletconn->query($query);
		$addressid=0;
		$userid=0;
		while($row = $result->fetch_assoc()) {
			$addressid=$row['id'];
			$userid=$row['userid'];
		}
		$unique_txid=$txid.":".$userid.":".$addressid;
		if ($confirmations==-1) {
			// if this tx is not confirmed
			if (!in_array($unique_txid, $confirmed_db_txid) && !in_array($txid, $confirmed_db_txid)) {
				if ($userid!=0) {
					if (bcsub($emercoin->getbalance("transfer"),$amount,6)>=0) {
						//transfer coins to the user account
						$emercoin->move("transfer", $userid, $amount);
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
						$account_balance=$emercoin->getbalance($userid);
						$insert_query = "INSERT INTO wallet_balance
								(userid, balance, time, coinsec)
								VALUES
								('$userid', '$account_balance', '$current_time', '$new_coinsec')";
						if ($dbwalletconn->query($insert_query) === TRUE) {
							$query = "UPDATE wallet_transaction
								SET confirmations='-1'
								WHERE txid='$unique_txid'";
							if ($dbwalletconn->query($query) === TRUE) {
								$confirmed=1;
							} else {
								walletlog($dbwalletconn, time(), 'error', 'query failed. transaction update failed', $userid, null, $addressid);
								exit;
							}
						} else {
							walletlog($dbwalletconn, time(), 'error', 'query failed. wallet_balace not updated', $userid, null, null);
							exit;
						}

					} else {
						walletlog($dbwalletconn, time(), 'error', 'insufficient transfer funds', $userid, null, $addressid);
						exit;
					}
				}
			}
		}
		//check if the transaction is new (insert) or if it has to be updated
		if (!in_array($unique_txid, $unconfirmed_tx)) {
			if (!in_array($unique_txid, $confirmed_db_txid)) {
				//insert tx in wallet_transaction
				$query = "INSERT IGNORE INTO wallet_transaction
				(userid, addressid, time, confirmations, txid, category, amount)
				VALUES
				('$userid', '$addressid', '$time', '$confirmations', '$unique_txid', 'receive', '$amount')";
				if ($dbwalletconn->query($query) === TRUE) {
						//
				} else {
					walletlog($dbwalletconn, time(), 'error', 'query failed. transaction insert failed', $userid, null, $addressid);
					exit;
				}
			}
		} elseif ($confirmed==0) {
			//update tx in wallet_transaction
			$query = "UPDATE wallet_transaction
				SET confirmations='$confirmations'
				WHERE txid='$unique_txid'";
			if ($dbwalletconn->query($query) === TRUE) {
						//
			} else {
				walletlog($dbwalletconn, time(), 'error', 'query failed. transaction update failed', $userid, null, $addressid);
				exit;
			}
		}
	}
}

///
// Start send listener:
///

$query = "SELECT id, userid, address, amount, service_fee FROM wallet_send_queue WHERE confirmations IS NULL";
$result = $dbwalletconn->query($query);
while($row = $result->fetch_assoc()) {
	$amount=$row['amount'];
	$userid=$row['userid'];
	$id=$row['id'];
	$service_fee=$row['service_fee'];
	$amountWithFee=bcadd($amount,$service_fee,6);

	if (bcsub($emercoin->getbalance($userid), $amountWithFee,6)>=0) {
		//transfer coins to the transfer account
		$emercoin->move( $userid, "transfer_out", (float)$amountWithFee);
		//update wallet_send_queue
		$query = "UPDATE wallet_send_queue
			SET confirmations='-1'
			WHERE id='$id'";
		if ($dbwalletconn->query($query) === TRUE) {
			//
		} else {
			walletlog($dbwalletconn, time(), 'error', 'Int Send Listener: query failed. wallet_send_queue not updated', $userid, null, null);
			exit;
		}
	} else {
		walletlog($dbwalletconn, time(), 'error', 'Int Send Listener: insufficient funds to send '.$amountWithFee, $userid, $id, null);
		exit;
	}
}

$query = "SELECT id, userid, address, SUM(amount) AS amount, SUM(service_fee) AS service_fee FROM wallet_send_queue WHERE confirmations = '-1' GROUP BY address";
$result = $dbwalletconn->query($query);
while($row = $result->fetch_assoc()) {
	$address=$row['address'];
	$amount=$row['amount'];
	$service_fee=$row['service_fee'];
	$userid=$row['userid'];
	$id=$row['id'];
	$addressArray[$address]=(float)$amount;
	$query2 = "UPDATE wallet_send_queue
			SET confirmations='-2'
			WHERE id='$id'";
	if ($dbwalletconn->query($query2) === TRUE) {
		//
	} else {
		walletlog($dbwalletconn, time(), 'error', 'Int Send Listener: query failed (-2). wallet_send_queue not updated', $userid, null, null);
		exit;
	}
}
unset($txid);
if (isset($addressArray)) {
	$emercoin->walletlock();
	$emercoin->walletpassphrase($wallet_password, 60, false);
	$txid=$emercoin->sendmany("transfer_out", $addressArray, $min_confirmations );
}
if (isset($txid)) {
	$transactiondetail=$emercoin->gettransaction($txid);
	$fee=$transactiondetail["fee"];
	$time=$transactiondetail["time"];
	$useridOld=0;
	$query = "SELECT id, userid, address, amount, service_fee FROM wallet_send_queue WHERE confirmations = '-2'";
	$result = $dbwalletconn->query($query);
	$count=0;
	while($row = $result->fetch_assoc()) {
		$count++;
		$address=$row['address'];
		$amount=$row['amount'];
		$service_fee=$row['service_fee'];
		$userid=$row['userid'];
		$unique_txid=$txid.":".$userid.":".$count;
		//insert tx in wallet_transaction
		$query = "INSERT INTO wallet_transaction
		(userid, time, txid, address, category, amount, fee, service_fee)
		VALUES
		('$userid', '$time', '$unique_txid', '$address', 'send', '$amount', '$fee', '$service_fee')";
		if ($dbwalletconn->query($query) === TRUE) {
			$query2 = "SELECT id, userid FROM wallet_send_queue WHERE confirmations = '-2' ORDER BY userid";
			$result2 = $dbwalletconn->query($query2);
			while($row2 = $result2->fetch_assoc()) {
				$userid=$row2['userid'];
				$id=$row2['id'];
				//update balance
				if ($userid>$useridOld) {
					$current_time=time();
					//get current balance to calculate the new coinsecs
					$query = "SELECT balance, time, coinsec FROM wallet_balance
					WHERE userid = '$userid' ORDER BY id DESC LIMIT 1";
					$result3 = $dbwalletconn->query($query);
					while($row3 = $result3->fetch_assoc()) {
						$oldbalance=$row3['balance'];
						$oldtime=$row3['time'];
						$coinsec=$row3['coinsec'];
						$new_coinsec=((($current_time-$oldtime)*$oldbalance)+$coinsec);
					}
					//update balance
					$account_balance=$emercoin->getbalance($userid);
					$insert_query = "INSERT INTO wallet_balance
							(userid, balance, time, coinsec)
							VALUES
							('$userid', '$account_balance', '$current_time', '$new_coinsec')";
					if ($dbwalletconn->query($insert_query) === TRUE) {
						$useridOld=$userid;
					} else {
						walletlog($dbwalletconn, time(), 'error', 'query failed. wallet_balace not updated', $userid, null, null);
						exit;
					}
				}
				//delete wallet_send_queue entry
				$delete_query = "DELETE FROM wallet_send_queue
					WHERE id='$id'";
				if ($dbwalletconn->query($delete_query) === TRUE) {
					//
				} else {
					walletlog($dbwalletconn, time(), 'error', 'query failed. wallet_send_queue entry not deleted', $userid, null, null);
					exit;
				}
			}
		} else {
			walletlog($dbwalletconn, time(), 'error', 'query failed. send transaction insert failed', $userid, null, $addressid);
			exit;
		}
	}
	if ($emercoin->getbalance("transfer_out")>1) {
		$coins_to_stake=(float)bcmul(bcsub($emercoin->getbalance("transfer_out"),1,6),$fee_to_stake,6);
		$coins_to_service=(float)bcsub(bcsub($emercoin->getbalance("transfer_out"),1,6),$coins_to_stake,6);
		$emercoin->move("transfer_out", "stake", $coins_to_stake);
		if ($emercoin->getbalance("transfer_out")>1) {
			$emercoin->move("transfer_out", "service", $coins_to_service);
		}
	}
	$emercoin->walletlock();
	$emercoin->walletpassphrase($wallet_password, 99999999, true);
}


//////
// Start stake listener Main:
//////

$start_time=86400;
$query = "SELECT time FROM wallet_stake
ORDER BY time DESC
LIMIT 1";
$result = $dbwalletconn->query($query);
while($row = $result->fetch_assoc()) {
	$start_time=$row['time'];
}
$start_time=($start_time-86400);
$confirmed_db_txid=array();
$query = "SELECT txid FROM wallet_stake
WHERE time >= '$start_time'";
$result = $dbwalletconn->query($query);
while($row = $result->fetch_assoc()) {
	array_push($confirmed_db_txid, $row['txid']);
}
for ($i=0; $i<=($counttransactionsMain-1); $i++) {
	if ($emcalltransactionsMain[$i]["time"]>=$start_time && $emcalltransactionsMain[$i]["category"]=="stake-mint") {
		$address = $emcalltransactionsMain[$i]["address"];
		$amount = $emcalltransactionsMain[$i]["amount"];
		$txid = $emcalltransactionsMain[$i]["txid"];
		$time = $emcalltransactionsMain[$i]["time"];
		if (!in_array($txid, $confirmed_db_txid)) {
			// move stake to service and stake pool
			$service_fee=bcmul($pos_percent_fee,$amount,6);
			$move_to_stake=bcsub($amount,$service_fee,6);
			if ($service_fee > 0) {
				try {
					$emercoin->move( "", "service", (float)$service_fee);
				} catch (Exception $e) {
					walletlog($dbwalletconn, time(), 'error', 'Stake Listener: Move to service failed', $userid, null, null);
					echo "1";
					exit;
				}
				try {
					$emercoin->move( "", "stake", (float)$move_to_stake);
				} catch (Exception $e) {
					walletlog($dbwalletconn, time(), 'error', 'Stake Listener: Move to stake failed', $userid, null, null);
					echo "1";
					exit;
				}
			}
			//insert tx in wallet_stake
			$query = "INSERT IGNORE INTO wallet_stake
			(time, txid, address, amount, service_fee)
			VALUES
			('$time', '$txid', '$address', '$amount', '$service_fee')";
			if ($dbwalletconn->query($query) === TRUE) {
				//
			} else {
					walletlog($dbwalletconn, time(), 'error', 'Stake Listener: query failed. stake insert failed', $userid, null, $addressid);
				exit;
			}
		}
	}
}

//////
// Start stake listener transfer:
//////

$start_time=86400;
$query = "SELECT time FROM wallet_stake
ORDER BY time DESC
LIMIT 1";
$result = $dbwalletconn->query($query);
while($row = $result->fetch_assoc()) {
	$start_time=$row['time'];
}
$start_time=($start_time-86400);
$confirmed_db_txid=array();
$query = "SELECT txid FROM wallet_stake
WHERE time >= '$start_time'";
$result = $dbwalletconn->query($query);
while($row = $result->fetch_assoc()) {
	array_push($confirmed_db_txid, $row['txid']);
}
for ($i=0; $i<=($counttransactions-1); $i++) {
	if ($emcalltransactions[$i]["time"]>=$start_time && $emcalltransactions[$i]["category"]=="stake-mint") {
		$address = $emcalltransactions[$i]["address"];
		$amount = $emcalltransactions[$i]["amount"];
		$account = $emcalltransactions[$i]["account"];
		$txid = $emcalltransactions[$i]["txid"];
		$time = $emcalltransactions[$i]["time"];
		if (!in_array($txid, $confirmed_db_txid)) {
			// move stake to service and stake pool
			$service_fee=bcmul($pos_percent_fee,$amount,6);
			$move_to_stake=bcsub($amount,$service_fee,6);
			if ($service_fee > 0) {
				try {
					$emercoin->move( $account, "service", (float)$service_fee);
				} catch (Exception $e) {
					walletlog($dbwalletconn, time(), 'error', 'Stake Listener: Move to service failed ('.(float)$service_fee.')', $userid, null, null);
					echo "1";
					exit;
				}
				try {
					$emercoin->move( $account, "stake", (float)$move_to_stake);
				} catch (Exception $e) {
					walletlog($dbwalletconn, time(), 'error', 'Stake Listener: Move to stake failed ('.(float)$service_fee.')', $userid, null, null);
					echo "1";
					exit;
				}
			}
			//insert tx in wallet_stake
			$query = "INSERT IGNORE INTO wallet_stake
			(time, txid, address, amount, service_fee)
			VALUES
			('$time', '$txid', '$address', '$amount', '$service_fee')";
			if ($dbwalletconn->query($query) === TRUE) {
				//
			} else {
					walletlog($dbwalletconn, time(), 'error', 'Stake Listener: query failed. stake insert failed', $userid, null, $addressid);
				exit;
			}
		}
	}
}
function walletlog($dbwalletconn, $time, $category, $log, $userid, $txid, $addressid) {
	$insert_query = "INSERT INTO wallet_log
		(time, category, log, userid, txid, addressid)
		VALUES
		('$time', '$category', '$log', '$userid', '$txid', '$addressid')";
	$dbwalletconn->query($insert_query);
}
?>
