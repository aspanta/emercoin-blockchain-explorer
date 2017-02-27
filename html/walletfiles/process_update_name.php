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

if (!isset($_POST['name']) || !isset($_POST['value']) || !isset($_POST['days'])) {
	echo "1";
	exit;
}

$userid=$_SESSION['userid'];
require_once __DIR__ . '/../../tools/include.php';

$address="";
if (isset($_POST['address'])) {
	$address=$_POST['address'];
	$validateaddress=$emercoin->validateaddress($address);
	$isvalid=$validateaddress['isvalid'];
	if ($isvalid) {
		$query = "SELECT userid
		FROM wallet_address
		WHERE address='$address'";
		$result = $dbwalletconn->query($query);
		$transferid=0;
		while($row = $result->fetch_assoc()) {
			$transferid=$row['userid'];
		}
	}
}

try {
$account_balance=$emercoin->getbalance($userid);
} catch (Exception $e) {
	walletlog($dbwalletconn, time(), 'error', 'Update Name: get balance failed', $userid, null, null);
	echo "1";
	exit;
}
try {
	$getinfo=$emercoin->getinfo();
	$blocks=$getinfo['blocks'];
} catch (Exception $e) {
	walletlog($dbwalletconn, time(), 'error', 'Update Name: get block count failed', $userid, null, null);
	echo "1";
	exit;
}

$query = "SELECT SUM(amount) AS amount, SUM(service_fee) AS service_fee FROM wallet_send_queue WHERE userid = '$userid' AND confirmations IS NULL";
$result = $dbwalletconn->query($query);
while($row = $result->fetch_assoc()) {
	$reserved_balance=($row['amount']+$row['service_fee']);
}
if ($reserved_balance=="") {$reserved_balance=0;}
(float)$account_balance-=$reserved_balance;

$name=$_POST['name'];
$value=$_POST['value'];
(int)$days=round($_POST['days'],0);
if ($days>5000) { $days=5000; }
$current_time=time();

