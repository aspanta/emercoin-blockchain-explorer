<?php
while(true) {
// sleep 10 sec and run again
sleep(10);
exec('php /var/www/emcchain/tools/get_blocks32.php');
}
?>
