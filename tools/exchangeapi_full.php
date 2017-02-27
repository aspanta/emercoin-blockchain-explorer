<?php
include 'include.php';
$url = "https://api.livecoin.net/exchange/ticker";
 
$params = array(
'currencyPair'=> 'EMC/USD'
);
 
$postFields = http_build_query($params, '', '&');
 
$ch = curl_init($url."?".http_build_query($params, '', '&'));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
 
$response = curl_exec($ch);
$statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
 
if ($statusCode!=200) {
throw new Exception('Can not execute the query!');
exit;
}

$response=json_decode($response);
$time=time();
if (isset($response->last)) {
	$query = "INSERT INTO stock_exchange_history
			(time, pair, last, high, low, volume, vwap, max_bid, min_ask)
			VALUES
			('$time', 'USD', '$response->last', '$response->high', '$response->low', '$response->volume', '$response->vwap', '$response->max_bid', '$response->min_ask')";	
	$dbexchangeconn->query($query);
}
?>

<?php
$url = "https://api.livecoin.net/exchange/ticker";
 
$params = array(
'currencyPair'=> 'EMC/BTC'
);
 
$postFields = http_build_query($params, '', '&');
 
$ch = curl_init($url."?".http_build_query($params, '', '&'));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
 
$response = curl_exec($ch);
$statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
 
if ($statusCode!=200) {
throw new Exception('Can not execute the query!');
exit;
}

$response=json_decode($response);
if (isset($response->last)) {
	$query = "INSERT INTO stock_exchange_history
			(time, pair, last, high, low, volume, vwap, max_bid, min_ask)
			VALUES
			('$time', 'BTC', '$response->last', '$response->high', '$response->low', '$response->volume', '$response->vwap', '$response->max_bid', '$response->min_ask')";	
	$dbexchangeconn->query($query);
}
?>
