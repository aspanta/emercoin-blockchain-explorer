	<?php
if (!empty($_COOKIE["lang"])) {
	$lang=$_COOKIE["lang"];
	require("../lang/".$lang.".php");
} else {
	setcookie("lang","en",time()+(3600*24*14), "/");
	require("../lang/en.php");
}

	function timeAgo ($time) {
		$time = time() - $time;

		$tokens = array (
			86400 => 'day',
			3600 => 'hour',
			60 => 'minute',
			1 => 'second'
		);

		foreach ($tokens as $unit => $text) {
			if ($time < $unit) continue;
			$numberOfUnits = floor($time / $unit);
			return $numberOfUnits.' '.$text.(($numberOfUnits>1)?'s':'');
		}
	}

	require_once __DIR__ . '/../../tools/include.php';

	echo '<div class="panel-heading"><b>Emercoin Versions</b> - Get the newest version <a target=_blank href="http://emercoin.com/#download">here</a></div>
	<table class="table">
	<thead>
	<tr><th>Version</th><th>Share</th></tr>
	</thead>
	<tbody>';
	$barcount=0;
	$blockinfo=$emercoin->getinfo();
	$blockheight=bcsub($blockinfo['blocks'],999,0);
	$showBlocksQuery = "SELECT COUNT(*) as count, version FROM blocks
						WHERE height >= '".$blockheight."'
						GROUP BY version
						ORDER BY height DESC";
	$result = $dbconn->query($showBlocksQuery);
	while($row = $result->fetch_assoc())
	{
		$count=bcmul(bcdiv($row['count'],1000,4),100,2);
		$version=$row['version'];
		if ($version == 43646981) {
			$version="5 (since client v.0.6.0)";
			$barcount+=$count;
		}
		if ($version == 43647237) {
			$version="5 (Merged Mining)";
			$barcount+=$count;
		}
		echo '<tr><td>'.$version.'</td><td>'.$count.' %</td></tr>';
	}
		?>
	</tbody></table>
