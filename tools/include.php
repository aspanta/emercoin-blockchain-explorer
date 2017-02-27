<?php
error_reporting(E_ALL);
$dbconn = new mysqli("localhost", "emcchain","ilikeemcchain", "emcchain");
// Check connection
if ($dbconn->connect_error) {
    die("Connection failed: " . $dbconn->connect_error);
}

$dbconn2 = new mysqli("localhost", "emcchain","ilikeemcchain", "emcchain");
// Check connection
if ($dbconn2->connect_error) {
    die("Connection failed: " . $dbconn2->connect_error);
}

$dbwalletconn = new mysqli("localhost", "emcchain","ilikeemcchain", "emcwallet");
// Check connection
if ($dbwalletconn->connect_error) {
    die("Connection failed: " . $dbwalletconn->connect_error);
}

$dbexchangeconn = new mysqli("localhost", "emcchain","ilikeemcchain", "emcexchange");
// Check connection
if ($dbexchangeconn->connect_error) {
    die("Connection failed: " . $dbwalletconn->connect_error);
}

include ('/var/www/emcchain/tools/wallet_settings.php');
require_once '/var/www/emcchain/tools/include/jsonRPCClient.php';
$emercoin = new jsonRPCClient('http://emercoinrpc:86wGy8zhJwTVPw6Jv6NnGXWGWCHhW4NiF7fFYJzbAkVf@127.0.0.1:6662/');
?>
