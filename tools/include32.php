<?php
error_reporting(E_ALL);
$dbconn = new mysqli("localhost", "emcchain","ilikeemcchain", "emcchain32");
// Check connection
if ($dbconn->connect_error) {
    die("Connection failed: " . $dbconn->connect_error);
} 
include ('/var/www/emcchain/tools/wallet_settings.php');
require_once '/var/www/emcchain/tools/include/jsonRPCClient.php';
$emercoin = new jsonRPCClient('http://emercoinrpc:86wGy8zhJwTVPw6Jv6NnGXWGWCHhW4NiF7fFYJzbAkVf@127.0.0.1:6662/');
?>
