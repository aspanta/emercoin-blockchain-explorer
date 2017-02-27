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
$reserved_balance=0;
try {
	$account_balance=$emercoin->getbalance($userid);

	$query = "SELECT SUM(amount) AS amount, SUM(service_fee) AS service_fee FROM wallet_send_queue WHERE userid = '$userid' AND confirmations IS NULL";
	$result = $dbwalletconn->query($query);
	while($row = $result->fetch_assoc()) {
		$reserved_balance=($row['amount']+$row['service_fee']);
	}
	if ($reserved_balance=="") {$reserved_balance=0;}

	$account_balance-=$reserved_balance;
} catch (Exception $e) {
	$account_balance="connection error - failed to receive";
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
<div class="container">

	<ol class="breadcrumb">
		<li><a href="/wallet/overview"><?php echo lang('OVERVIEW_OVERVIEW'); ?></a></li>
		<li><a href="/wallet/transactions"><?php echo lang('TRANSACTIONS_TRANSACTIONS'); ?></a></li>
		<li><a href="/wallet/addressbook"><?php echo lang('ADDRESS_BOOK'); ?></a></li>
		<li class="active"><?php echo lang('SEND_SEND'); ?></li>
		<li><a href="/wallet/receive"><?php echo lang('RECEIVE_RECEIVE'); ?></a></li>
		<li><a href="/wallet/nvs"><?php echo lang('NVS_NVS'); ?></a></li>
	</ol>

	<div class="row">
		<div class="col-md-8">
			<div class="panel panel-default">
				<div class="panel-heading"><?php echo lang('SEND_COINS'); ?> | <?php echo lang('BALANCE_BALANCE'); ?>: <?php echo $account_balance; ?> EMC</div>
				<div class="panel-body">
					<div class="row">
					  <div class="col-md-4">
						<div class="input-group">
						  <input type="text" id="textAmount" class="form-control" placeholder="0.00000000">
						  <span class="input-group-addon">EMC</span>
						</div>
					  </div>
					  <div class="col-md-6">
						  <input type="text" id="textAddress" class="form-control" placeholder="EMC-<?php echo lang('ADDRESS_ADDRESS'); ?>">
					  </div>
					  <div class="col-md-1">
							<button class="btn btn-danger disabled" id="btnSendCoins" type="button"><?php echo lang('SEND_COINS'); ?></button>
					  </div>
					</div><!-- /.row -->
					<div class="row">
						<div class="col-md-offset-4 col-md-6">
						<br>
							<select class="selectpicker" id="selectpickerName">
								
							</select>
						</div>
					</div>
				</div>
				<div class="panel-footer"><footer class="text-muted"><i><sub><span id="spantxfee"></span> </sub></i><sub><span id="spantxinfo"></span></sub></footer></div>
			</div>
		</div>		
	</div>	
</div>	

<script>
$('.selectpicker').selectpicker({
	style: 'btn-default',
	size: 5
});
checkValidNames();
validAmount=0;
validAddress=0;
mineAddress=0;
amount=0;
$('#textAmount').on('change keyup paste click', function(e) {
	validateAmount();
});

$('#textAddress').on('change keyup paste click', function(e) {
	validateAddress($('#textAddress').val());
});

$("#btnSendCoins").click(function() {
	sentCoins();
});

function validateAddress(address) {
	jQuery.ajaxSetup({async:false});
	$.get('<?php echo $blockchainurl; ?>/api/address/isvalid/'+address,function(data){
		if(data==1) {
			validAddress=1;
		} else {
			validAddress=0;
		}
	});	
	$.get('<?php echo $blockchainurl; ?>/api/address/ismine/'+address,function(data){
		withdraw_fee=<?php echo $withdraw_fee; ?>;
		send_to_another_account_fee=<?php echo $send_to_another_account_fee; ?>;
		if (mineAddress==1) {
			fee=send_to_another_account_fee;
		} else {
			fee=withdraw_fee;
		}
		if(data==1) {
			mineAddress=1;
			if (validAddress==1) {
				$("#btnSendCoins").removeClass("btn-danger");
				$("#btnSendCoins").addClass("btn-primary");
				$("#btnSendCoins").removeClass("disabled");
				$("#spantxfee").text("<?php echo lang('TRANSACTION_EMC'); ?> "+fee+" EMC");
				$("#spantxinfo").text("| <?php echo lang('THIS_SUBSTRACTBALANCE'); ?>");
				validateAmount();
			} else {
				$("#btnSendCoins").addClass("disabled");
				$("#spantxfee").text("");
				$("#spantxinfo").text("");
			} 
		} else {
			mineAddress=0;
			if (validAddress==1) {
				$("#btnSendCoins").removeClass("btn-primary");
				$("#btnSendCoins").addClass("btn-danger");
				$("#btnSendCoins").removeClass("disabled");
				$("#spantxfee").text("<?php echo lang('TRANSACTION_EMC'); ?> "+fee+" EMC");
				$("#spantxinfo").text("| <?php echo lang('THIS_SUBSTRACTBALANCE'); ?>");
				validateAmount();
			} else {
				$("#btnSendCoins").addClass("disabled");
				$("#spantxfee").text("");
				$("#spantxinfo").text("");
			} 	
		}
	});	
	jQuery.ajaxSetup({async:true});
};

function validateAmount() {
	number=$('#textAmount').val();
	number=number.replace("," , ".");
	$('#textAmount').val(number);
	if ($.isNumeric(number)) {
		if (number>=0.000001) {
			amount=number;
			amountWOfee=Math.round($('#textAmount').val() * 1000000) / 1000000;
			amount=(parseFloat(amount)+parseFloat(fee));
			amount=Math.round(amount * 1000000) / 1000000;
			
			if (amount<=parseFloat(<?php echo $account_balance; ?>)) {
				validAmount=1;
			} else {
				validAmount=0;
			}
			
		}
		else {
			validAmount=0;	
		}
	} else {
		validAmount=0;	
	}
	if (validAmount==1 && validAddress==1) {
	   $("#btnSendCoins").removeClass("disabled");
	   $("#spantxinfo").text("| <?php echo lang('THIS_SUBSTRACTBALANCE2'); ?>");
    } else {
        $("#btnSendCoins").addClass("disabled");
		$("#spantxinfo").text("");
    } 
};

function sentCoins()
{	
	$("#btnSendCoins").addClass("disabled");
	var request = $.ajax({
		type: "POST",
		url: "/walletfiles/process_send.php",
		data: { amount: amountWOfee, address:$('#textAddress').val(), connid: '<?php echo $randomString; ?>' }
	});
	request.done(function( response ) {
		if (response=='0') {
			$.notify("<?php echo lang('TRANSACTION_QUEUE'); ?>","success");
			setTimeout("location.reload(true);",750);
		}
		else if (response=='1') {
			$.notify("Something went wrong.","error");
			$("#btnSendCoins").removeClass("disabled");
		} else if (response=='10') {
			$.notify(amountWOfee+" <?php echo lang('EMC_TO'); ?> "+$('#textAddress').val() ,"success");
			setTimeout("location.reload(true);",750);
		} else if (response=='127') {
			$.notify("Invalid session","error");
			setTimeout("location.reload(true);",700);
		} else {
			$.notify(response,"error");
		}
	});
}

function checkValidNames()
{	
	var request = $.ajax({
		type: "POST",
		url: "/walletfiles/process_load_addressbook.php"
	});
	request.done(function( response ) {
		$('#selectpickerName').html(response);
		$('#selectpickerName').selectpicker('refresh');
		$('#selectpickerName').selectpicker('val', '-');
	});
}

$('#selectpickerName').on('change', function(e) {
	if ($('#selectpickerName').val()!="-") {
		$('#textAddress').val($('#selectpickerName').val());
		validateAddress($('#textAddress').val());
		validateAddress($('#textAddress').val());
	} else {
		$('#textAddress').val('');
		$("#btnSendCoins").addClass("disabled");
		$("#spantxfee").text("");
		$("#spantxinfo").text("");
	}
});
</script>	