//check name ownership
$query = "SELECT name FROM wallet_nvs WHERE userid = '$userid' AND BINARY name = '$name'";
$result = $dbwalletconn->query($query);
$name_found=0;
while($row = $result->fetch_assoc()) {
	$name=$row['name'];
	try {
		$name_show=$emercoin->name_show($name);
	} catch (Exception $e) {
		walletlog($dbwalletconn, time(), 'error', 'Update Name: validity check failed', $userid, null, null);
		echo "validity check failed - Please try again later";
		exit;
	}
	if (isset($name_show['expires_in'])) {
		if ($name_show['expires_in']>0) {
			$name_found=1;
		} else {
			echo "This name has expired";
			walletlog($dbwalletconn, time(), 'error', 'Update Name: name has expired', $userid, null, null);
			exit;
		}
	} else {
		echo "Name not found";
		walletlog($dbwalletconn, time(), 'error', 'Update Name: name not found', $userid, null, null);
		exit;
	}
}
//calculate costs
$updateNameFee=0.01;
$nameLength=strlen($name);
$valueLength=strlen($value);
// get last pow reward
$query = "SELECT mint FROM blocks WHERE flags LIKE '%proof-of-work%' ORDER BY height DESC LIMIT 1";
$result = $dbconn->query($query);
while($row = $result->fetch_assoc())
{
	$pow_reward=$row['mint'];
}
if ($address=="") {
	(float)$updateNameFee=round(bcmul($nvs_multiplier,bcdiv(bcsqrt(bcadd(bcmul($pow_reward,bcadd($update_name_basis_fee,bcdiv($days,365,8),8),8),floor(bcdiv(bcadd($nameLength,$valueLength,8),128,8)),8),8),100,8),8),4);
} else {
	$address=$_POST['address'];
	try {
		$validateaddress=$emercoin->validateaddress($address);
	} catch (Exception $e) {
		walletlog($dbwalletconn, time(), 'error', 'Update Name: validate address failed', $userid, null, null);
		echo "1";
		exit;
	}
	$isvalid=$validateaddress['isvalid'];
	if ($isvalid==1) {
		(float)$updateNameFee=round(bcadd(bcmul($nvs_multiplier,bcdiv(bcsqrt(bcadd(bcmul($pow_reward,bcadd($update_name_basis_fee,bcdiv($days,365,8),8),8),floor(bcdiv(bcadd($nameLength,$valueLength,8),128,8)),8),8),100,8),8),$nvs_move_address,8),4);
	} else {
		echo "1";
		exit;
	}	
}
if ($name_found==1 && $updateNameFee<=$account_balance) {
		try {
			$emercoin->walletlock();
		} catch (Exception $e) {
			walletlog($dbwalletconn, time(), 'warn', 'Update Name: walletlock failed', $userid, null, null);
		}	
		try {
			$emercoin->walletpassphrase($wallet_password, 20, false);
			if ($address=="") {
				$txid=$emercoin->name_update($name, $value, (int)$days);
			} else {
				$txid=$emercoin->name_update($name, $value, (int)$days, $address);
			}
			$emercoin->walletlock();
			$emercoin->walletpassphrase($wallet_password, 99999999, true);
		} catch (Exception $e) {
			walletlog($dbwalletconn, time(), 'error', 'Update Name: name update failed', $userid, null, null);
			echo $e;
			exit;
		}
		if ($txid!="" && $emercoin->move( $userid, "", $updateNameFee)) {
			try {
				$rawtx=$emercoin->getrawtransaction($txid, 1);
			} catch (Exception $e) {
				walletlog($dbwalletconn, time(), 'error', 'Update Name: get raw TX failed', $userid, null, null);
				echo "1";
				exit;
			}
			$updateCosts=0;
			foreach ($rawtx['vout'] as $vout)  {
				$updateCosts-=$vout['value'];
			}
			foreach ($rawtx['vin'] as $vin)  {
				$inputnumber=$vin['vout'];
				try {
					$inputtx=$emercoin->getrawtransaction($vin['txid'],1 );
				} catch (Exception $e) {
					walletlog($dbwalletconn, time(), 'error', 'Update Name: get raw TX failed', $userid, null, null);
					echo "1";
					exit;
				}
				foreach ($inputtx['vout'] as $vout)  {
					if ($vout['n'] == $inputnumber) {
						$updateCosts+=$vout['value'];
					}
				}
			}
			$updateCosts=round($updateCosts,8);
			// move earnings to stake and service accounts
			$tx_earnings=bcsub($updateNameFee,$updateCosts,8);
			//$tx_earnings=bcsub($tx_earnings,0.01,8);
			$move_to_stake=bcmul($tx_earnings,$fee_to_stake,8);
			$move_to_service=bcsub($tx_earnings,$move_to_stake,8);
			try {
				$emercoin->move( "", "service", (float)$move_to_service);
			} catch (Exception $e) {
				walletlog($dbwalletconn, time(), 'error', 'Update Name: Move to service failed', $userid, null, null);
				echo "1";
				exit;
			}
			try {
				$emercoin->move( "", "stake", (float)$move_to_stake);
			} catch (Exception $e) {
				walletlog($dbwalletconn, time(), 'error', 'Update Name: Move to stake failed', $userid, null, null);
				echo "1";
				exit;
			}

			//save name update tx in db
			$query = "INSERT INTO wallet_transaction
			(userid, time, category, txid, tx_details, fee, service_fee)
			VALUES
			('$userid', '$current_time', 'new_update', '$txid', '$name', '$updateCosts', '$updateNameFee')";
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
					walletlog($dbwalletconn, time(), 'error', 'Update Name: get new balance failed', $userid, null, null);
				}
				$insert_query = "INSERT INTO wallet_balance
						(userid, balance, time, coinsec)
						VALUES
						('$userid', '$account_balance', '$current_time', '$new_coinsec')";		
				if ($dbwalletconn->query($insert_query) === TRUE) {
					//update nvs entry
					$query = "UPDATE wallet_nvs
					SET registered_at = $blocks WHERE userid='$userid' AND name='$name'";
					if (isset($transferid)) {
						if ($transferid!=0 && $transferid!=$userid) {
							$query = "UPDATE wallet_nvs
							SET registered_at = '$blocks', userid = '$transferid' WHERE userid='$userid' AND name='$name'";
						} else {
							$query = "DELETE FROM wallet_nvs
							WHERE userid='$userid' AND name='$name'";
						}
					}
					if ($dbwalletconn->query($query) === TRUE) {
						echo "0";
					} else {
						echo "1";
						exit;
					}
				} else {
					walletlog($dbwalletconn, time(), 'error', 'Update Name: query failed. wallet_balance not updated', $userid, null, null);
					echo "1";
					exit;
				}	
			} else {
				walletlog($dbwalletconn, time(), 'error', 'Update Name: query failed. new_name tx insert failed', $userid, null, null);
				echo "1";
				exit;
			}
		} else {
			echo "1";
			exit;
		}		
} else {
	echo "1";
	exit;
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