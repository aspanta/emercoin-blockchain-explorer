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
$unconfirmed_balance=0;
$account_balance=0;
try {
	$account_balance=round($emercoin->getbalance($userid),6);

	$query = "SELECT SUM(amount) as amount FROM wallet_transaction WHERE userid = '$userid' AND category = 'receive' AND confirmations != '-1'";
	$result = $dbwalletconn->query($query);
	while($row = $result->fetch_assoc()) {
		$unconfirmed_balance=$row['amount'];
	}
	if ($unconfirmed_balance=="") {$unconfirmed_balance=0;}

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

$query = "SELECT vwap FROM stock_exchange_vwap_history
		WHERE pair = 'USD' ORDER BY time DESC LIMIT 1";
$result = $dbexchangeconn->query($query);
$usdvaule=0;
while($row = $result->fetch_assoc()) {
	$usdvaule=$row['vwap'];
}

$account_balance_usd=round(bcmul($account_balance,$usdvaule,3),2);
$unconfirmed_balance_usd=round(bcmul($unconfirmed_balance,$usdvaule,3),2);
$reserved_balance_usd=round(bcmul($reserved_balance,$usdvaule,3),2);

$query = "SELECT vwap FROM stock_exchange_vwap_history
		WHERE pair = 'BTC' ORDER BY time DESC LIMIT 1";
$result = $dbexchangeconn->query($query);
$btcvaule=0;
while($row = $result->fetch_assoc()) {
	$btcvaule=$row['vwap'];
}
$account_balance_btc=round(bcmul($account_balance,$btcvaule,9),8);
$unconfirmed_balance_btc=round(bcmul($unconfirmed_balance,$btcvaule,9),8);
$reserved_balance_btc=round(bcmul($reserved_balance,$btcvaule,9),8);

$randomString=md5($userid.generateRandomString());
$_SESSION['randomString']=$randomString;
?>
<style>
	#usdchartdiv {
		width		: 100%;
		height		: 300px;
		font-size	: 10px;
	}
	#btcchartdiv {
		width		: 100%;
		height		: 300px;
		font-size	: 10px;
	}
	#balancechartdiv {
		width		: 100%;
		height		: 300px;
		font-size	: 10px;
	}

	#balance_body {
		height		: 100px;
	}
	#balance_info {
		height		: 10px;
		font-size	: 11px;
	}
	#recent_transactions {
		height		: 100px;
	}
	#transaction_info {
		height		: 10px;
		font-size	: 11px;
	}
