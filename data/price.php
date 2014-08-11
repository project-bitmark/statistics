<?php

$sources = array(
	'poloniex' => 'https://poloniex.com/public'	
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
	$context = stream_context_create(array('https' => array('method' => 'GET', 'timeout' => 10)));
	return json_decode(file_get_contents($location, false, $context), true);
}