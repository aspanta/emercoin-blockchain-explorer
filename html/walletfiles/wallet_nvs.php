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

$query = "SELECT COUNT(id) AS addresses FROM wallet_address WHERE userid = '$userid'";
$result = $dbwalletconn->query($query);
while($row = $result->fetch_assoc()) {
	$address_count=$row['addresses'];
}
// get last pow reward
$query = "SELECT mint FROM blocks WHERE flags LIKE '%proof-of-work%' ORDER BY height DESC LIMIT 1";
	$result = $dbconn->query($query);
	while($row = $result->fetch_assoc())
	{
		$pow_reward=$row['mint'];
	}
?>
<div class="container">

	<ol class="breadcrumb">
		<li><a href="/wallet/overview"><?php echo lang('OVERVIEW_OVERVIEW'); ?></a></li>
		<li><a href="/wallet/transactions"><?php echo lang('TRANSACTIONS_TRANSACTIONS'); ?></a></li>
		<li><a href="/wallet/addressbook"><?php echo lang('ADDRESS_BOOK'); ?></a></li>
		<li><a href="/wallet/send"><?php echo lang('SEND_SEND'); ?></a></li>
		<li><a href="/wallet/receive"><?php echo lang('RECEIVE_RECEIVE'); ?></a></li>
		<li class="active"><?php echo lang('NVS_NVS'); ?></li>
	</ol>

	<div class="row">
		<div class="col-md-12">
			<div class="panel panel-default">
				<div class="panel-heading"><?php echo lang('REGISTER_PAIR'); ?> | <?php echo lang('BALANCE_BALANCE'); ?>: <?php echo $account_balance; ?> EMC</div>
				<div class="panel-body">
					<div class="row">
					  <div class="col-md-4">
							<input type="text" id="textName" class="form-control" maxlength="1000" placeholder="<?php echo lang('NAME_NAME'); ?>"><br>
							<button class="btn btn-success disabled" id="btnClaimName" type="button"><?php echo lang('CLAIM_NAME'); ?></button>
							<abbr title="<?php echo lang('CLAIM_INFO'); ?>"><i class="fa fa-question-circle"></i></abbr>
					  </div>
					  <div class="col-md-6">
							<textarea style="resize: none;" rows="4" cols="50" id="textValue" name="textValue" class="form-control" maxlength="5000" placeholder="<?php echo lang('VALUE_VALUENVS'); ?>"></textarea>
					  </div>
					  <div class="col-md-2">
							<input type="text" id="textDays" class="form-control" placeholder="<?php echo lang('DAYS_DAYS'); ?>">
					  </div>
					  <br><br><br>
					  <div class="col-md-2">
							<button class="btn btn-success disabled" id="btnRegisterName" type="button"><?php echo lang('REGISTER_NAME'); ?></button> 
							<abbr title="<?php echo lang('THE_LATER'); ?>"><i class="fa fa-question-circle"></i></abbr>
					  </div>
					</div>
				</div>
				<div class="panel-footer"><footer class="text-muted"><sub><span id="spantxinfo"></span></sub></footer></div>
			</div>
		</div>		
	</div>	
	
	<br>
	
	<div class="row">
		<div class="col-md-12">
			<div class="panel panel-default">
				<div class="panel-heading"><?php echo lang('UPDATE_PAIR'); ?> | <?php echo lang('BALANCE_BALANCE'); ?>: <?php echo $account_balance; ?> EMC</div>
				<div class="panel-body">
					<div class="row">
						<div class="col-md-4">
							<select class="selectpicker" id="selectpickerName">
								
							</select>
						</div>
						<div class="col-md-6">
							<textarea style="resize: none;" rows="4" cols="50" id="textUpdateValue" name="textUpdateValue" class="form-control" maxlength="5000" placeholder="<?php echo lang('VALUE_VALUENVS'); ?>"></textarea>
						</div>
						<div class="col-md-2">
							<input type="text" id="textUpdateDays" class="form-control" placeholder="<?php echo lang('ADDITIONAL_DAYS'); ?>">
						</div>
						<br><br><br>
						<div class="col-md-2">
							<button class="btn btn-info disabled" id="btnUpdateName" type="button"><?php echo lang('UPDATE_NAME'); ?></button> 
						</div>
					</div>
					<div class="row">
						<div class="col-md-offset-4 col-md-2">
							<br>
							<footer class="text-muted"><sub><span id="spanaddressownerinfo"></span></sub></footer>
						</div>
						<div class="col-md-4">
							<br>
							<input type="text" id="textUpdateAddress" class="form-control" maxlength="50" placeholder="<?php echo lang('MOVE_ADDRESS'); ?>">
						</div>
					</div>
				</div>
				<div class="panel-footer"><footer class="text-muted"><sub><span id="spantxupdateinfo"></span></sub></footer></div>
			</div>
		</div>		
	</div>	
	
	<br>
	
	<div class="row">
		<div class="col-md-12">
			<div class="panel panel-default">
				<div class="panel-heading"><?php echo lang('DELETE_PAIR'); ?> | <?php echo lang('BALANCE_BALANCE'); ?>: <?php echo $account_balance; ?> EMC</div>
				<div class="panel-body">
					<div class="row">
						<div class="col-md-4">
							<select class="selectpickerdelete" id="selectpickerDeleteName">
								
							</select>
						</div>
						<div class="col-md-6">
							<textarea style="resize: none;" rows="4" cols="50" id="textDeleteValue" name="textDeleteValue" class="form-control" disabled></textarea>
						</div>
						<br><br><br>
						<div class="col-md-2">
							<button class="btn btn-danger disabled" id="btnDeleteName" type="button"><?php echo lang('DELETE_NAME'); ?></button> 
						</div>
					</div>
				</div>
				<div class="panel-footer"><footer class="text-muted"><sub><span id="spantxdeleteinfo"></span></sub></footer></div>
			</div>
		</div>		
	</div>	
	