</style>
<div class="container">

	<ol class="breadcrumb">
		<li class="active"><?php echo lang('OVERVIEW_OVERVIEW'); ?></li>
		<li><a href="/wallet/transactions"><?php echo lang('TRANSACTIONS_TRANSACTIONS'); ?></a></li>
		<li><a href="/wallet/addressbook"><?php echo lang('ADDRESS_BOOK'); ?></a></li>
		<li><a href="/wallet/send"><?php echo lang('SEND_SEND'); ?></a></li>
		<li><a href="/wallet/receive"><?php echo lang('RECEIVE_RECEIVE'); ?></a></li>
		<li><a href="/wallet/nvs"><?php echo lang('NVS_NVS'); ?></a></li>
	</ol>

	<div class="row">
		<div class="col-md-4">
			<div class="panel panel-primary">
				<div class="panel-heading" style="color:white"><?php echo lang('BALANCE_BALANCE'); ?>: <a style="color:white" onclick="setcurrency('EMC');">EMC</a> | <a style="color:white" onclick="setcurrency('BTC');">BTC</a> | <a style="color:white" onclick="setcurrency('USD');">USD</a></div>
				<div class="panel-body">
				<div id="balance_body">
					<div class="row"><div class="col-md-12"><h3><div id="balance"></div></h3></div></div>
					<?php
					if ($unconfirmed_balance!=0) {
						echo '<div class="row"><div class="col-md-4">'.lang('UNCONFIRMED_UNCONFIRMED').'</div><div class="col-md-8"><div id="unconfirmedbalance"></div></div></div>';
					}
					if ($reserved_balance!=0) {
						echo '<div class="row"><div class="col-md-4">'.lang('RESERVED_RESERVED').'</div><div class="col-md-8"><div id="reservedbalance"></div></div></div>';
					}
					?>
				</div>
				</div>
				<div class="panel-footer">
				<div id="balance_info"></div>
				</div>
			</div>
		</div>
		<div class="col-md-8">
			<div class="panel panel-default">
				<div class="panel-heading"><?php echo lang('RECENT_TRANSACTIONS'); ?></div>
				<div class="panel-body">
					<div id="recent_transactions">
					<table class="table" style="font-size:13px">
					<?php
					$query = "SELECT tx.category, tx.amount, tx.fee, tx.service_fee, tx.address, tx.txid, tx.tx_details, tx.addressid, tx.time, tx.confirmations, address.address AS own_address, address.label AS own_label, book.name AS book_name
					FROM wallet_transaction as tx
					LEFT JOIN wallet_address as address ON address.id=tx.addressid
					LEFT JOIN wallet_addressbook as book ON book.address=tx.address AND book.userid = '$userid'
					WHERE tx.userid = '$userid'
					ORDER BY tx.time DESC LIMIT 3";
					$result = $dbwalletconn->query($query);
					if ($result !== false) {
						while (($row = $result->fetch_assoc()) !== null) {
							$category=$row['category'];
							$amount=$row['amount'];
							$fee=$row['fee'];
							$txidArr=explode(':',$row['txid']);
							$txid=$txidArr[0];
							$tx_id_short = substr($txid, 0, 4)."...".substr($txid, -4);
							$service_fee=$row['service_fee'];
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
								echo '<tr><td>'.lang('RECEIVE_RECEIVE').'</td><td><a href="/tx/'.$txid.'" class="btn btn-primary btn-xs" role="button">'.$tx_id_short.'</a></td><th>'.$amount.' EMC</th><td>'.$own_address_label.'</td><td>'.$read_time.'</td><td width="10px">'.$confirmations.'</td></tr>';
							}
							if ($category=="int_rec") {
								echo '<tr><td>'.lang('RECEIVE_RECEIVE').'</td><td></td><th>'.$amount.' EMC</th><td>'.$own_address_label.'</td><td>'.$read_time.'</td><td width="10px"></td></tr>';
							}
							if ($category=="send") {
								echo '<tr><td>'.lang('SEND_SEND').'</td><td><a href="/tx/'.$txid.'" class="btn btn-primary btn-xs" role="button">'.$tx_id_short.'</a></td><th class="text-danger">-'.$amount.' EMC</th><td>'.$own_address_name.'</td><td>'.$read_time.'</td><td width="10px"></td></tr>';
							}
							if ($category=="int_send") {
								echo '<tr><td>'.lang('SEND_SEND').'</td><td></td><th class="text-danger">-'.$amount.' EMC</th><td>'.$own_address_name.'</td><td>'.$read_time.'</td><td width="10px"></td></tr>';
							}
							if ($category=="int_stake") {
								echo '<tr><td>'.lang('STAKE_STAKE').'</td><td></td><th class="text-success">'.$amount.' EMC</th><td></td><td>'.$read_time.'</td><td width="10px"></td></tr>';
							}
							if ($category=="new_addr") {
								echo '<tr><td>'.lang('NEW_ADDRESS').'</td><td><a href="/tx/'.$txid.'" class="btn btn-primary btn-xs" role="button">'.$tx_id_short.'</a></td><th class="text-info">-'.$service_fee.' EMC</th><td>'.$address.'</td><td>'.$read_time.'</td><td width="10px"></td></tr>';
							}
							if ($category=="new_name") {
								echo '<tr><td>'.lang('NEW_NAME').'</td><td><a href="/tx/'.$txid.'" class="btn btn-primary btn-xs" role="button">'.$tx_id_short.'</a></td><th class="text-info">-'.$service_fee.' EMC</th><td>'.$tx_details.'</td><td>'.$read_time.'</td><td width="10px"></td></tr>';
							}
							if ($category=="new_update") {
								echo '<tr><td>'.lang('NAME_UPDATE').'</td><td><a href="/tx/'.$txid.'" class="btn btn-primary btn-xs" role="button">'.$tx_id_short.'</a></td><th class="text-info">-'.$service_fee.' EMC</th><td>'.$tx_details.'</td><td>'.$read_time.'</td><td width="10px"></td></tr>';
							}
							if ($category=="new_delete") {
								echo '<tr><td>'.lang('NAME_DELETE').'</td><td><a href="/tx/'.$txid.'" class="btn btn-primary btn-xs" role="button">'.$tx_id_short.'</a></td><th class="text-info">-'.$service_fee.' EMC</th><td>'.$tx_details.'</td><td>'.$read_time.'</td><td width="10px"></td></tr>';
							}
						}
					}
					$query = "SELECT SUM(amount) AS amount
							FROM wallet_transaction
							WHERE userid = '$userid' AND category = 'int_stake'";
					$result = $dbwalletconn->query($query);
					while($row = $result->fetch_assoc()) {
						$staketotal=round($row['amount'],6);
						if ($staketotal=="") {$staketotal="0";}
					}
					?>
					</table>
					</div>
				</div>
				<div class="panel-footer">
				<div id="transaction_info"></div>
				</div>
			</div>
		</div>

	</div>
	<div class="row">
		<div class="col-md-8" id="stock_exchange_history">
			<div class="panel panel-default">
				<div class="panel-heading"><?php echo lang('CHARTS_CHARTS'); ?>: <a onclick="showchart('balance');"><?php echo lang('BALANCE_BALANCE'); ?></a> | <a onclick="showchart('BTC');"><?php echo lang('STOCK_BTC'); ?></a> | <a onclick="showchart('USD');"><?php echo lang('STOCK_USD'); ?></a></div>
				<div class="panel-body">
					<div id="balancechartdiv"></div>
					<div id="usdchartdiv"></div>
					<div id="btcchartdiv"></div>
				</div>
			</div>
		</div>
		<div class="col-md-4 ">
			<div class="panel panel-default">
				<div class="panel-heading"><?php echo lang('STAKE_STAKE'); ?></div>
				<div class="panel-body">
					<table class="table">
					<?php
						try {
							$stake=$emercoin->getbalance($userid.":stake");
						} catch (Exception $e) {
							$stake="connection error - failed to receive";
						}
					?>
					<tr><td><?php echo lang('STAKE_PERIOD'); ?></td><th><?php echo number_format($stake,6); ?> EMC</th></tr>
					<tr><td><?php echo lang('EARNED_STAKE'); ?></td><td><?php echo number_format($staketotal,6); ?> EMC</td></tr>
					</table>
				</div>
			</div>
			<div class="panel panel-default">
				<div class="panel-heading"><a data-toggle="collapse" href="#ExpectedInterest" aria-expanded="true" aria-controls="ExpectedInterest"><?php echo lang('INTEREST_INTEREST'); ?></a></div>
				<div class="panel-body collapse" id="ExpectedInterest">
				<script>
					$('#BlockChainStatistics').collapse('hide')
				</script>
					<?php
						$dateMonthAgo=date("U", strtotime(' -1 month'));
						$query = "SELECT interest FROM wallet_balance
							WHERE userid='1' AND interest >= 0 AND coinsec = 0 AND time >= $dateMonthAgo";
						$result = $dbwalletconn->query($query);
						$interest=0;
						$count=0;
						$regrassionArray_interest=array();
						$values_interest=array();
						if ($result !== false) {
							while (($row = $result->fetch_assoc()) !== null) {
								$interest+=$row['interest'];
								$count++;
								$regrassionArray_interest[$count]['x']=$count;
								$regrassionArray_interest[$count]['y']=$row['interest'];
							}
							$interestTrend=linearRegression($regrassionArray_interest, null, $count);
							if ($count!=0) {
								$interestAvg=bcdiv($interest,$count,8);
							} else {
								$interestAvg=0;
							}
							$interestAnnualTrend=round(bcmul(bcadd($interestAvg,bcmul($interestTrend,$count,8),8),365,8),2);
							$interest=round(bcmul($interest,12,8),2);
							echo '<table class="table">';
							if ($interest > $interestAnnualTrend) {
								echo "<tr><td>".lang('EXPECTED_INTEREST')."</td><th>".$interestAnnualTrend."% - ".$interest."%</th></tr>";
							} else if ($interest < $interestAnnualTrend) {
								echo "<tr><td>".lang('EXPECTED_INTEREST')."</td><th>".$interest."% - ".$interestAnnualTrend."%</th></tr>";
							} else if ($interest == $interestAnnualTrend) {
								echo "<tr><td>".lang('EXPECTED_INTEREST')."</td><th>".$interest."%</th></tr>";
							}
							echo "<tr><td>".lang('AVERAGE_INTEREST')."</td><td>".$interestAvg."%</td></tr>";
							echo '</table>';
							echo '<footer class="text-muted"><i><sub>'.lang('BASED_M').'</sub></i></footer>';
						} else {
							echo "Connection issue";
						}
					?>
				</div>
			</div>
			<div class="panel panel-default">
				<div class="panel-heading"><a data-toggle="collapse" href="#BlocksFound" aria-expanded="true" aria-controls="BlocksFound"><?php echo lang('POOL_STATISTICS'); ?></a></div>
				<div class="panel-body collapse" id="BlocksFound">
				<script>
					$('#BlocksFound').collapse('hide')
				</script>
					<?php
						$query = "SELECT sum(amount) as total_amount, count(id) as total_ids FROM wallet_stake;";
						$result = $dbwalletconn->query($query);
						$total_amount=0;
						$total_ids=0;
						if ($result !== false) {
							while (($row = $result->fetch_assoc()) !== null) {
								$total_amount=$row['total_amount'];
								$total_ids=$row['total_ids'];
							}
							echo '<table class="table">';
							echo "<tr><td>PoS ".lang('VOLUME_VOLUME')."</td><th>".round($total_amount,6)." EMC</th></tr>";
							echo "<tr><td>".lang('WALLET_BLOCKS')."</td><td>".$total_ids."</td></tr>";
							echo '</table>';
						} else {
							echo "Connection issue";
						}
					?>
				</div>
			</div>
		</div>
	</div>
