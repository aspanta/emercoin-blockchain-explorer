<?php
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
	echo '<script type="text/javascript">
		window.location = "/wallet"
	</script>';
	exit;
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

function TrimTrailingZeroes($nbr) {
    return strpos($nbr,'.')!==false ? rtrim(rtrim($nbr,'0'),'.') : $nbr;
}

function timeAgo ($time) {
    $time = time() - $time;

    $tokens = array (
        86400 => lang('DAYS_DAYS'),
        3600 => lang('HOURS_HOURS'),
        60 => lang('MINUTES_MINUTES'),
        1 => lang('SECONDS_SECONDS')
    );

    foreach ($tokens as $unit => $text) {
        if ($time < $unit) continue;
        $numberOfUnits = floor($time / $unit);
        return $numberOfUnits.' '.$text;
    }
}
?>
<div class="container">

	<ol class="breadcrumb">
		<li><a href="/wallet/overview"><?php echo lang('OVERVIEW_OVERVIEW'); ?></a></li>
		<li class="active"><?php echo lang('TRANSACTIONS_TRANSACTIONS'); ?></li>
		<li><a href="/wallet/addressbook"><?php echo lang('ADDRESS_BOOK'); ?></a></li>
		<li><a href="/wallet/send"><?php echo lang('SEND_SEND'); ?></a></li>
		<li><a href="/wallet/receive"><?php echo lang('RECEIVE_RECEIVE'); ?></a></li>
		<li><a href="/wallet/nvs"><?php echo lang('NVS_NVS'); ?></a></li>
	</ol>

	<div class="row">
		<div class="col-md-12">
			<div class="panel panel-default">
				<div class="panel-heading"><?php echo lang('ALL_TRANSACTIONS'); ?></div>
				<div class="panel-body">
					<table class="table">
					<tr><th><?php echo lang('CATEGORY_CATEGORY'); ?></th><th><?php echo lang('TX_ID'); ?></th><th><?php echo lang('AMOUNT_EMC'); ?></th><th><?php echo lang('FEE_FEE'); ?></th><th><?php echo lang('BALANCE_BALANCE'); ?></th><th><?php echo lang('FROM_TO'); ?></th><th><?php echo lang('TIME_AGO'); ?></th></tr>
					<?php
					$query = "SELECT balance
					FROM wallet_balance
					WHERE userid = '$userid'
					ORDER BY time DESC LIMIT 1";
					$result = $dbwalletconn->query($query);
					while($row = $result->fetch_assoc()) {
						$balance=$row['balance'];
					}
					$current_balance=$balance;
					$query = "SELECT tx.category, tx.amount, tx.fee, tx.service_fee, tx.address, tx.txid, tx.tx_details, tx.addressid, tx.time, tx.confirmations, address.address AS own_address, address.label AS own_label, book.name AS book_name
					FROM wallet_transaction as tx
					LEFT JOIN wallet_address as address ON address.id=tx.addressid
					LEFT JOIN wallet_addressbook as book ON book.address=tx.address AND book.userid = '$userid'
					WHERE tx.userid = '$userid'
					ORDER BY tx.time DESC";
					$result = $dbwalletconn->query($query);
					if ($result !== false) {
						while($row = $result->fetch_assoc()) {
							$category=$row['category'];
							$amount=$row['amount'];
							$fee=$row['fee'];
							$service_fee=$row['service_fee'];
							$txidArr=explode(':',$row['txid']);
							$txid=$txidArr[0];
							$tx_id_short = substr($txid, 0, 4)."...".substr($txid, -4);
							$address=$row['address'];
							$tx_details=$row['tx_details'];
							$own_address=$row['own_address'];
							$own_label=$row['own_label'];
							if ($own_label!="") {
								$own_address_label='<abbr title="'.$own_address.'">'.$own_label.'</abbr>';
							} else {
								$own_address_label=$own_address;
							}
							$book_name=$row['book_name'];
							if ($book_name!="") {
								$own_address_name='<abbr title="'.$address.'">'.$book_name.'</abbr>';
							} else {
								$own_address_name=$address;
							}
							$time=$row['time'];
							$read_time=date('d.m.Y H:i', $time);
							$confirmations=$row['confirmations'];
							if ($confirmations=="-1") {
								$confirmations="";
							}
							if ($category=="receive") {
								echo '<tr><td>'.lang('RECEIVE_RECEIVE').'</td><td><a href="/tx/'.$txid.'" class="btn btn-primary btn-xs" role="button">'.$tx_id_short.'</a></td><th>'.$amount.' </th><td>0.00</td><td>'.$current_balance.' EMC</td><td>'.$own_address_label.'</td><td><abbr title="'.$read_time.'">'.timeAgo($time).'</abbr></td><td width="10px">'.$confirmations.'</td></tr>';
							}
							if ($category=="int_rec") {
								echo '<tr><td>'.lang('RECEIVE_RECEIVE').'</td><td></td><th>'.$amount.' </th><td>0.00</td><td>'.$current_balance.' EMC</td><td>'.$own_address_label.'</td><td><abbr title="'.$read_time.'">'.timeAgo($time).'</abbr></td><td width="10px"></td></tr>';
							}
							if ($category=="send") {
								echo '<tr><td>'.lang('SEND_SEND').'</td><td><a href="/tx/'.$txid.'" class="btn btn-primary btn-xs" role="button">'.$tx_id_short.'</a></td><th class="text-danger">-'.$amount.' </th><td>'.$service_fee.'</td><td>'.$current_balance.' EMC</td><td>'.$own_address_name.'</td><td><abbr title="'.$read_time.'">'.timeAgo($time).'</abbr></td><td width="10px"></td></tr>';
								$amount=$amount*(-1);
							}
							if ($category=="int_send") {
								echo '<tr><td>'.lang('SEND_SEND').'</td><td></td><th class="text-danger">-'.$amount.' </th><td>'.$service_fee.'</td><td>'.$current_balance.' EMC</td><td>'.$own_address_name.'</td><td><abbr title="'.$read_time.'">'.timeAgo($time).'</abbr></td><td width="10px"></td></tr>';
								$amount=$amount*(-1);
							}
							if ($category=="int_stake") {
								echo '<tr><td>'.lang('STAKE_STAKE').'</td><td></td><th class="text-success">'.$amount.' </th><td>0.00</td><td>'.$current_balance.' EMC</td><td></td><td><abbr title="'.$read_time.'">'.timeAgo($time).'</abbr></td><td width="10px"></td></tr>';
							}
							if ($category=="new_addr") {
								echo '<tr><td>'.lang('NEW_ADDRESS').'</td><td><a href="/tx/'.$txid.'" class="btn btn-primary btn-xs" role="button">'.$tx_id_short.'</a></td><th class="text-info">-'.$service_fee.'</th><td>0.00</td><td>'.$current_balance.' EMC</td><td>'.$address.'</td><td><abbr title="'.$read_time.'">'.timeAgo($time).'</abbr></td><td width="10px"></td></tr>';
							}
							if ($category=="new_name") {
								echo '<tr><td>'.lang('NEW_NAME').'</td><td><a href="/tx/'.$txid.'" class="btn btn-primary btn-xs" role="button">'.$tx_id_short.'</a></td><th class="text-info">-'.$service_fee.' </th><td>0.00</td><td>'.$current_balance.' EMC</td><td>'.$tx_details.'</td><td><abbr title="'.$read_time.'">'.timeAgo($time).'</abbr></td><td width="10px"></td></tr>';
							}
							if ($category=="new_update") {
								echo '<tr><td>'.lang('NAME_UPDATE').'</td><td><a href="/tx/'.$txid.'" class="btn btn-primary btn-xs" role="button">'.$tx_id_short.'</a></td><th class="text-info">-'.$service_fee.' </th><td>0.00</td><td>'.$current_balance.' EMC</td><td>'.$tx_details.'</td><td><abbr title="'.$read_time.'">'.timeAgo($time).'</abbr></td><td width="10px"></td></tr>';
							}
							if ($category=="new_delete") {
								echo '<tr><td>'.lang('NAME_DELETE').'</td><td><a href="/tx/'.$txid.'" class="btn btn-primary btn-xs" role="button">'.$tx_id_short.'</a></td><th class="text-info">-'.$service_fee.' </th><td>0.00</td><td>'.$current_balance.' EMC</td><td>'.$tx_details.'</td><td><abbr title="'.$read_time.'">'.timeAgo($time).'</abbr></td><td width="10px"></td></tr>';
							}
							$current_balance=TrimTrailingZeroes(bcsub($current_balance,bcsub($amount,$service_fee,6),6));
						}
					}
					?>
					</table>
				</div>
			</div>
		</div>
	</div>
</div>