</div>	

<script>
$('.selectpicker').selectpicker({
	style: 'btn-default',
	size: 5
});
$('.selectpickerdelete').selectpicker({
	style: 'btn-danger',
	size: 5
});
checkValidNames();

$('#ownership_selector button').click(function() {
    $(this).addClass('active').siblings().removeClass('active');
    take_ownership=$(this).val();
	checkName();
});

address_count=<?php echo $address_count; ?>;
max_free_address=<?php echo $max_addresses; ?>;
account_balance=<?php echo $account_balance; ?>;
price_address=<?php echo $price_extra_address; ?>;
newNameFee=0;
newNameBasisFee=<?php echo $new_name_basis_fee; ?>;
$('#textName').on('change keyup paste', function(e) {
	checkName();
});
$('#textValue').on('change keyup paste', function(e) {
	checkName();
});
$('#textDays').on('change keyup paste', function(e) {
	checkName();
});

$("#btnRegisterName").click(function() {
	registerName();
});

$("#btnClaimName").click(function() {
	claimName();
});

$("#btnUpdateName").click(function() {
	updateName();
});

$("#btnDeleteName").click(function() {
	deleteName();
});

function checkName() {
	textName=$('#textName').val();
	textValue=$('#textValue').val();
	textDays=$('#textDays').val();
	validName=0;
	validValue=0;
	validDays=0;
	if ($.isNumeric(textDays)) {
		validDays=1;
		if (textDays><?php echo $nvs_max_days; ?>) {
			textDays=<?php echo $nvs_max_days; ?>;
			$('#textDays').val('<?php echo $nvs_max_days; ?>');
		}
		if (textDays==0) {
			textDays=1;
			$('#textDays').val('1');
		}
	} else {
		textDays=0;
	}
	nameLength = textName.length;
	if (nameLength>0) {
		validName=1; 
		$("#btnClaimName").removeClass("disabled");
	} else {
		$("#btnClaimName").addClass("disabled");
	}
	valueLength = textValue.length;
	if (valueLength>0) { validValue=1; }
	
	powreward=<?php echo $pow_reward; ?>;
	servicemultiplier=<?php echo $nvs_multiplier; ?>;
	newNameFee=servicemultiplier*(Math.sqrt(powreward*(newNameBasisFee+(textDays/365))+Math.floor(((nameLength+valueLength)/128)))/100);
	newNameFee=Math.round(newNameFee * 10000) / 10000;
	$("#spantxinfo").html("<?php echo lang('NAME_REGFEES'); ?>: service_multiplier * (sqrt(PoW_Reward * (registration_basis_fee+(Days/365)) + floor(((Name_Chars+Value_Chars)/128)))/100) <i>(max. 4 decimals)</i><br>");
	$("#spantxinfo").append("<?php echo lang('NAME_REGFEES'); ?>: "+servicemultiplier+" * (sqrt("+powreward+" * ("+newNameBasisFee+"+("+textDays+"/365)) + floor((("+nameLength+"+"+valueLength+")/128)))/100) = <b>"+newNameFee+" EMC</b> ");
	if (validName==1 && validValue==1 && validDays==1 && account_balance>=newNameFee) {
	   $("#btnRegisterName").removeClass("disabled");
    } else {
        $("#btnRegisterName").addClass("disabled");
    } 
};

