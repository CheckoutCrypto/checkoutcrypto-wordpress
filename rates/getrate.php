<?php

include 'cryptorates.php';

$btc = getBtcDollars();
getRate("POT", $btc);
getRate("DOGE", $btc);
getRate("LTC", $btc);

?>
