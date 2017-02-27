<?php 
error_reporting(E_ALL);
include "../dbconnect.inc.php";
$query="SELECT time, total_coins FROM blocks WHERE id > 1 ORDER BY time";
$result = $dbconn->query($query);
echo $_GET["callback"]; 
echo "(";
$days_array = array();
while($row = $result->fetch_assoc())
{
	$time_epoch =($row['time'] * 1000);
	$day_array = array($time_epoch, round($row['total_coins'],2));
	array_push($days_array, $day_array);
}
print json_encode($days_array, JSON_NUMERIC_CHECK);
echo ");";
?>