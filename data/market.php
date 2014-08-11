#!/usr/bin/php
<?php
define('PRICE_CACHE',  dirname(__FILE__) . DIRECTORY_SEPARATOR );
define('PRICE_CACHE_FILENAME', 'marketsamples.json');
define('PRICE_CACHE_FILE', PRICE_CACHE . PRICE_CACHE_FILENAME);

$update = true; $prices = array(); $sources = array(
	'poloniex' => 'https://poloniex.com/public'	
);

foreach($sources as $name => $url) {
	try {
		switch($name) {
			case 'poloniex':
			$data = fetchJSON( $url . '?' . http_build_query(array(
				'command' => 'returnChartData',
				'currencyPair' => 'BTC_BTM',
				'start' => time()-(86400*4),
				'end' => '9999999999',
				'period' => '86400'
			)));
			break;
		}
		foreach($data as $index => $entry) $prices[] = number_format($entry['weightedAverage'], 8, '.', '');
	} catch(Exception $e) {
		handleSourceError($e);
		$update = false;
	}
}

if($update) file_put_contents(PRICE_CACHE_FILE, json_encode($prices));

function handleSourceError($e) {
	return;
}

function fetchJSON($location) {
	$context = stream_context_create(array('https' => array('method' => 'GET', 'timeout' => 10)));
	return json_decode(file_get_contents($location, false, $context), true);
}