$('#textUpdateValue').on('keyup paste', function(e) {
	checkNameUpdate('0');
});
$('#textUpdateDays').on('keyup paste', function(e) {
	checkNameUpdate('0');
});
$('#textUpdateAddress').on('keyup paste', function(e) {
	checkNameUpdate('1');
});

function checkNameUpdate(addressupdated) {
	textUpdateName=$('#selectpickerName').val();
	if (textName=="-") {
		$("#spanaddressownerinfo").text('');
		$("#spantxupdateinfo").text('');
		$('#textUpdateAddress').val('');
	}
	textUpdateValue=$('#textUpdateValue').val();
	textUpdateDays=$('#textUpdateDays').val();
	textUpdateAddress=$('#textUpdateAddress').val();
	moveAddress=<?php echo $nvs_move_address; ?>;
	validName=0;
	validValue=0;
	validDays=0;
	if (addressupdated==1) { 
		validAddress=0;
	}
	if ($.isNumeric(textUpdateDays)) {
		validDays=1;
		if (textUpdateDays><?php echo $nvs_max_days; ?>) {
			textUpdateDays=<?php echo $nvs_max_days; ?>;
			$('#textUpdateDays').val('<?php echo $nvs_max_days; ?>');
		}
	} else {
		textUpdateDays=0;
	}
	nameLength = textUpdateName.length;
	if (nameLength>0) { validName=1; }
	valueLength = textUpdateValue.length;
	if (valueLength>0) { validValue=1; }
	addressLength = textUpdateAddress.length;
	if (addressLength==0) { 
		$("#spanaddressownerinfo").text('');
	}
	if (addressLength>0 && addressupdated==1) { 
		validAddress=0;
		mineAddress=0;
		if (addressLength==34) { 
			jQuery.ajaxSetup({async:false});
			$("#spanaddressownerinfo").html('<img src="/img/horizontal-loader.gif"></img>');
			$.get('https://emercoin.mintr.org/api/address/isvalid/'+textUpdateAddress,function(data){
				if(data==1) {
					validAddress=1;
					$.get('https://emercoin.mintr.org/api/address/ismine/'+textUpdateAddress,function(data2){
						if(data2==1) {
							mineAddress=1;
							getaddressowner(textUpdateAddress);
						} else {
							mineAddress=0;
							$("#spanaddressownerinfo").text('<?php echo lang('THIS_EXTERNALADDRESS'); ?>');
						}
					});
				} else {
					validAddress=0;
					$("#spanaddressownerinfo").text('<?php echo lang('THIS_INVALIDADDRESS'); ?>');
				}
			});
			jQuery.ajaxSetup({async:true});
		} else {
			$("#spanaddressownerinfo").text('<?php echo lang('THIS_INVALIDADDRESS'); ?>');
		}		
	}
	if (addressLength==0) {
		powreward=<?php echo $pow_reward; ?>;
		servicemultiplier=<?php echo $nvs_multiplier; ?>;
		updatenamebasisfee=<?php echo $update_name_basis_fee; ?>;
		updateNameFee=servicemultiplier*(Math.sqrt(powreward*(updatenamebasisfee+(textUpdateDays/365))+Math.floor(((nameLength+valueLength)/128)))/100);
		updateNameFee=Math.round(updateNameFee * 10000) / 10000;
		$("#spantxupdateinfo").html("<?php echo lang('NAME_UPFEES'); ?>: service_multiplier * (sqrt(PoW_Reward * (update_basis_fee+(Days/365)) + floor(((Name_Chars+Value_Chars)/128)))/100) <i>(max. 4 decimals)</i><br>");
		$("#spantxupdateinfo").append("<?php echo lang('NAME_UPFEES'); ?>:"+servicemultiplier+" * (sqrt("+powreward+" * ("+updatenamebasisfee+"+("+textUpdateDays+"/365)) + floor((("+nameLength+"+"+valueLength+")/128)))/100) = <b>"+updateNameFee+" EMC</b> ");
		if (validName==1 && validValue==1 && validDays==1 && account_balance>=updateNameFee && textName!="-") {
		   $("#btnUpdateName").removeClass("disabled");
		} else {
			$("#btnUpdateName").addClass("disabled");
		} 
	}
	if (addressLength>0) {
		powreward=<?php echo $pow_reward; ?>;
		servicemultiplier=<?php echo $nvs_multiplier; ?>;
		updatenamebasisfee=<?php echo $update_name_basis_fee; ?>;
		updateNameFee=servicemultiplier*(Math.sqrt(powreward*(updatenamebasisfee+(textUpdateDays/365))+Math.floor(((nameLength+valueLength)/128)))/100)+moveAddress;
		updateNameFee=Math.round(updateNameFee * 10000) / 10000;
		$("#spantxupdateinfo").html("<?php echo lang('NAME_UPFEES'); ?>: update_basis_fee + service_multiplier * (sqrt(PoW_Reward * (0+(Days/365)) + floor(((Name_Chars+Value_Chars)/128)))/100) + move_address_fee <i>(max. 4 decimals)</i><br>");
		$("#spantxupdateinfo").append("<?php echo lang('NAME_UPFEES'); ?>: "+updatenamebasisfee+" + "+servicemultiplier+" * (sqrt("+powreward+" * (0+("+textUpdateDays+"/365)) + floor((("+nameLength+"+"+valueLength+")/128)))/100) + "+moveAddress+" = <b>"+updateNameFee+" EMC</b> ");
		if (validAddress==1 && validName==1 && validValue==1 && validDays==1 && account_balance>=updateNameFee && textName!="-") {
		   $("#btnUpdateName").removeClass("disabled");
		} else {
			$("#btnUpdateName").addClass("disabled");
		} 
	}
};

