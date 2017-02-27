<?php
if (isset($_SERVER['REQUEST_URI'])) {
	$URI=explode('/',$_SERVER['REQUEST_URI']);
	if ($URI[1]=="block") {
		if (isset($URI[2])) {
			$hash=$URI[2];
		}
	}
}
function TrimTrailingZeroes($nbr) {
    return strpos($nbr,'.')!==false ? rtrim(rtrim($nbr,'0'),'.') : $nbr;
}

echo '<div class="container">';
if (isset($hash) && $hash!="") {
	if (is_numeric($hash)) {
		$query = "SELECT hash FROM blocks WHERE height = '$hash'";
		$result = $dbconn->query($query);
		while($row = $result->fetch_assoc())
		{
			$hash=$row['hash'];
		}
	}
	$query = "SELECT id, height, size, previousblockhash, time, flags, difficulty, total_coins, total_avgcoindays, nonce, merkleroot, numtx, numvin, numvout, valuein, valueout, mint, fee, coindaysdestroyed, avgcoindaysdestroyed 
		FROM blocks 
		WHERE hash = '$hash'";
	$result = $dbconn->query($query);
	while($row = $result->fetch_assoc())
	{
		$block_id=$row['id'];
		$prev_hash=$row['previousblockhash'];
		$height=$row['height'];
		$next_height=($height+1);
		$prev_hash_short = substr($prev_hash, 0, 4)."...".substr($prev_hash, -4);
		$time=date("Y-m-d G:i:s e",$row['time']);
		$flag=$row['flags'];
		$nonce=$row['nonce'];
		$merkleroot=$row['merkleroot'];
		$difficulty=$row['difficulty'];
		$numtx=$row['numtx'];
		$numvin=$row['numvin'];
		$numvout=$row['numvout'];
		$valuein=$row['valuein'];
		$valueout=$row['valueout'];
		$total_coins=$row['total_coins'];
		$mint=$row['mint'];
		$fee=$row['fee'];
		$size=$row['size'];
		$total_avgcoindays=$row['total_avgcoindays'];
		$coindaysdestroyed=$row['coindaysdestroyed'];
		$avgcoindaysdestroyed=$row['avgcoindaysdestroyed'];
		if (strpos($flag,'proof-of-work') !== false) {
			$flag="PoW";
			$flagcolor="danger";
			$feeWOmint=$fee;
		} else {
			$flag="PoS";
			$flagcolor="success";
			$feeWOmint=bcadd($fee,$mint,8);
		}
	}
	if (isset($height)) {
	
		$query = "SELECT hash FROM blocks WHERE height = '$next_height'";
		$result = $dbconn->query($query);
		while($row = $result->fetch_assoc())
		{
			$next_hash=$row['hash'];
			$next_hash_short = substr($next_hash, 0, 4)."...".substr($next_hash, -4);
		}
	
	
		echo '
		<div class="panel panel-default">
			<div class="panel-heading">
				<h3 class="panel-title">'.lang("BLOCK_DETAILS").' - #'.$height.'</h3>
			</div>
			<div class="panel-body">

				<table class="table">';
				if (isset($next_hash)) {
					echo '<tr><td>'.lang("HASH_HASH").'</td><td><a href="/block/'.$prev_hash.'" class="btn btn-primary btn-xs" role="button"><i class="fa fa-arrow-left"></i> '.$prev_hash_short.'</a> '.$hash.' <a href="/block/'.$next_hash.'" class="btn btn-primary btn-xs" role="button">'.$next_hash_short.' <i class="fa fa-arrow-right"></i></a></td>';
				} else {
					echo '<tr><td>'.lang("HASH_HASH").'</td><td><a href="/block/'.$prev_hash.'" class="btn btn-primary btn-xs" role="button"><i class="fa fa-arrow-left"></i> '.$prev_hash_short.'</a> '.$hash.'</a></td>';
				}
				
				echo '
				<tr><td>'.lang("TIME_TIME").'</td><td>'.$time.'</td></tr>
				<tr><td class="text-'.$flagcolor.'">'.$flag.'</span> '.lang("DIFFICULTY_DIFFICULTY").'</td><td>'.TrimTrailingZeroes(number_format($difficulty,8)).'</td></tr>
				<tr><td>'.lang("COINS_AVAILABLE").'</td><td>'.TrimTrailingZeroes(number_format($total_coins,8)).' EMC</td></tr>
				<tr><td>'.lang("AVG_AGE").'</td><td>'.TrimTrailingZeroes(number_format($total_avgcoindays,8)).' <sup>Days</sup>/<sub>Coin</sub></td></tr>
				<tr><td>'.lang("NONCE_NONCE").'</td><td>'.$nonce.'</td></tr>
				<tr><td>'.lang("MERKLE_ROOT").'</td><td>'.$merkleroot.'</td></tr>
				<tr><td>'.lang("TRANSACTIONS_TRANSACTIONS").'</td><td>'.$numtx.'</td></tr>
				<tr><td>'.lang("INPUTS_INPUTS").'</td><td><span class="label label-danger">'.$numvin.' / '.$valuein.' EMC</span></td></tr>
				<tr><td>'.lang("OUTPUTS_OUTPUTS").'</td><td><span class="label label-success">'.$numvout.' / '.$valueout.' EMC</span></td></tr>
				<tr><td>'.lang("MINT_MINT").'</td><td><span class="label label-primary">'.$mint.' EMC</span></td></tr>
				<tr><td>'.lang("SIZE_SIZE").'</td><td>'.TrimTrailingZeroes(number_format($size,2)).' kiB</td></tr>
				<tr><td>'.lang("FEE_FEE").'</td><td>'.TrimTrailingZeroes(number_format($feeWOmint,8)).' EMC</td></tr>
				<tr><td>'.lang("COIN_DESTROYED").'</td><td>'.TrimTrailingZeroes(number_format($coindaysdestroyed,8)).' '.lang("DAYS_DAYS").' => '.TrimTrailingZeroes(number_format($avgcoindaysdestroyed,8)).' <sup>'.lang("DAYS_DAYS").'</sup>/<sub>'.lang("COIN_COIN").'</sub></td></tr>
				</table>
				
				
			</div>
		</div>
		';
		

	 
		echo '
		<div class="panel panel-default">
			<div class="panel-heading">
				<h3 class="panel-title">'.lang("TRANSACTIONS_TRANSACTIONS").'</h3>
			</div>
			<div class="panel-body">
		<table class="table table-striped">
		<thead>
		<tr><th>'.lang("TX_ID").'</th><th>'.lang("FEE_FEE").'</th><th>'.lang("INPUTS_INPUTS").'</th><th>'.lang("OUTPUTS_OUTPUTS").'</th></tr>
		</thead>
		<tbody>';
	} else {
		echo '<h3>'.lang("UNKNOWN_BLOCK").'</h3>';
	}

	if (isset($block_id)) {
		$query="SELECT vin.id+'' AS vid, tx.id, tx.txid, tx.time, tx.fee, vin.coinbase, vin.value AS sent, vin.coindaysdestroyed, vin.avgcoindaysdestroyed, '' AS received, vin.address
		FROM transactions AS tx
		INNER JOIN vin ON vin.parenttxid = tx.id
		WHERE tx.blockid = '$block_id'
		UNION ALL
		SELECT vout.id+'' AS vid, tx.id, tx.txid, tx.time, tx.fee, '' AS coinbase, '' AS sent, '', '', vout.value AS received, vout.address
		FROM transactions AS tx
		INNER JOIN vout ON vout.parenttxid = tx.id
		WHERE tx.blockid = '$block_id'
		ORDER BY id";
		$countvin=0;
		$countvout=0;
		$input="";
		$output="";
		$result = $dbconn->query($query);
		while($row = $result->fetch_assoc())
		{
			$tx_id=$row['txid'];
			if(!isset($oldid)) {
				$oldid=$row['txid'];
				$tx_id=$oldid;
			}
			if ($oldid!=$tx_id) {
				$tx_id_short = substr($oldid, 0, 4)."...".substr($oldid, -4);
				echo '<tr><td><a href="/tx/'.$oldid.'" class="btn btn-primary btn-xs" role="button">'.$tx_id_short.'</a></td><td>'.TrimTrailingZeroes(number_format($fee,8)).'</td><td>'.$input.'</td><td>'.$output.'</td></tr>';
				$input="";
				$output="";
				$countvin=0;
				$countvout=0;
				$oldid=$tx_id;
			}

			if ($row['sent']!="") {
				$vid=$row['vid'];
				if ($countvin>0) {
					$input.='<hr>';
				}
				if (($row['coinbase'])!="") {
					$input.='coinbase<br>0 EMC / 0 <sup>Days</sup>/<sub>Coin</sub></td>';
				} else {
					if ($row['address']=="") { 
						$address="N/A";
					} else {
						$address=$row['address'];
					}
					$input.='<a href="/address/'.$address.'"><button type="button" class="btn btn-link" style="padding:0">'.$address.'</button></a><br>';
					if ($address!="N/A") {
						$input.='<a href="/cointrace/received/vin/'.$vid.'" target="_blank"><button type="button" class="btn btn-link" style="padding:0"><i class="fa fa-code-fork fa-rotate-270"></button></a></i>';
					}
					$input.=' <span class="label label-danger">'.TrimTrailingZeroes(number_format($row['sent'],8)).' EMC</span> / '.TrimTrailingZeroes(number_format($row['coindaysdestroyed'],2)).' Days => '.TrimTrailingZeroes(number_format($row['avgcoindaysdestroyed'],2)).' <sup>Days</sup>/<sub>Coin</sub><br>';
					$countvin++;
				}
			}
			if ($row['received']!="") {
				$vid=$row['vid'];
				if ($countvout>0) {
					$output.='<hr>';
				}
				if ($row['address']=="") { 
					$address="N/A";
				} else {
					$address=$row['address'];
				}
				$output.='<a href="/address/'.$address.'"><button type="button" class="btn btn-link" style="padding:0">'.$address.'</button></a><br>';
				if ($address!="N/A") {
					$output.='<a href="/cointrace/received/vout/'.$vid.'" target="_blank"><button type="button" class="btn btn-link" style="padding:0"><i class="fa fa-code-fork fa-rotate-270"></button></a></i>';
				}
				$output.=' <span class="label label-success">'.TrimTrailingZeroes(number_format($row['received'],8)).' EMC</span>';
				$countvout++;
			}
			$fee=$row['fee'];
		}
		if (isset($tx_id)) {
			$tx_id_short = substr($tx_id, 0, 4)."...".substr($tx_id, -4);
			echo '<tr><td><a href="/tx/'.$tx_id.'" class="btn btn-primary btn-xs" role="button">'.$tx_id_short.'</a></td><td>'.TrimTrailingZeroes(number_format($fee,8)).'</td><td>'.$input.'</td><td>'.$output.'</td></tr>';
		}
		echo '</tbody>';
		echo '</table>  
				</div>
			</div>';
	}	
} else {
	echo '<h3>No Block Provided</h3>';
}
echo '</div>';
?>