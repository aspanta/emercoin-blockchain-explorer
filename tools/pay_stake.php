<?php
require_once __DIR__ . '/include.php';
$current_time=time();
$coinsectotal=0;
$balancetotal=0;
$userids=array();
$total_stake=$emercoin->getbalance("stake");
$emptyaccount=$emercoin->getbalance("");
$transferaccount=$emercoin->getbalance("transfer");
$transferoutaccount=$emercoin->getbalance("transfer_out");

if ($total_stake < 0 || $emptyaccount < 0 || $transferaccount < 0 || $transferoutaccount < 0) {
	walletlog($dbwalletconn, time(), 'error', 'pay stake: negative account balance', null, null, null);
	exit;
}

if ($total_stake > 0 && $emptyaccount >= 0 && $transferaccount >= 0 && $transferoutaccount >= 0) {
	$query = "SELECT id FROM wallet_user
			ORDER BY id";
	$result = $dbwalletconn->query($query);
	while($row = $result->fetch_assoc()) {
		$userid=$row['id'];
		array_push($userids,$userid);
		//get current balance to calculate the new coinsecs
		$query2 = "SELECT balance, time, coinsec FROM wallet_balance
		WHERE userid = '$userid' ORDER BY id DESC LIMIT 1";
		$result2 = $dbwalletconn->query($query2);
		while($row2 = $result2->fetch_assoc()) {
			$oldbalance=$row2['balance'];
			$oldtime=$row2['time'];
			$coinsec=$row2['coinsec'];
			$new_coinsec=((($current_time-$oldtime)*$oldbalance)+$coinsec);
			$coinsectotal+=$new_coinsec;
			$balancetotal+=$oldbalance;
		}

		//update balance
		$insert_query = "INSERT INTO wallet_balance
				(userid, balance, time, coinsec)
				VALUES
				('$userid', '$oldbalance', '$current_time', '$new_coinsec')";
		if ($dbwalletconn->query($insert_query) === TRUE) {
			//
		} else {
			walletlog($dbwalletconn, time(), 'error', 'query failed. wallet_balace not updated', $userid, null, null);
		}
	}

	// reserve stake payout
	foreach ($userids as $userid) {
		//get current coinsecs
		$query2 = "SELECT coinsec FROM wallet_balance
		WHERE userid = '$userid' AND time = '$current_time' ORDER BY id DESC LIMIT 1";
		$result2 = $dbwalletconn->query($query2);
		while($row2 = $result2->fetch_assoc()) {
			$coinsec=$row2['coinsec'];
		}
		$query3 = "SELECT time FROM wallet_balance
		WHERE userid = '$userid' AND coinsec = '0' ORDER BY id DESC LIMIT 1";
		$result3 = $dbwalletconn->query($query3);
		$oldtime=$current_time;
		while($row3 = $result3->fetch_assoc()) {
			$oldtime=$row3['time'];
		}
		$timediff=bcsub($current_time,$oldtime,0);
		if ($total_stake!=0) {
			$stake=bcmul(bcdiv($coinsec,$coinsectotal,8),$total_stake,6);
		} else {
			$stake=0;
		}
		if ($stake!=0) {
			$emercoin->move("stake",$userid.":stake",(float)$stake);
		}
		if ($timediff!=0) {
			$coinavg=bcdiv($coinsec,$timediff,10);
		} else {
			$coinavg=0;
		}
		if ($coinavg!=0) {
			$interest=bcmul(bcdiv($stake,$coinavg,10),100,8);
		} else {
			$interest=0;
		}


		//update balance
		$query = "UPDATE wallet_balance
			SET coinsec='0', coinavg='$coinavg', stake='$stake', interest='$interest'
			WHERE time='$current_time' AND userid='$userid'";
		if ($dbwalletconn->query($query) === TRUE) {
					//
		} else {
			walletlog($dbwalletconn, time(), 'error', 'query failed. balance update failed', $userid, null, $addressid);
		}
	}

	if (date("d")=="01" || date("d")=="15") {
		foreach ($userids as $userid) {
			$userstake=$emercoin->getbalance($userid.":stake");
			if ((float)$userstake>0) {
				if ($emercoin->move($userid.":stake",$userid,(float)$userstake)) {
					//update balance
					$userbalance=$emercoin->getbalance($userid);
					$query = "UPDATE wallet_balance
						SET balance='$userbalance'
						WHERE time='$current_time' AND userid='$userid' AND coinsec='0'";
					if ($dbwalletconn->query($query) === TRUE) {
								//
					} else {
						walletlog($dbwalletconn, time(), 'error', 'query failed. balance update failed', $userid, null, $addressid);
					}
					//insert tx in wallet_transaction
					$query = "INSERT INTO wallet_transaction
					(userid, time, category, amount)
					VALUES
					('$userid', '$current_time', 'int_stake', '$userstake')";
					if ($dbwalletconn->query($query) === TRUE) {
						//
					} else {
						walletlog($dbwalletconn, time(), 'error', 'query failed. stake transaction insert failed', $userid, null, null);
					}
				} else {
					walletlog($dbwalletconn, time(), 'error', 'stake move failed.', $userid, null, null);
				}
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
