#!/usr/bin/php<?php
define('HASHCOST_CACHE',  dirname(__FILE__) . DIRECTORY_SEPARATOR );
define('HASHCOST_CACHE_FILENAME', 'miningsamples.json');
define('HASHCOST_CACHE_FILE', HASHCOST_CACHE . HASHCOST_CACHE_FILENAME);
define('STATS_CACHE',  dirname(__FILE__) . DIRECTORY_SEPARATOR );
define('STATS_CACHE_FILENAME', 'blocksummary.json');
define('STATS_CACHE_FILE', STATS_CACHE . STATS_CACHE_FILENAME);

$update = true; $costs = array(); $moving = array();
$sources = array(
		'nicehash' => 'https://www.nicehash.com/api?method=stats.global.24h',
		'mrr' => '0.00064',
		'lr' => '0.00111385',
		'tdok' => '0.0011',
		'br' => '0.000515'
);

$blocksummaries = array_slice(getCache()->data, 0-(12*4));
foreach($blocksummaries as $k => $v) {
	$moving[] = ((($v->endtime - $v->starttime)/60)*(($v->hashrate_s/1000000)/86400))/20;
}
$moving = (array_sum($moving)/count($moving));

foreach($sources as $name => $url) {
	try {
		switch($name) {
			case 'nicehash':
				$data = fetchJSON($url);
				foreach($data['result']['stats'] as $k => $v) if($v['algo'] == '0') $costs[$name] = $v['price']/1000;
				break;
			default:
				$costs[$name] = $url;
				break;
		}
		$costs[$name] = number_format($costs[$name]*$moving, 8);
	} catch(Exception $e) {
		handleSourceError($e);
		$update = false;
	}
}

if($update) file_put_contents(HASHCOST_CACHE_FILE, json_encode(array_values($costs)));

function getCache() {
	if(!file_exists(STATS_CACHE_FILE)) return (object)array(
			'generated' => 0,
			'considered' => 0,
			'data' => array()
	);
	return json_decode(file_get_contents(STATS_CACHE_FILE));
}

function handleSourceError($e) {
	return;
}

function fetchJSON($location) {
	$context = stream_context_create(array('https' => array('method' => 'GET', 'timeout' => 10)));
	return json_decode(file_get_contents($location, false, $context), true);
}