</div>

<script src="/js/amcharts/amcharts.js"></script>
<script src="/js/amcharts/serial.js"></script>
<script src="/js/amcharts/themes/light.js"></script>

<script>
account_balance=<?php echo $account_balance; ?>;
account_balance_usd=<?php echo $account_balance_usd; ?>;
account_balance_btc=<?php echo $account_balance_btc; ?>;
reserved_balance=<?php echo $reserved_balance; ?>;
reserved_balance_usd=<?php echo $reserved_balance_usd; ?>;
reserved_balance_btc=<?php echo $reserved_balance_btc; ?>;
unconfirmed_balance=<?php echo $unconfirmed_balance; ?>;
unconfirmed_balance_usd=<?php echo $unconfirmed_balance_usd; ?>;
unconfirmed_balance_btc=<?php echo $unconfirmed_balance_btc; ?>;
$('#balance').html(account_balance+' EMC');
$('#reservedbalance').html(reserved_balance+' EMC');
$('#unconfirmedbalance').html(unconfirmed_balance+' EMC');
function setcurrency(currency) {
	if (currency=="EMC") {
		$('#balance').html(account_balance+' EMC');
		$('#reservedbalance').html(reserved_balance+' EMC');
		$('#unconfirmedbalance').html(unconfirmed_balance+' EMC');
		$('#balance_info').html('');
	} else if (currency=="BTC") {
		$('#balance').html(account_balance_btc+' BTC');
		$('#reservedbalance').html(reserved_balance_btc+' BTC');
		$('#unconfirmedbalance').html(unconfirmed_balance_btc+' BTC');
		$('#balance_info').html('<?php echo lang('AVERAGE_AT'); ?> <a href="https://livecoin.net?from=Livecoin-20e00c47">Livecoin.net</a>: <?php echo number_format($btcvaule,8); ?> BTC');
	} else if (currency=="USD") {
		$('#balance').html(account_balance_usd+' USD');
		$('#reservedbalance').html(reserved_balance_usd+' USD');
		$('#unconfirmedbalance').html(unconfirmed_balance_usd+' USD');
		$('#balance_info').html('<?php echo lang('AVERAGE_AT'); ?> <a href="https://livecoin.net?from=Livecoin-20e00c47">Livecoin.net</a>: <?php echo number_format($usdvaule,8); ?> USD');
	}
};