function registerName()
{	
	$("#btnRegisterName").addClass("disabled");
	var request = $.ajax({
		type: "POST",
		url: "/walletfiles/process_register_name.php",
		data: { name: textName, value: textValue, days: textDays, connid: '<?php echo $randomString; ?>' }
	});
	request.done(function( response ) {
		if (response=='0') {
			$.notify("<?php echo lang('NAME_CONFIRMED'); ?>","success");
			setTimeout("location.reload(true);",700);
		} else if (response=='1') {
			$.notify("<?php echo lang('SOMETHING_WRONG'); ?>","error");
			$("#btnRegisterName").removeClass("disabled");
		} else if (response=='2') {
			$.notify("<?php echo lang('THIS_NAMETAKEN'); ?>","error");
			$("#btnRegisterName").removeClass("disabled");
		} else {
			$.notify(response,"error");
		}
	});
}

function claimName()
{	
	$("#btnClaimName").addClass("disabled");
	var request = $.ajax({
		type: "POST",
		url: "/walletfiles/process_claim_name.php",
		data: { name: textName, connid: '<?php echo $randomString; ?>' }
	});
	request.done(function( response ) {
		if (response=='0') {
			$.notify("<?php echo lang('NAME_CLAIMED'); ?>","success");
			setTimeout("location.reload(true);",700);
		} else if (response=='1') {
			$.notify("<?php echo lang('SOMETHING_WRONG'); ?>","error");
			$("#btnClaimName").removeClass("disabled");
		} else if (response=='2') {
			$.notify("<?php echo lang('NAME_BELONG'); ?>","error");
			$("#btnClaimName").removeClass("disabled");
		} else if (response=='3') {
			$.notify("<?php echo lang('NAME_NOTREGISTERED'); ?>","error");
			$("#btnClaimName").removeClass("disabled");
		}else {
			$.notify(response,"error");
		}
	});
}

