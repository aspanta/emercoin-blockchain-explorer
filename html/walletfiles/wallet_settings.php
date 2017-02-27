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

$mailcheck="-1";
if ($_SESSION['email']!="") {
	$query = "SELECT mailcheck FROM wallet_user 
			WHERE id = '$userid'";
	$result = $dbwalletconn->query($query);
	while($row = $result->fetch_assoc()) {
		$mailcheck=$row['mailcheck'];
	}
}
?>
<div class="container">

	<ol class="breadcrumb">
		<li><a href="/wallet/overview"><?php echo lang('OVERVIEW_OVERVIEW'); ?></a></li>
		<li><a href="/wallet/transactions"><?php echo lang('TRANSACTIONS_TRANSACTIONS'); ?></a></li>
		<li><a href="/wallet/addressbook"><?php echo lang('ADDRESS_BOOK'); ?></a></li>
		<li><a href="/wallet/send"><?php echo lang('SEND_SEND'); ?></a></li>
		<li><a href="/wallet/receive"><?php echo lang('RECEIVE_RECEIVE'); ?></a></li>
		<li><a href="/wallet/nvs"><?php echo lang('NVS_NVS'); ?></a></li>
	</ol>
	
	<div class="row">
		<div class="col-sm-4 col-sm-offset-4">
			<input type="text" id="inputUser" class="form-control" DISABLED value="<?php echo $_SESSION['username']; ?>">
	</div>	
	<div class="row">
		<div class="col-sm-4 col-sm-offset-4">
			<input type="password" id="password1_mgmt" class="form-control" placeholder="<?php echo lang('NEW_PASSWORD'); ?>">
		</div>		
		<div class="col-sm-4" id="pw-strength-check_mgmt"></div>
	</div>	
	<div class="row">
		<div class="col-sm-4 col-sm-offset-4">	
			<input type="password" id="password2_mgmt" class="form-control" placeholder="<?php echo lang('REPEAT_PASSWORD'); ?>">
		</div>	
		<div class="col-sm-4" id="pw-similar-check_mgmt"></div>
	</div>
	<br>
	<div class="row">
		<div class="col-sm-4 col-sm-offset-4">
			<input type="text" id="email1_mgmt" class="form-control" placeholder="New Email Address" value="<?php echo $_SESSION['email']; ?>">
		</div>		
		<div class="col-sm-4" id="email-check_mgmt"></div>
	</div>	
	<div class="row">
		<div class="col-sm-4 col-sm-offset-4">	
			<input type="text" id="email2_mgmt" class="form-control" placeholder="Repeat New Email Address">
		</div>	
		<div class="col-sm-4" id="email-similar-check_mgmt">
		<?php 
			if ($mailcheck!="-1" && $mailcheck!="1") {
				echo "<span style='color:red'>Verification pending...<span>";
			}
			if ($mailcheck=="1") {
				//echo "<span style='color:green'>Verified<span>";
			}
		?>		
		</div>
	</div>
	<br>
	<div class="row">
		<div class="col-sm-4 col-sm-offset-4">
			<input type="password" id="currentpassword" class="form-control" placeholder="<?php echo lang('CURRENT_PASSWORD'); ?>" required>
		</div>		
		<div class="col-sm-4" id="pw-strength-check_mgmt"></div>
	</div>	
	
	<br>
	<div class="row">
		<div class="col-sm-4 col-sm-offset-4">
			<br>
			<button class="btn btn-lg btn-primary btn-block" id="updatebutton" type="submit"><?php echo lang('UPDATE_SETTINGS'); ?></button>
		</div>
	</div>
	<br>
		<div class="row">
			<div class="col-sm-4 col-sm-offset-4">
				<br>
	<?php 
	$emcsslauth=$_SESSION['emcsslauth'];
	if ($_SESSION['emcsslisvalid']==1) {
			if ($emcsslauth==3) {
				echo '<button class="btn btn-success btn-block active" id="btnEMCSSLwPassword" onclick="setemcssl(3)" type="submit">'.lang("EMCSSL_VERYHIGH").'</button>';
			} else {
				echo '<button class="btn btn-success btn-block" id="btnEMCSSLwPassword" onclick="setemcssl(3)" type="submit">'.lang("EMCSSL_VERYHIGH").'</button>';
			}
			if ($emcsslauth==2) {
				echo '<button class="btn btn-info btn-block active" id="btnEMCSSLwoPassword" onclick="setemcssl(2)" type="submit">'.lang("EMCSSL_HIGH").'</button>';
			} else {
				echo '<button class="btn btn-info btn-block" id="btnEMCSSLwoPassword" onclick="setemcssl(2)" type="submit">'.lang("EMCSSL_HIGH").'</button>';
			}
			if ($emcsslauth==1) {
				echo '<button class="btn btn-danger btn-block active" id="btnEMCSSLorPassword" onclick="setemcssl(1)" type="submit">'.lang("EMCSSL_LOW").'</button>';
			} else {
				echo '<button class="btn btn-danger btn-block" id="btnEMCSSLorPassword" onclick="setemcssl(1)" type="submit">'.lang("EMCSSL_LOW").'</button>';
			}
			if ($emcsslauth>0) {
				echo '<button class="btn btn-default btn-block" id="btndisableEMCSSL" onclick="setemcssl(0)" type="submit">'.lang("EMCSSL_DEACTIVATE").'</button>';
			}
			echo '
				<br>
				'.lang("YOUR_CERTIFICATE").': '.$_SESSION['sslusername'].' - '.$_SESSION['emcssl'].'
				';
	} else {
		if($emcsslauth == 0) {
			echo lang("YOU_LOGIN").'
			<a href="http://emercoin.com/EMCSSL">'.lang("WHAT_EMCSSL").'</a>';
		}	
	}
	?>
		</div>
	</div>
 </div> <!-- /container -->
 
 <script>
 
 emcsslauth=<?php echo $emcsslauth; ?>;
 function setemcssl(emcsslauth) {
	var request = $.ajax({
		type: "POST",
		url: "/walletfiles/process_setemcssl.php",
		data: { emcsslauth: emcsslauth, key:'<?php echo $_SESSION['emcssl']; ?>' }
	});
	request.done(function( response ) {

		if (response=='0')
		{
			$.notify("<?php echo lang('EMCSSL_SET'); ?>","success");
			setTimeout("location.reload(true);",600);
		}
		else if (response=='1')
		{
			$.notify("<?php echo lang('SOMETHING_WRONG'); ?>","error");
		}
		else if (response=='127')
		{
			$.notify("Invalid session","error");
			setTimeout("location.reload(true);",600);
		}
		else
		{
			$.notify(response,"error");
		}
			
	});
 }
 
 </script>
 <script src="../js/user_settings_check.js" type="text/javascript"></script>