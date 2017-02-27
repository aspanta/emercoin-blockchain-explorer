<?php
error_reporting(E_ALL); 
ini_set("display_errors", 1); 
require_once __DIR__ . '/../../tools/include.php';
function emcssl_validate($emercoin) {
	try {
		error_reporting(E_ALL);
		if(!array_key_exists('SSL_CLIENT_CERT', $_SERVER))
		  return "No certificate presented, or missing flag +ExportCertData";

		if(!array_key_exists('SSL_CLIENT_I_DN_UID', $_SERVER))
		  return "This certificane is not belong to any cryptocurrency";

		if($_SERVER['SSL_CLIENT_I_DN_UID'] != 'EMC')
		  return "Wrong blockchain currency - this is not EmerCoin blockchain certificate";

		// Generate search key, and retrieve NVS-value 
		$key = str_pad(strtolower($_SERVER['SSL_CLIENT_M_SERIAL']), 16, 0, STR_PAD_LEFT);
		if($key[0] == '0') 
		  return "Wrong serial number - must not start from zero";
		$key = "ssl:" . $key;
		$nvs = $emercoin->name_show($key);
		if($nvs['expires_in'] <= 0)
		  return "NVS record expired, and is not trustable";

		// Compute certificate fingerprint, using algo, defined in the NVS value
		list($algo, $emc_fp) = explode('=', $nvs['value']);
		$crt_fp = hash($algo, 
					   base64_decode(
						 preg_replace('/\-+BEGIN CERTIFICATE\-+|-+END CERTIFICATE\-+|\n|\r/',
						   '', $_SERVER['SSL_CLIENT_CERT'])));

		return ($emc_fp == $crt_fp)? '$' . $nvs['address'] : "False certificate provided";

	  } catch(Exception $e) {
		return "Cannot extract from NVS key=$key"; // Any mmcFE error - validation fails
	}
} // emcssl_validate
$valid_cert=0;
$userid=0;
$emcsslauth=0;
$username="";
if(array_key_exists('SSL_CLIENT_CERT', $_SERVER)) {
	$validate=emcssl_validate($emercoin);
	if ($validate[0]=="$" && $validate[1]=="E") {
		$valid_cert=1;
		$key = str_pad(strtolower($_SERVER['SSL_CLIENT_M_SERIAL']), 16, 0, STR_PAD_LEFT);
		$query = "SELECT id, emcsslauth FROM wallet_user
		WHERE emcssl='$key'";
		$result = $dbwalletconn->query($query);
		while($row = $result->fetch_assoc()) {
			$userid=$row['id'];
			$emcsslauth=$row['emcsslauth'];
		}
	}
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
	<form id='loginForm' action='#' method='post'>
	<div class="row">
		<div class="col-sm-4 col-sm-offset-4">
			<input type="text" id="inputUser" class="form-control" placeholder="<?php echo lang('USERNAME_USERNAME'); ?>" autofocus>
		</div>		
		<div class="col-sm-4"><table height=40px><tr><td><div id="username-check"></div></td></tr>
		<tr><td><div id="username-length"></div></td></tr></table>
		</div>	
	</div>	
	<div class="row">
		<div class="col-sm-4 col-sm-offset-4">
			<input type="password" id="inputPassword" class="form-control" placeholder="<?php echo lang('PASSWORD_PASSWORD'); ?>">
			<div id="forgotpassword">
				
			</div>
		</div>		
		<div class="col-sm-4" id="pw-strength-check"></div>
	</div>	
	<div class="row">
		<div class="col-sm-4 col-sm-offset-4">	
			<input type="password" id="inputPassword2" class="form-control" placeholder="<?php echo lang('REPEAT_PASSWORD'); ?>">
		</div>
		<div class="col-sm-4" id="pw-similar-check"></div>
	</div>
	<div class="row">
		<div class="col-sm-4 col-sm-offset-4">	
			<input type="checkbox" id="checkTos"> <span id="spanTos"> <?php echo lang('I_THE'); ?> <a target="_blank" href="/tos/"><?php echo lang('TERMS_SERVICE'); ?></a>.<br>
			<i><sub><span class="text-danger"><?php echo lang('BETA_RISK'); ?> </span> <?php echo lang('ADDITIONAL_APPLY'); ?></sub></i></span>
		</div>	
	</div>
	<div class="row" id="captcha-row">
		<div class="col-sm-4 col-sm-offset-4">	
			<img src="captcha/captcha.php" id="captcha" />
			<!-- CHANGE TEXT LINK -->
			<a href="#" onclick="
				document.getElementById('captcha').src='captcha/captcha.php?'+Math.random();
				document.getElementById('captcha-text').focus();"
				id="captcha-change"><i class="fa fa-refresh"></i></a><br/>

			<input type="text" name="captcha" id="captcha-text" autocomplete="off" placeholder="captcha"/><br/>
		</div>	
	</div>
	<div class="row">
			<div class="col-sm-4 col-sm-offset-4">
				<br>
				<button class="btn btn-lg btn-primary btn-block" id="btnLogin" type="submit"><?php echo lang('LOGIN_LOGIN'); ?></button>
				<h5 id="labelOR" align="center"><?php echo lang('OR_OR'); ?></h5>
				<button class="btn btn-lg btn-success btn-block" id="btnRegister" type="submit"><?php echo lang('REGISTER_REGISTER'); ?></button>
			</div>
	</div>
	</form>
 </div> <!-- /container -->
 
<script>
$("#inputPassword2").hide();
$("#checkTos").hide();
$("#spanTos").hide();
$("#captcha-row").hide();
$('#loginForm').submit(function(event){event.preventDefault();});	
inputPassword2visible=0;
btnLoginvisible=1;
btnRegisterClicks=0;
userid=<?php echo $userid; ?>;
emcsslauth=<?php echo $emcsslauth; ?>;
validcert=<?php echo $valid_cert; ?>;

$("#btnRegister").click(function() {
	btnRegisterClicks++;
	if (inputPassword2visible==0) {
		inputPassword2visible=1;
		btnLoginvisible=0;
		$("#inputPassword2").show(200);
		$('#checkTos').show(200);
		$('#spanTos').show(200);
		$('#captcha-row').show(200);
		$("#btnLogin").hide(200);
		$("#labelOR").hide(200);
	}
});

$('#inputUser').on('keyup', function(e) {
	if (e.which == 13 && btnLoginvisible==1) {
		login($("#inputUser").val(),$("#inputPassword").val(), emcsslauth);
	}
});
$('#inputPassword').on('keyup', function(e) {
	if (e.which == 13 && btnLoginvisible==1) {
		login($("#inputUser").val(),$("#inputPassword").val(), emcsslauth);
	}
});

$("#btnLogin").click(function() {
login($("#inputUser").val(),$("#inputPassword").val(), emcsslauth);
});

function login(username, password, emcsslauth)
{	
	var request = $.ajax({
		type: "POST",
		url: "/usrmgmt/process_login.php",
		data: { username: username, password: password, emcsslauth: emcsslauth }
	});
	//$.notify("Login request has been sent","info");
	request.done(function( response ) {
		if (response=='0')
		{
			$.notify("<?php echo lang('LOGIN_SUCCESSFULL'); ?>","success");
			setTimeout("location.reload(true);",700);
		}
		else if (response=='1')
		{
			$( "#inputUser" ).animate({
				backgroundColor: "#FF5959",
				color: "#fff",
			}, 300 );
			$( "#inputUser" ).animate({
			  backgroundColor: "#fff",
			  color: "#000",
			}, 300 );
			$( "#inputPassword" ).animate({
				backgroundColor: "#FF5959",
				color: "#fff",
			}, 300 );
			$( "#inputPassword" ).animate({
			  backgroundColor: "#fff",
			  color: "#000",
			}, 300 );
			$.notify("<?php echo lang('LOGIN_NOTSUCCESSFULL'); ?>","error");
			//$('#forgotpassword').html('<a href="/wallet/forgotpassword">Forgot password?</a>');
		}
		else
		{
			$.notify(response,"error");
		}
	});
}
</script>


<script type="text/javascript">

$(function() {
	passwordcheckid=1;
	usercheckid=1;
	toscheckid=1;
});

$( "#inputUser" ).keyup(function() {
	if (inputPassword2visible==1) {
		$.checkuser();
	}	
});
$( "#btnRegister" ).click(function() {
	if (btnRegisterClicks==1) {
		$.checkuser();
	}
});	
$( "#inputPassword" ).keyup(function() {
	if (inputPassword2visible==1) {
		$.checkpassword();
	}	
});
$( "#inputPassword2" ).keyup(function() {
	if (inputPassword2visible==1) {
		$.checkpassword();
	}	
});

$( "#btnRegister" ).click(function() {
	if (btnRegisterClicks>=2) {
		if(usercheckid==1)
		{
			$( "#inputUser" ).animate({
				backgroundColor: "#FF5959",
				color: "#fff",
			}, 500 );
			$( "#inputUser" ).animate({
			  backgroundColor: "#fff",
			  color: "#000",
			}, 500 );
		}
		if(passwordcheckid==1)
		{
			$( "#inputPassword" ).animate({
				backgroundColor: "#FF5959",
				color: "#fff",
			}, 500 );
			$( "#inputPassword" ).animate({
			  backgroundColor: "#fff",
			  color: "#000",
			}, 500 );
			$( "#inputPassword2" ).animate({
				backgroundColor: "#FF5959",
				color: "#fff",
			}, 500 );
			$( "#inputPassword2" ).animate({
			  backgroundColor: "#fff",
			  color: "#000",
			}, 500 );
		}

		if(usercheckid==0 && passwordcheckid==0)
		{
			if ($('#checkTos').prop('checked')) {	
				senduserdata($( "#inputUser" ).val(),$( "#inputPassword" ).val(),$( "#captcha-text" ).val());
			} else {
				$.notify("<?php echo lang('PLEASE_SERVICE'); ?>","error");
			}	
		}
		else
		{
			$.notify("<?php echo lang('A_REQUEST'); ?>","error");
		}
			
	}	
});


$.checkuser = function() {
	var username = $('#inputUser').val();
	username=username.replace(/ /g,'');
	if (username.length>=4 && username.length<=50) {
		$('#username-length').html('');
		setTimeout(checkusername, 250, username);
	}
	if (username.length<4) {
		$('#username-check').html('');
		$('#username-length').html('<span class="label label-warning"><?php echo lang('AT_NECESSARY'); ?></span>');
		usercheckid=1;
	} 
	if (username.length>50) {
		$('#username-check').html('');
		$('#username-length').html('<span class="label label-warning"><?php echo lang('PLEASE_CHARACTERS'); ?></span>');
		usercheckid=1;
	}
	if (username.length==0) {
		$('#username-length').html('');
		usercheckid=1;
	}
};

$.checkpassword = function() {
var pw1 = $('#inputPassword').val();
var pw2 = $('#inputPassword2').val();
if (pw1.length>0 || pw2.length>0)
{
	if (pw1==pw2)
	{
		$('#pw-similar-check').html('<span class="label label-success"><?php echo lang('PASSWORDS_MATCH'); ?></span>');
		passwordcheckid=0;
	}
	else
	{
		$('#pw-similar-check').html('<span class="label label-warning"><?php echo lang('PASSWORDS_NOMATCH'); ?></span>');
		passwordcheckid=1;
	}
}
else
{
	$('#pw-similar-check').html('');
	passwordcheckid=1;
}

var pwstrength=$.pwstrength(pw1);

if (pw1.length>0)
{
	if (pwstrength == 0)
	{
		$('#pw-strength-check').html('<i class="fa red fa-circle fa-fw"></i><i class="fa orange fa-circle-o fa-fw"></i><i class="fa light-orange fa-circle-o fa-fw"></i><i class="fa light-green fa-circle-o fa-fw"></i><i class="fa green fa-circle-o fa-fw"></i>');
	}
	if (pwstrength == 1)
	{
		$('#pw-strength-check').html('<i class="fa red fa-circle fa-fw"></i><i class="fa orange fa-circle fa-fw"></i><i class="fa light-orange  fa-circle-o fa-fw"></i><i class="fa light-green fa-circle-o fa-fw"></i><i class="fa green fa-circle-o fa-fw"></i>');
	}
	if (pwstrength == 2)
	{
		$('#pw-strength-check').html('<i class="fa red fa-circle fa-fw"></i><i class="fa orange fa-circle fa-fw"></i><i class="fa light-orange  fa-circle fa-fw"></i><i class="fa light-green fa-circle-o fa-fw"></i><i class="fa green fa-circle-o fa-fw"></i>');
	}
	if (pwstrength == 3)
	{
		$('#pw-strength-check').html('<i class="fa red fa-circle fa-fw"></i><i class="fa orange fa-circle fa-fw"></i><i class="fa light-orange  fa-circle fa-fw"></i><i class="fa light-green fa-circle fa-fw"></i><i class="fa green fa-circle-o fa-fw"></i>');
	}
	if (pwstrength == 4)
	{
		$('#pw-strength-check').html('<i class="fa red fa-circle fa-fw"></i><i class="fa orange fa-circle fa-fw"></i><i class="fa light-orange  fa-circle fa-fw"></i><i class="fa light-green fa-circle fa-fw"></i><i class="fa green fa-circle fa-fw"></i>');
	}
}
else
{
	$('#pw-strength-check').html('');
}
};



$.pwstrength = function(password) {
	var score = 0, length = password.length, upperCase, lowerCase, digits, nonAlpha;
        
	if(length < 5) score += 0;
	else if(length < 8) score += 5;
	else if(length < 15) score += 10;
	else if(length < 20) score += 20;
	else score += 50;
        
	lowerCase = password.match(/[a-z]/g);
	if(lowerCase) score += 1;
        
	upperCase = password.match(/[A-Z]/g);
	if(upperCase) score += 2;
        
	if(upperCase && lowerCase) score += 5;
        
	digits = password.match(/\d/g);
	if(digits && digits.length > 1) score += 5;
        
	nonAlpha = password.match(/\W/g)
	if(nonAlpha) score += (nonAlpha.length > 1) ? 15 : 10;
        
	if(upperCase && lowerCase && digits && nonAlpha) score += 15;

	if(password.match(/\s/)) score += 10;

	if(score < 10) return 0;
	if(score < 15) return 1;
	if(score < 30) return 2;
	if(score < 50) return 3;
	return 4;
};

function checkusername(username)
{	
	var request_loaduser = $.ajax({
		type: "POST",
		url: "/usrmgmt/check_username.php",
		data: { username: username }
	});
	request_loaduser.done(function( response ) {
		if ($('#inputUser').val().length>=4)
		{
			if (response==1)
			{
				$('#username-check').html('<span class="label label-warning"><?php echo lang('THIS_TAKEN'); ?></span>');
				usercheckid=1;
			}
			if (response==0)
			{
				$('#username-check').html('<span class="label label-success"><?php echo lang('THIS_FREE'); ?></span>');
				usercheckid=0;
			}
		}
	});
}

function senduserdata(username,password,captcha)
{	
	var request_sendcredentials = $.ajax({
		type: "POST",
		url: "/usrmgmt/process_registration.php",
		data: { username: username, password: password, captcha: captcha}
	});
	request_sendcredentials.done(function( response ) {
		if ($('#inputUser').val().length>=4 && $('#inputUser').val().length<=50)
		{
			if (response==0)
			{
				$.notify("<?php echo lang('USER_CREATED'); ?>","success");
				//$('#username').val('');
				//$('#username-check').html('');
				setTimeout("location.reload(true);",700);
			}
			else if (response==1)
			{
				$.notify("<?php echo lang('THIS_TAKEN'); ?>","error");
			}
			else if (response==2)
			{
				$.notify("<?php echo lang('REQUEST_INCOMPLETE'); ?>","error");
			}
			else if (response==3)
			{
				document.getElementById('captcha').src='captcha/captcha.php?'+Math.random();
				document.getElementById('captcha-text').focus();
				$.notify("<?php echo lang('INVALID_CAPTCHA'); ?>","error");
			}
			else
			{
				$.notify(response,"error");
			}
			
			
		}	
	});
}

</script>