function updateName()
{	
	$("#btnUpdateName").addClass("disabled");
	var request = $.ajax({
		type: "POST",
		url: "/walletfiles/process_update_name.php",
		data: { name: textUpdateName, value: textUpdateValue, days: textUpdateDays, address: textUpdateAddress, connid: '<?php echo $randomString; ?>' }
	});
	request.done(function( response ) {
		if (response=='0') {
			$.notify("<?php echo lang('NAME_UPSUCCESS'); ?>","success");
			setTimeout("location.reload(true);",700);
		} else if (response=='1') {
			$.notify("<?php echo lang('SOMETHING_WRONG'); ?>","error");
			$("#btnUpdateName").removeClass("disabled");
		} else {
			$.notify(response,"error");
		}
	});
}

function deleteName()
{	
	$("#btnUpdateName").addClass("disabled");
	var request = $.ajax({
		type: "POST",
		url: "/walletfiles/process_delete_name.php",
		data: { name: $('#selectpickerDeleteName').val(), connid: '<?php echo $randomString; ?>' }
	});
	request.done(function( response ) {
		if (response=='0') {
			$.notify("<?php echo lang('NAME_DELETED'); ?>","success");
			setTimeout("location.reload(true);",700);
		} else if (response=='1') {
			$.notify("<?php echo lang('SOMETHING_WRONG'); ?>","error");
			$("#btnDeleteName").removeClass("disabled");
		} else {
			$.notify(response,"error");
		}
	});
}

$('#selectpickerName').on('change', function(e) {
	var request = $.ajax({
		type: "POST",
		url: "/walletfiles/process_get_name_value.php",
		data: { name: $('#selectpickerName').val() }
	});
	request.done(function( json ) {
		response_obj = JSON.parse(json);
		$('#textUpdateValue').val(response_obj['value']);
		document.getElementById('textUpdateAddress').placeholder=response_obj['address'];
		checkNameUpdate('0');
	});
});

$('#selectpickerDeleteName').on('change', function(e) {
	var request = $.ajax({
		type: "POST",
		url: "/walletfiles/process_get_name_value.php",
		data: { name: $('#selectpickerDeleteName').val() }
	});
	request.done(function( json ) {
		response_obj = JSON.parse(json);
		$('#textDeleteValue').val(response_obj['value']);
		if ($('#selectpickerDeleteName').val()!="-") {
		   $("#btnDeleteName").removeClass("disabled");
		   deletenamebasisfee=<?php echo $delete_name_basis_fee; ?>;
		   $("#spantxdeleteinfo").html("<?php echo lang('FEE_FEE'); ?>: <b>"+deletenamebasisfee+" EMC</b>");
		} else {
			$("#btnDeleteName").addClass("disabled");
			$("#spantxdeleteinfo").html("");
		} 
	});
});

function getaddressowner(address)
{	
	var request = $.ajax({
		type: "POST",
		url: "/walletfiles/process_getaddressowner.php",
		data: { address: address }
	});
	request.done(function( response ) {
		$("#spanaddressownerinfo").text('<?php echo lang('THIS_TO'); ?> '+response);
	});
}

function checkValidNames()
{	
	var request = $.ajax({
		type: "POST",
		url: "/walletfiles/process_check_names.php"
	});
	request.done(function( response ) {
		$('#selectpickerName').html(response);
		$('#selectpickerName').selectpicker('refresh');
		$('#selectpickerName').selectpicker('val', '-');
		$('#selectpickerDeleteName').html(response);
		$('#selectpickerDeleteName').selectpicker('refresh');
		$('#selectpickerDeleteName').selectpicker('val', '-');
	});
}
</script>	