$('#balancechartdiv').show();
$('#btcchartdiv').hide();
$('#usdchartdiv').hide();
function showchart(type) {
	if (type=="balance") {
		$('#balancechartdiv').show();
		$('#btcchartdiv').hide();
		$('#usdchartdiv').hide();
	} else if (type=="BTC") {
	$('#balancechartdiv').hide();
		$('#btcchartdiv').show();
		$('#usdchartdiv').hide();
	} else if (type=="USD") {
		$('#balancechartdiv').hide();
		$('#btcchartdiv').hide();
		$('#usdchartdiv').show();
	}
};


AmCharts.useUTC = true;
var chart = AmCharts.makeChart( "balancechartdiv", {
  "type": "serial",
  "theme": "light",
  "valueAxes": [ {
	"id":"v1",
    "position": "left",
    "title": "<?php echo lang('AMOUNT_EMC'); ?>"
  }],

  "graphs": [ {
    "id": "balance",
	"valueAxis": "v1",
    "lineColor": "#333",
	"lineAlpha": 1,
	"type": "step",
	"lineThickness": "2",
    "title": "balance",
	"showBalloon": true,
    "valueField": "balance",
    "balloonText": "<?php echo lang('BALANCE_BALANCE'); ?>: <b>[[balance]]</b>"
  } ],
  "chartScrollbar": {
    "graph": "balance",
    "graphType": "step",
    "scrollbarHeight": 30
  },
    "chartCursor": {
        "categoryBalloonDateFormat": "JJ:NN, DD MMMM",
        "cursorPosition": "mouse",
        "showNextAvailable": true
    },
	"autoMarginOffset": 5,
    "columnWidth": 1,
	"categoryField": "date",
	"categoryAxis": {
    "parseDates": true,
	"minPeriod": "ss"
  },
  "dataProvider": [
  <?php
  $query="SELECT balance, time
		FROM wallet_balance
        WHERE userid = '$userid' ORDER BY time ASC";
  $result = $dbwalletconn->query($query);
	while($row = $result->fetch_assoc()) {
		$date=$row['time']*1000;
		$balance=$row['balance'];
			echo '{
					"date": '.$date.',
					"balance": "'.$balance.'",
				  },';
	}
  ?>
  ],
  "export": {
    "enabled": true
  }
} );

