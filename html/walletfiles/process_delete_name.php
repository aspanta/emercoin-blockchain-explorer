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

if (!isset($_POST['name'])) {
	echo "1";
	exit;
}

$userid=$_SESSION['userid'];
require_once __DIR__ . '/../../tools/include.php';
try {
	$account_balance=$emercoin->getbalance($userid);
} catch (Exception $e) {
	walletlog($dbwalletconn, time(), 'error', 'Delete Name: get balance failed', $userid, null, null);
	echo "1";
	exit;
}	
try {
	$getinfo=$emercoin->getinfo();
	$blocks=$getinfo['blocks'];
} catch (Exception $e) {
	walletlog($dbwalletconn, time(), 'error', 'Delete Name: get block count failed', $userid, null, null);
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

$current_time=time();

$name=$_POST['name'];
//check name ownership
$query = "SELECT name FROM wallet_nvs WHERE userid = '$userid' AND BINARY name = '$name'";
$result = $dbwalletconn->query($query);
$name_found=0;
while($row = $result->fetch_assoc()) {
	$name=$row['name'];
	$name_show=$emercoin->name_show($name);
	if ($emercoin->name_show($name)) {
		$name_found=1;
	}
}

//calculate costs
$deleteNameFee=$delete_name_basis_fee;

if ($name_found==1 && $deleteNameFee<=$account_balance) {
	try {	
		$emercoin->walletlock();
		$emercoin->walletpassphrase($wallet_password, 30, false);
		$txid=$emercoin->name_delete($name);
		$emercoin->walletlock();
		$emercoin->walletpassphrase($wallet_password, 99999999, true);
	} catch (Exception $e) {
		walletlog($dbwalletconn, time(), 'error', 'Delete Name: Deletion failed', $userid, null, null);
		echo "1";
		exit;
	}
	if ($txid!="" && $emercoin->move( $userid, "", $deleteNameFee)) {
			try {
				$rawtx=$emercoin->getrawtransaction($txid, 1);
			} catch (Exception $e) {
				walletlog($dbwalletconn, time(), 'error', 'Delete Name: Get raw TX failed', $userid, null, null);
				echo "1";
				exit;
			}
			$deleteCosts=0;
			foreach ($rawtx['vout'] as $vout)  {
				$deleteCosts-=$vout['value'];
			}
			foreach ($rawtx['vin'] as $vin)  {
				$inputnumber=$vin['vout'];
				$inputtx=$emercoin->getrawtransaction($vin['txid'],1 );
				foreach ($inputtx['vout'] as $vout)  {
					if ($vout['n'] == $inputnumber) {
						$deleteCosts+=$vout['value'];
					}
				}
			}
			$deleteCosts=round($deleteCosts,8);
			// move earnings to stake and service accounts
			$tx_earnings=bcsub($deleteNameFee,$deleteCosts,8);
			//$tx_earnings=bcsub($tx_earnings,0.01,8);
			$move_to_stake=bcmul($tx_earnings,$fee_to_stake,8);
			$move_to_service=bcsub($tx_earnings,$move_to_stake,8);
			try {
				$emercoin->move( "", "service", (float)$move_to_service);
			} catch (Exception $e) {
				walletlog($dbwalletconn, time(), 'error', 'Delete Name: Move to service failed', $userid, null, null);
				echo "1";
				exit;
			}
			try {
				$emercoin->move( "", "stake", (float)$move_to_stake);
			} catch (Exception $e) {
				walletlog($dbwalletconn, time(), 'error', 'Delete Name: Move to stake failed', $userid, null, null);
				echo "1";
				exit;
			}
			//save name update tx in db
			$query = "INSERT INTO wallet_transaction
			(userid, time, category, txid, tx_details, fee, service_fee)
			VALUES
			('$userid', '$current_time', 'new_delete', '$txid', '$name', '$deleteCosts', '$deleteNameFee')";
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
					walletlog($dbwalletconn, time(), 'error', 'Delete Name: Get new balance failed', $userid, null, null);
				}
				$insert_query = "INSERT INTO wallet_balance
						(userid, balance, time, coinsec)
						VALUES
						('$userid', '$account_balance', '$current_time', '$new_coinsec')";		
				if ($dbwalletconn->query($insert_query) === TRUE) {
					//delete nvs entry
					$query = "DELETE FROM wallet_nvs
					WHERE userid='$userid' AND BINARY name='$name'";
					if ($dbwalletconn->query($query) === TRUE) {
						echo "0";
					} else {
						echo "1";
						exit;
					}
				} else {
					walletlog($dbwalletconn, time(), 'error', 'Delete Name: query failed. wallet_balance not updated', $userid, null, null);
					echo "1";
					exit;
				}	
			} else {
				walletlog($dbwalletconn, time(), 'error', 'Delete Name: query failed. new_name tx insert failed', $userid, null, null);
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