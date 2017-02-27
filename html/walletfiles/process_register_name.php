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
try {
$account_balance=$emercoin->getbalance($userid);
} catch (Exception $e) {
	walletlog($dbwalletconn, time(), 'error', 'Reg Name: get balance failed', $userid, null, null);
	echo "1";
	exit;
}
try {
	$getinfo=$emercoin->getinfo();
	$blocks=$getinfo['blocks'];
} catch (Exception $e) {
	walletlog($dbwalletconn, time(), 'error', 'Reg Name: get block count failed', $userid, null, null);
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

$name=trim($_POST['name']);
$value=$_POST['value'];
(int)$days=round($_POST['days'],0);
if ($days>5000) { $days=5000; }
if ($days==0) { $days=1; }
$current_time=time();


//check name availability
$name_taken=0;
try {
	$name_show=$emercoin->name_show($name);
} catch (Exception $e) {
	//walletlog($dbwalletconn, time(), 'error', 'Reg Name: Availability check failed', $userid, null, null);
	//echo "Availability check failed - Please try again later";
	//exit;
}

if ($name_show['expires_in']>0) {
	$name_taken=1;
	echo "2";
	exit;
}

//calculate costs
$newNameFee=0.01;
$nameLength=strlen($name);
$valueLength=strlen($value);
// get last pow reward
$query = "SELECT mint FROM blocks WHERE flags LIKE '%proof-of-work%' ORDER BY height DESC LIMIT 1";
$result = $dbconn->query($query);
while($row = $result->fetch_assoc())
{
	$pow_reward=$row['mint'];
}

(float)$newNameFee=round(bcmul($nvs_multiplier,bcdiv(bcsqrt(bcadd(bcmul($pow_reward,bcadd(1,bcdiv($days,365,8),8),8),floor(bcdiv(bcadd($nameLength,$valueLength,8),128,8)),8),8),100,8),8),4);

if ($name_taken==0 && $newNameFee<=$account_balance) {
		try {
			$emercoin->walletlock();
		} catch (Exception $e) {
			walletlog($dbwalletconn, time(), 'warn', 'Reg Name: walletlock failed', $userid, null, null);
		}
		try {
			$emercoin->walletpassphrase($wallet_password, 10, false);
			$txid=$emercoin->name_new($name, $value, (int)$days);
			$emercoin->walletlock();
			$emercoin->walletpassphrase($wallet_password, 99999999, true);
		} catch (Exception $e) {
			walletlog($dbwalletconn, time(), 'error', 'Reg Name: registration failed', $userid, null, null);
			echo "1";
			exit;
		}
		if ($txid!="" && $emercoin->move( $userid, "", $newNameFee)) {
			try {
				$rawtx=$emercoin->getrawtransaction($txid, 1);
			} catch (Exception $e) {
				walletlog($dbwalletconn, time(), 'error', 'Reg Name: get raw TX failed', $userid, null, null);
				echo "1";
				exit;
			}
			$registrationCosts=0;
			foreach ($rawtx['vout'] as $vout)  {
				$registrationCosts-=$vout['value'];
				if ($vout['n']==0) {
					$new_address=$vout['scriptPubKey']['addresses'][0];
				}
			}
			foreach ($rawtx['vin'] as $vin)  {
				$inputnumber=$vin['vout'];
				try {
					$inputtx=$emercoin->getrawtransaction($vin['txid'],1 );
				} catch (Exception $e) {
					walletlog($dbwalletconn, time(), 'error', 'Reg Name: get raw TX failed', $userid, null, null);
					echo "1";
					exit;
				}
				foreach ($inputtx['vout'] as $vout)  {
					if ($vout['n'] == $inputnumber) {
						$registrationCosts+=$vout['value'];
					}
				}
			}
			$registrationCosts=round($registrationCosts,8);
			// move earnings to stake and service accounts
			$tx_earnings=bcsub($newNameFee,$registrationCosts,8);
			$tx_earnings=bcsub($tx_earnings,0.01,8);
			$move_to_stake=bcmul($tx_earnings,$fee_to_stake,8);
			$move_to_service=bcsub($tx_earnings,$move_to_stake,8);
			try {
				$emercoin->move( "", "service", (float)$move_to_service);
			} catch (Exception $e) {
				walletlog($dbwalletconn, time(), 'error', 'Reg Name: Move to service failed', $userid, null, null);
				echo "1";
				exit;
			}
			try {
				$emercoin->move( "", "stake", (float)$move_to_stake);
			} catch (Exception $e) {
				walletlog($dbwalletconn, time(), 'error', 'Reg Name: Move to stake failed', $userid, null, null);
				echo "1";
				exit;
			}

			//save name registration tx in db
			$query = "INSERT INTO wallet_transaction
			(userid, time, category, txid, tx_details, fee, service_fee)
			VALUES
			('$userid', '$current_time', 'new_name', '$txid', '$name', '$registrationCosts', '$newNameFee')";
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
					walletlog($dbwalletconn, time(), 'error', 'Reg Name: Get new balance failed', $userid, null, null);
				}
				$insert_query = "INSERT INTO wallet_balance
						(userid, balance, time, coinsec)
						VALUES
						('$userid', '$account_balance', '$current_time', '$new_coinsec')";		
				if ($dbwalletconn->query($insert_query) === TRUE) {
					//write nvs entry into db
					$query = "INSERT INTO wallet_nvs
					(userid,name,registered_at)
					VALUES
					('$userid', '$name', '$blocks')";
					if ($dbwalletconn->query($query) === TRUE) {
						echo "0";
					} else {
						echo "1";
						exit;
					}
				} else {
					walletlog($dbwalletconn, time(), 'error', 'Reg Name: query failed. wallet_balance not updated', $userid, null, null);
					exit;
				}	
			} else {
				walletlog($dbwalletconn, time(), 'error', 'Reg Name: query failed. new_name tx insert failed', $userid, null, null);
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