chart.addListener( "rendered", zoomChart );
zoomChart();

// this method is called when chart is first inited as we listen for "dataUpdated" event
function zoomChart() {
  // different zoom methods can be used - zoomToIndexes, zoomToDates, zoomToCategoryValues
  chart.zoomToIndexes( chart.dataProvider.length - 336, chart.dataProvider.length - 1 );
}



var chart = AmCharts.makeChart( "usdchartdiv", {
  "type": "serial",
  "theme": "light",
  "valueAxes": [ {
	"id":"v1",
    "position": "left",
    "title": "<?php echo lang('VALUE_VALUE'); ?> [USD]"
  }, {
	"id":"v2",
	"axisColor": "#ddd",
	"axisThickness": 2,
	"gridAlpha": 0,
	"axisAlpha": 1,
	"position": "right",
	"title": "<?php echo lang('VOLUME_VOLUME'); ?> [EMC]"
    }],

  "graphs": [ {
    "id": "vwap",
	"valueAxis": "v1",
    "lineColor": "#333",
	"lineAlpha": 1,
	"lineThickness": "2",
    "title": "vwap",
	"type": "line",
	"showBalloon": true,
    "valueField": "vwap",
    "balloonText": "<?php echo lang('VWAP_VWAP'); ?>: <b>[[vwap]]</b>"
  }, {
    "id": "last",
	"valueAxis": "v1",
    "lineColor": "#BE81F7",
	"lineThickness": "1",
	"lineAlpha": 0,
    "title": "Last",
	"showBalloon": false,
	"dashLength": 2,
	"balloonText": "<span style='font-size: 80%'><?php echo lang('LAST_LAST'); ?>: [[last]]</span>",
    "valueField": "last"
  }, {
	"id": "volume",
	"valueAxis": "v2",
    "columnWidth": 20,
    "fillAlphas": 0.8,
    "type": "column",
    "title": "Volume",
	"lineColor": "#ddd",
    "valueField": "volume",
    "balloonText": "<span style='font-size: 80%'><?php echo lang('VOLUME_VOLUME'); ?>: [[volume]]</span>"
  }, {
        "id": "fromGraph",
		"valueAxis": "v1",
        "lineAlpha": 1,
		"lineColor": "#FF5A35",
        "showBalloon": true,
		"type": "step",
        "valueField": "low",
		"title": "Low",
		"balloonText": "<span style='font-size: 80%'><?php echo lang('LOW_LOW'); ?>: [[low]]</span>",
        "fillAlphas": 0
    }, {
		"fillAlphas": 0.2,
        "fillToGraph": "fromGraph",
        "lineAlpha": 1,
		"lineColor": "#C6FF35",
        "showBalloon": true,
		"type": "step",
		"title": "High",
        "valueField": "high",
		"balloonText": "<span style='font-size: 80%'><?php echo lang('HIGH_HIGH'); ?>: [[high]]</span>",
    } ],
  "chartScrollbar": {
    "graph": "vwap",
    "graphType": "line",
    "scrollbarHeight": 30
  },
    "chartCursor": {
        "categoryBalloonDateFormat": "JJ:NN, DD MMMM",
        "cursorPosition": "mouse",
        "showNextAvailable": true
    },
	"autoMarginOffset": 5,
    "columnWidth": 1,
	"categoryField": "date",
	"categoryAxis": {
    "parseDates": true,
	"minPeriod": "hh"
  },
  "dataProvider": [
  <?php
  $time=time();
  $last30daystime=$time-2592000;
  $query='SELECT vwap.time, vwap.vwap, vwap.last, vol.volume, spread.high, spread.low
		FROM stock_exchange_vwap_history AS vwap
		LEFT JOIN stock_exchange_history AS spread ON FROM_UNIXTIME(spread.time,"%Y-%m")=FROM_UNIXTIME(vwap.time,"%Y-%m") AND spread.pair="USD" AND FROM_UNIXTIME(spread.time,"%d")-1=FROM_UNIXTIME(vwap.time,"%d")
		LEFT JOIN stock_exchange_history AS vol ON FROM_UNIXTIME(vol.time,"%Y-%m")=FROM_UNIXTIME(vwap.time,"%Y-%m") AND vol.pair="USD" AND FROM_UNIXTIME(vwap.time,"%H")="12" AND FROM_UNIXTIME(vol.time,"%d")-1=FROM_UNIXTIME(vwap.time,"%d")
        WHERE vwap.pair = "USD" AND vwap.time >= '.$last30daystime.' ORDER BY vwap.time ASC';
  $result = $dbexchangeconn->query($query);
	while($row = $result->fetch_assoc()) {
		$date=$row['time']*1000;
		$vwap=$row['vwap'];
		$last=$row['last'];
		$volume=$row['volume'];
		$high=$row['high'];
		$low=$row['low'];
		if ($volume != null) {
			echo '{
					"date": '.$date.',
					"vwap": "'.$vwap.'",
					"last": "'.$last.'",
					"volume": "'.$volume.'",
					"high": "'.$high.'",
					"low": "'.$low.'",
				  },';
		} else if ($high != null) {
			echo '{
					"date": '.$date.',
					"vwap": "'.$vwap.'",
					"last": "'.$last.'",
					"high": "'.$high.'",
					"low": "'.$low.'",
				  },';
		} else {
			echo '{
					"date": '.$date.',
					"vwap": "'.$vwap.'",
					"last": "'.$last.'",
				  },';
		}


	}
  ?>
  ],
  "export": {
    "enabled": true
  }
} );



