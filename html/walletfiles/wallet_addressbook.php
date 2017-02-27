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
		<li class="active"><?php echo lang('ADDRESS_BOOK'); ?></li>
		<li><a href="/wallet/send"><?php echo lang('SEND_SEND'); ?></a></li>
		<li><a href="/wallet/receive"><?php echo lang('RECEIVE_RECEIVE'); ?></a></li>
		<li><a href="/wallet/nvs"><?php echo lang('NVS_NVS'); ?></a></li>
	</ol>

	<div class="row">
		<div class="col-md-8">
			<div class="panel panel-default">
				<div class="panel-heading"><?php echo lang('ADDRESS_BOOK'); ?></div>
				<div class="panel-body">
					<table class="table">
					<tr><th><?php echo lang('NAME_NAME'); ?></th><th><?php echo lang('ADDRESS_ADDRESS'); ?></th><td><?php echo lang('STATUS_STATUS'); ?></td><td><?php echo lang('SENT_SENT'); ?></td><td></td></tr>
					<?php
					$value=0;
					$query = "SELECT book.id, book.address, book.name, book.valid, SUM(tx.amount) AS value
					FROM wallet_addressbook AS book
					LEFT JOIN wallet_transaction AS tx ON book.userid = tx.userid AND book.address=tx.address AND tx.category IN ('send','int_send')
					WHERE book.userid = '$userid' 
					GROUP BY book.id, book.address, book.name, book.valid
					ORDER BY book.name";
					$result = $dbwalletconn->query($query);
					if ($result !== false) {
						while($row = $result->fetch_assoc()) {
							$address=$row['address'];
							$name=$row['name'];
							$addressid=$row['id'];
							$valid=$row['valid'];
							$value=round($row['value'],8);
							if ($value=="") { $value=0; }
							if ($valid==0) {
								$valid='<span class="text-danger">'.lang("INVALID_INVALID").'</span>';
							}
							if ($valid==1) {
								$valid='<span class="text-success">'.lang("VALID_EXTERNAL").'</span>';
							}
							if ($valid==2) {
								$valid='<span class="text-primary">'.lang("VALID_INTERNAL").'</span>';
							}
							echo '<tr><td><input type="text" id="'.$addressid.'_'.$name.'" onblur="updateName('.$addressid.', \''.$name.'\');"class="form-control" maxlength="50" placeholder="Name" value="'.$name.'"></td><td><input type="text" id="'.$addressid.'_'.$address.'" onblur="updateAddress('.$addressid.', \''.$address.'\');"class="form-control" maxlength="50" placeholder="Address" value="'.$address.'"></td><td id="'.$addressid.'_status">'.$valid.'</td><td><sub>'.$value.' EMC</sub></td><td id="'.$addressid.'_delete"><a href="#" class="text-danger" onclick="removeEntry('.$addressid.')"><i class="fa fa-trash"></i></a></td></tr>';
						}
					}	
					?>
					</table>
				</div>
			</div>
		</div>
		<div class="col-md-4">
			<div class="panel panel-default">
				<div class="panel-heading"><?php echo lang('NEW_ENTRY'); ?></div>
				<div class="panel-body">
					<input type="text" id="textNewName" class="form-control" maxlength="50" placeholder="<?php echo lang('NAME_NAME'); ?>"><br>
					<input type="text" id="textNewAddress" class="form-control" maxlength="50" placeholder="<?php echo lang('ADDRESS_ADDRESS'); ?>"><br>
					<div class="row">
						<div class="col-md-3">
							<button class="btn btn-success disabled" id="btnNewEntry" type="button"><?php echo lang('SAVE_SAVE'); ?></button>
						</div>
						<div class="col-md-8">
							<footer class="text-muted"><sub><span id="spanaddressownerinfo"></span></sub></footer>
						</div>
					</div>
					<span id="currentbalance"></span>
				</div>
			</div>
		</div>			
	</div>	
</div>	


<script>

