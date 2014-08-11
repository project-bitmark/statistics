<?php

$sources = array(
	'poloniex' => ' https://poloniex.com/public'	
);

foreach($sources as $name => $url) {
	try {
		switch($name) {
			case 'poloniex':
			$data = fetchJSON( $url . '?' . http_build_query(array(
				'command' => 'returnChartData',
				'currencyPair' => 'BTC_BTM',
				'start' => time()-(86400*5),
				'end' => '9999999999',
				'period' => '86400'
			)));
			break;
		}
	} catch(Exception $e) {
		handleSourceError($e);
	}
	print_r($data);
	
}

function handleSourceError($e) {
	print_r($e);
}

function fetchJSON($location) {
	$ch = curl_init();
	
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
	curl_setopt($ch, CURLOPT_HEADER, FALSE);
	curl_setopt($ch, CURLOPT_URL, $location);
	
	$result = curl_exec($ch);
	if ($result === false) throw new Exception(curl_error($ch));
	
	curl_close($ch);
	
	return json_decode($result);
}