var chart = AmCharts.makeChart( "btcchartdiv", {
  "type": "serial",
  "theme": "light",
  "valueAxes": [ {
	"id":"v1",
    "position": "left",
    "title": "<?php echo lang('VALUE_VALUE'); ?> [BTC]"
  }, {
	"id":"v2",
	"axisColor": "#ddd",
	"axisThickness": 2,
	"gridAlpha": 0,
	"axisAlpha": 1,
	"position": "right",
	"title": "<?php echo lang('VOLUME_VOLUME'); ?> [EMC]"
    }],

  "graphs": [ {
    "id": "vwap",
	"valueAxis": "v1",
    "lineColor": "#333",
	"lineAlpha": 1,
	"lineThickness": "2",
    "title": "vwap",
	"type": "line",
	"showBalloon": true,
    "valueField": "vwap",
    "balloonText": "<?php echo lang('VWAP_VWAP'); ?>: <b>[[vwap]]</b>"
  }, {
    "id": "last",
	"valueAxis": "v1",
    "lineColor": "#BE81F7",
	"lineThickness": "1",
	"lineAlpha": 0,
    "title": "Last",
	"showBalloon": false,
	"dashLength": 2,
	"balloonText": "<span style='font-size: 80%'><?php echo lang('LAST_LAST'); ?>: [[last]]</span>",
    "valueField": "last"
  }, {
	"id": "volume",
	"valueAxis": "v2",
    "columnWidth": 20,
    "fillAlphas": 0.8,
    "type": "column",
    "title": "Volume",
	"lineColor": "#ddd",
    "valueField": "volume",
    "balloonText": "<span style='font-size: 80%'><?php echo lang('VOLUME_VOLUME'); ?>: [[volume]]</span>"
  }, {
        "id": "fromGraph",
		"valueAxis": "v1",
        "lineAlpha": 1,
		"lineColor": "#FF5A35",
        "showBalloon": true,
		"type": "step",
        "valueField": "low",
		"title": "Low",
		"balloonText": "<span style='font-size: 80%'><?php echo lang('LOW_LOW'); ?>: [[low]]</span>",
        "fillAlphas": 0
    }, {
		"fillAlphas": 0.2,
        "fillToGraph": "fromGraph",
        "lineAlpha": 1,
		"lineColor": "#C6FF35",
        "showBalloon": true,
		"type": "step",
		"title": "High",
        "valueField": "high",
		"balloonText": "<span style='font-size: 80%'><?php echo lang('HIGH_HIGH'); ?>: [[high]]</span>",
    } ],
  "chartScrollbar": {
    "graph": "vwap",
    "graphType": "line",
    "scrollbarHeight": 30
  },
    "chartCursor": {
        "categoryBalloonDateFormat": "JJ:NN, DD MMMM",
        "cursorPosition": "mouse",
        "showNextAvailable": true
    },
	"autoMarginOffset": 5,
    "columnWidth": 1,
	"categoryField": "date",
	"categoryAxis": {
    "parseDates": true,
	"minPeriod": "hh"
  },
  "dataProvider": [
  <?php
  $query='SELECT vwap.time, vwap.vwap, vwap.last, vol.volume, spread.high, spread.low
		FROM stock_exchange_vwap_history AS vwap
		LEFT JOIN stock_exchange_history AS spread ON FROM_UNIXTIME(spread.time,"%Y-%m")=FROM_UNIXTIME(vwap.time,"%Y-%m") AND spread.pair="BTC" AND FROM_UNIXTIME(spread.time,"%d")-1=FROM_UNIXTIME(vwap.time,"%d")
		LEFT JOIN stock_exchange_history AS vol ON FROM_UNIXTIME(vol.time,"%Y-%m")=FROM_UNIXTIME(vwap.time,"%Y-%m") AND vol.pair="BTC" AND FROM_UNIXTIME(vwap.time,"%H")="12" AND FROM_UNIXTIME(vol.time,"%d")-1=FROM_UNIXTIME(vwap.time,"%d")
        WHERE vwap.pair = "BTC" AND vwap.time >= '.$last30daystime.' ORDER BY vwap.time ASC';
  $result = $dbexchangeconn->query($query);
	while($row = $result->fetch_assoc()) {
		$date=$row['time']*1000;
		$vwap=$row['vwap'];
		$last=$row['last'];
		$volume=$row['volume'];
		$high=$row['high'];
		$low=$row['low'];
		if ($volume != null) {
			echo '{
					"date": '.$date.',
					"vwap": "'.$vwap.'",
					"last": "'.$last.'",
					"volume": "'.$volume.'",
					"high": "'.$high.'",
					"low": "'.$low.'",
				  },';
		} else if ($high != null) {
			echo '{
					"date": '.$date.',
					"vwap": "'.$vwap.'",
					"last": "'.$last.'",
					"high": "'.$high.'",
					"low": "'.$low.'",
				  },';
		} else {
			echo '{
					"date": '.$date.',
					"vwap": "'.$vwap.'",
					"last": "'.$last.'",
				  },';
		}


	}
  ?>
  ],
  "export": {
    "enabled": true
  }
} );



