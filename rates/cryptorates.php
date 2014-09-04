<?php
/*
* Copyright 2014 CheckoutCrypto Apache License 2.0
*/
Class ratesDb {

    function connectDb() {
        include_once('ratesconfig.php');
        $r = new rDbConfig();
        $rDbConfig = $r->config();
        $rDb = new PDO($rDbConfig['driver'].":host=".$rDbConfig['host'].";dbname=".$rDbConfig['database'], $rDbConfig['username'], $rDbConfig['password']);
        return $rDb;
    }
    
    function setRates($coin_code,$coin_rate) {
        try {
            $rDb = $this->connectDb();
            $stmt = $rDb->prepare("UPDATE cc_coins SET coin_rate = :coin_rate  WHERE coin_code = :coin_code LIMIT 1" );

            $stmt->bindValue(':coin_code', $coin_code, PDO::PARAM_STR);
            $stmt->bindValue(':coin_rate', $coin_rate, PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (exception $e) {
            echo $e;
        }
        return false;
    }

}

function apiRequest($url, $json) {
$scheme = parse_url($url);
$scheme = $scheme['scheme'];
$delay = stream_context_create(array($scheme => array('timeout' => 5)));
$response = file_get_contents($url);
$result = $response;
if($json) {
        if (is_array($json)) {
            $tmp = json_decode($response,true);
            $finArr = array();
			if($url == "https://www.bitstamp.net/api/ticker/"){
				$finArr[$json[0]] = $tmp['high'];
				$finArr[$json[1]] = $tmp['low'];
				$finArr[$json[2]] = $tmp['last'];
			}else{
				$finArr[$json[0]] = $tmp[0]['last_price'];
				$finArr[$json[1]] = $tmp[0]['yesterday_price'];
			}            
			$result = $finArr;
			
            if($result) {
                return $result;
            }
            return false;
        }
        return json_decode($response[$json]);
     }
     return $return;
}


function getRate($coin, $btcprice){

	$url = "https://api.mintpal.com/v1/market/stats/".$coin."/BTC";
	 $jsonpath = array('last_price', 'yesterday_price');
	$result = apiRequest($url, $jsonpath);

	if($result) {
		$rate = $result['last_price'] * $btcprice;
		echo $rate;
		$rDb = new ratesDb();
		$rDb->setRates($coin,$rate);
	}
}

function getBtcDollars(){
	
	$url = "https://www.bitstamp.net/api/ticker/";
	$jsonpath = array('high', 'low', 'last');
	$result = apiRequest($url, $jsonpath);

	if($result) {
		$rDb = new ratesDb();
		$rDb->setRates('BTC',$result['last']);
		return $result['last'];
	}
}

?>