$("#textNewName").keyup(function() {
	if ($("#textNewName").val()!="" && $("#textNewAddress").val()!="") {
		$("#btnNewEntry").removeClass("disabled");
	} else {
		$("#btnNewEntry").addClass("disabled");
	}
});
$("#textNewAddress").keyup(function() {
	if ($("#textNewName").val()!="" && $("#textNewAddress").val()!="") {
		$("#btnNewEntry").removeClass("disabled");
	} else {
		$("#btnNewEntry").addClass("disabled");
	}
	address=$("#textNewAddress").val();
	addressLength = address.length;
	if (addressLength==0) { 
		$("#spanaddressownerinfo").text('');
	}
	if (addressLength>0) { 
		validAddress=0;
		mineAddress=0;
		if (addressLength==34) { 
			jQuery.ajaxSetup({async:false});
			$("#spanaddressownerinfo").html('<img src="/img/horizontal-loader.gif"></img>');
			$.get('<?php echo $blockchainurl; ?>/api/address/isvalid/'+address,function(data){
				if(data==1) {
					validAddress=1;
					$.get('<?php echo $blockchainurl; ?>/api/address/ismine/'+address,function(data2){
						if(data2==1) {
							mineAddress=1;
							getaddressowner(address);
							$("#btnNewEntry").removeClass("btn-danger");
							$("#btnNewEntry").addClass("btn-success");
						} else {
							mineAddress=0;
							$("#spanaddressownerinfo").text('<?php echo lang('THIS_EXTERNALADDRESS'); ?>');
							$("#btnNewEntry").removeClass("btn-danger");
							$("#btnNewEntry").addClass("btn-success");
						}
					});
				} else {
					validAddress=0;
					$("#spanaddressownerinfo").text('<?php echo lang('THIS_INVALIDADDRESS'); ?>');
					$("#btnNewEntry").removeClass("btn-success");
					$("#btnNewEntry").addClass("btn-danger");
				}
			});
			jQuery.ajaxSetup({async:true});
		} else {
			$("#spanaddressownerinfo").text('<?php echo lang('THIS_INVALIDADDRESS'); ?>');
			$("#btnNewEntry").removeClass("btn-success");
			$("#btnNewEntry").addClass("btn-danger");
		}		
	}
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


$("#btnNewEntry").click(function() {
	newEntry();
});

function newEntry()
{	
	$("#btnNewEntry").addClass("disabled");
	var request = $.ajax({
		type: "POST",
		url: "/walletfiles/process_newaddress_entry.php",
		data: { name:$('#textNewName').val(), address:$('#textNewAddress').val(), connid: '<?php echo $randomString; ?>' }
	});
	request.done(function( response ) {
		if (response=='0') {
			$.notify("<?php echo lang('NEW_CREATED'); ?>","success");
			setTimeout("location.reload(true);",700);
		}
		else if (response=='1') {
			$.notify("<?php echo lang('SOMETHING_WRONG'); ?>","error");
			$("#btnNewEntry").removeClass("disabled");
		} else {
			$.notify(response,"error");
		}
	});
}

function removeEntry(addressid)
{	
	var request = $.ajax({
		type: "POST",
		url: "/walletfiles/process_removeaddress_entry.php",
		data: { addressid:addressid, connid: '<?php echo $randomString; ?>' }
	});
	request.done(function( response ) {
		if (response=='0') {
			$.notify("<?php echo lang('ADDRESS_REMOVED'); ?>","success");
			setTimeout("location.reload(true);",700);
		}
		else if (response=='1') {
			$.notify("<?php echo lang('SOMETHING_WRONG'); ?>","error");
		} else {
			$.notify(response,"error");
		}
	});
}

nameUpdated=0;
addressUpdated=0;

function updateName(id, name)
{	
	currentName=$('#'+id+'_'+name).val();
	if (nameUpdated==1) {
		name=nameNew;
	}
	if (name!=currentName) {
		var request = $.ajax({
			type: "POST",
			url: "/walletfiles/update_address_name.php",
			data: { name:currentName, addressid:id, connid: '<?php echo $randomString; ?>' }
		});
		request.done(function( response ) {
			if (response=='0') {
					$.notify("<?php echo lang('NAME_CHANGED'); ?>","success");
					nameUpdated=1;
					nameNew=currentName;
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

function updateAddress(id, address)
{	
	currentAddress=$('#'+id+'_'+address).val();
	if (addressUpdated==1) {
		address=addressNew;
	}
	if (address!=currentAddress) {
		var request = $.ajax({
			type: "POST",
			url: "/walletfiles/update_address_address.php",
			data: { address:currentAddress, addressid:id, connid: '<?php echo $randomString; ?>' }
		});
		request.done(function( response ) {
			if (response=='0'||response=='1'||response=='2') {
					$.notify("<?php echo lang('ADDRESS_CHANGED'); ?>","success");
					addressUpdated=1;
					addressNew=currentAddress;
					if (response==0) {
						$('#'+id+'_status').html('<span class="text-danger"><?php echo lang('INVALID_INVALID'); ?></span>');
					}
					if (response==1) {
						$('#'+id+'_status').html('<span class="text-success"><?php echo lang('VALID_EXTERNAL'); ?></span>');
					}
					if (response==2) {
						$('#'+id+'_status').html('<span class="text-primary"><?php echo lang('VALID_INTERNAL'); ?></span>');
					}
			}
			else if (response=='10') {
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
</script>	