</script>


<?php
function linearRegression ($regrassionArray, $values, $count) {
	$x_avg=0;
	$y_avg=0;
	for ($i=1; $i<=$count; $i++) {
		$x_avg=bcadd($x_avg,$regrassionArray[$i]['x'],8);
		$y_avg=bcadd($y_avg,$regrassionArray[$i]['y'],8);
	}
	if ($count!=0) {
		$x_avg=bcdiv($x_avg,$count,8);
		$y_avg=bcdiv($y_avg,$count,8);
	} else {
		$x_avg=0;
		$y_avg=0;
	}
	$x_avg_diff_sum=0;
	$y_avg_diff_sum=0;
	$x_avg_diff_X_y_avg_diff_sum=0;
	$x_avg_X2_sum=0;
	$y_avg_X2_sum=0;
	for ($i=1; $i<=$count; $i++) {
		$regrassionArray[$i]['x_avg_diff']=bcsub($regrassionArray[$i]['x'],$x_avg,8);
		$x_avg_diff_sum=bcadd($x_avg_diff_sum,$regrassionArray[$i]['x_avg_diff'],8);
		$regrassionArray[$i]['y_avg_diff']=bcsub($regrassionArray[$i]['y'],$y_avg,8);
		$y_avg_diff_sum=bcadd($y_avg_diff_sum,$regrassionArray[$i]['y_avg_diff'],8);
		$regrassionArray[$i]['x_avg_diff_X_y_avg_diff']=bcmul($regrassionArray[$i]['x_avg_diff'],$regrassionArray[$i]['y_avg_diff'],8);
		$x_avg_diff_X_y_avg_diff_sum=bcadd($x_avg_diff_X_y_avg_diff_sum,$regrassionArray[$i]['x_avg_diff_X_y_avg_diff'],8);
		$regrassionArray[$i]['x_avg_X2']=bcmul($regrassionArray[$i]['x_avg_diff'],$regrassionArray[$i]['x_avg_diff'],8);
		$x_avg_X2_sum=bcadd($x_avg_X2_sum,$regrassionArray[$i]['x_avg_X2'],8);
		$regrassionArray[$i]['y_avg_X2']=bcmul($regrassionArray[$i]['y_avg_diff'],$regrassionArray[$i]['y_avg_diff'],8);
		$y_avg_X2_sum=bcadd($y_avg_X2_sum,$regrassionArray[$i]['y_avg_X2'],8);
	}
	if ($count!=0) {
		$x_avg_diff_X_y_avg_diff_sum_avg=bcdiv($x_avg_diff_X_y_avg_diff_sum,$count,8);
		$x_avg_X2_sum_avg=bcdiv($x_avg_X2_sum,$count,8);
		$y_avg_X2_sum_avg=bcdiv($y_avg_X2_sum,$count,8);
	} else {
		$x_avg_diff_X_y_avg_diff_sum_avg=0;
		$x_avg_X2_sum_avg=0;
		$y_avg_X2_sum_avg=0;
	}
	$Sx=sqrt($x_avg_X2_sum_avg);
	$Sy=sqrt($y_avg_X2_sum_avg);
	if (($Sy*$Sx)!=0) {
		$Ryx=bcdiv($x_avg_diff_X_y_avg_diff_sum_avg,bcmul($Sy,$Sx,8),8);
		$Myx=bcmul($Ryx,bcdiv($Sy,$Sx,8),8);
	}
	else {
		$Myx=0;
	}

	return $Myx;
}
?>
