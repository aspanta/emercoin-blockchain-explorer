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
	location.reload(true);
	exit;
}

$account_balance=$emercoin->getbalance($userid);

$query = "SELECT SUM(amount) AS amount, SUM(service_fee) AS service_fee FROM wallet_send_queue WHERE userid = '$userid' AND confirmations IS NULL";
$result = $dbwalletconn->query($query);
while($row = $result->fetch_assoc()) {
	$reserved_balance=($row['amount']+$row['service_fee']);
}
if ($reserved_balance=="") {$reserved_balance=0;}

$account_balance-=$reserved_balance;

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

$query = "SELECT COUNT(id) AS addresses FROM wallet_address WHERE userid = '$userid'";
$result = $dbwalletconn->query($query);
while($row = $result->fetch_assoc()) {
	$address_count=$row['addresses'];
}
?>
<div class="container">
	
	<ol class="breadcrumb">
		<li><a href="/wallet/overview"><?php echo lang('OVERVIEW_OVERVIEW'); ?></a></li>
		<li><a href="/wallet/transactions"><?php echo lang('TRANSACTIONS_TRANSACTIONS'); ?></a></li>
		<li><a href="/wallet/addressbook"><?php echo lang('ADDRESS_BOOK'); ?></a></li>
		<li><a href="/wallet/send"><?php echo lang('SEND_SEND'); ?></a></li>
		<li class="active"><?php echo lang('RECEIVE_RECEIVE'); ?></li>
		<li><a href="/wallet/nvs"><?php echo lang('NVS_NVS'); ?></a></li>
	</ol>

	<div class="row">
		<div class="col-md-8">
			<div class="panel panel-default">
				<div class="panel-heading"><?php echo lang('ADDRESSES_ADDRESSES'); ?></div>
				<div class="panel-body">
					<table class="table table-responsive">
					<tr><th><?php echo lang('ADDRESS_ADDRESS'); ?></th><th></th><th><?php echo lang('RECEIVED_RECEIVED'); ?></th><th><?php echo lang('LABEL_LABEL'); ?></th></tr>
					<?php
					$query = "SELECT id, address, label FROM wallet_address WHERE userid = '$userid' ORDER BY id";
					$result = $dbwalletconn->query($query);
					while($row = $result->fetch_assoc()) {
						$address=$row['address'];
						$label=$row['label'];
						$addressid=$row['id'];
						$query2 = "SELECT SUM(amount) AS amount FROM wallet_transaction WHERE addressid = '$addressid'";
						$result2 = $dbwalletconn->query($query2);
						while($row2 = $result2->fetch_assoc()) {
							$received=round($row2['amount'],8);
							if ($received=="") { $received=0; }
						}
						echo '<tr><td>'.$address.'</td><td><button class="btn btn-xs btn-primary" type="button" onclick="javascript:genQRcode(\''.$address.'\');"><i class="fa fa-qrcode"></i></button></td><td>'.$received.' EMC</td><td><input type="text" id="'.$addressid.'" onblur="updateLabel('.$addressid.', \''.$label.'\');"class="form-control" maxlength="50" placeholder="Label" value="'.$label.'"></td></tr>';
						echo '<tr><td colspan="4"><div class="well" id="'.$address.'"></div></td></tr>';
					}
					?>
					</table>
				</div>
			</div>
		</div>	
		<div class="col-md-4">
			<div class="panel panel-default">
				<div class="panel-heading"><?php echo lang('NEW_ADDRESS'); ?></div>
				<div class="panel-body">
					<input type="text" id="textLabel" class="form-control" maxlength="50" placeholder="<?php echo lang('LABEL_LABEL'); ?>"><br>
					<button class="btn btn-success disabled" id="btnGetAddress" type="button"><?php echo lang('GET_ADDRESS'); ?></button><br>
					<span id="currentbalance"></span>
				</div>
			</div>
		</div>			
	</div>	
</div>	


<script>
address_count=<?php echo $address_count; ?>;
max_free_address=<?php echo $max_addresses; ?>;
account_balance=<?php echo $account_balance; ?>;
price_address=<?php echo $price_extra_address; ?>;

$(".well").hide();

if (address_count>=max_free_address) {
	$("#btnGetAddress").text("<?php echo lang('BUY_ADDRESS'); ?> ("+price_address+" EMC)");
	$("#currentbalance").html("<sub><?php echo lang('BALANCE_BALANCE'); ?>: "+account_balance+" EMC</sub>");
	if (account_balance>=price_address) {
		$("#btnGetAddress").removeClass("disabled");
		$("#btnGetAddress").removeClass("btn-success");
		$("#btnGetAddress").addClass("btn-info");
	} else {
		$("#btnGetAddress").removeClass("btn-success");
		$("#btnGetAddress").addClass("btn-danger");
	}	
} else {
	$("#btnGetAddress").removeClass("disabled");
}

$("#btnGetAddress").click(function() {
	requestAddress();
});

function requestAddress()
{	
	$("#btnGetAddress").addClass("disabled");
	var request = $.ajax({
		type: "POST",
		url: "/walletfiles/get_newaddress.php",
		data: { label:$('#textLabel').val(), connid: '<?php echo $randomString; ?>' }
	});
	request.done(function( response ) {
		if (response=='0') {
			$.notify("<?php echo lang('NEW_ADDRESSCREATED'); ?>","success");
			setTimeout("location.reload(true);",700);
		}
		else if (response=='1') {
			$.notify("<?php echo lang('SOMETHING_WRONG'); ?>","error");
			$("#btnGetAddress").removeClass("disabled");
		} else {
			$.notify(response,"error");
		}
	});
}

function updateLabel(id, label)
{	
	if (label!==$('#'+id).val()) {
		var request = $.ajax({
			type: "POST",
			url: "/walletfiles/update_label.php",
			data: { label:$('#'+id).val(), addressid:id, connid: '<?php echo $randomString; ?>' }
		});
		request.done(function( response ) {
			if (response=='0') {
					$.notify("<?php echo lang('LABEL_CHANGED'); ?>","success");
			}
			else if (response=='1') {
				$.notify("<?php echo lang('SOMETHING_WRONG'); ?>","error");
			} else if (response=='127') {
				$.notify("Invalid session","error");
				setTimeout("location.reload(true);",700);
			}else {
				$.notify(response,"error");
			}
		});	
	}
}
function genQRcode(address) {
$('#'+address).toggle(200);
$('#'+address).text('');
	var qrcode = new QRCode(address, {
		text: address,
		width: 128,
		height: 128,
		colorDark : "#000000",
		colorLight : "#ffffff",
		correctLevel : QRCode.CorrectLevel.H
	});
}
</script>	

<script src="../js/qrcode.min.js" type="text/javascript"></script>