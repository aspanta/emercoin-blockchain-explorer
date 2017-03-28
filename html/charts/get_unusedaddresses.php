<?php
error_reporting(E_ALL);
require_once __DIR__ . '/../../tools/include.php';
$query="SELECT time, total_addresses_unused
FROM blocks
WHERE id > 1
GROUP BY CEIL((time)/86400)
ORDER BY time";
$result = $dbconn->query($query);
echo $_GET["callback"];
echo "(";
$days_array = array();
while($row = $result->fetch_assoc())
{
	$time_epoch =($row['time'] * 1000);
	$day_array = array($time_epoch, round($row['total_addresses_unused'],2));
	array_push($days_array, $day_array);
}
print json_encode($days_array, JSON_NUMERIC_CHECK);
echo ");";
?>
