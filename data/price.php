#!/usr/bin/php
<?php
define('SAMPLE_CACHE',  dirname(__FILE__) . DIRECTORY_SEPARATOR );
define('SAMPLE_CACHE_FILENAME', 'sample.json');
define('SAMPLE_CACHE_FILE', SAMPLE_CACHE . SAMPLE_CACHE_FILENAME);

if(file_exists(SAMPLE_CACHE_FILE)) {
	$file = json_decode(file_get_contents(SAMPLE_CACHE_FILE));
	if(time()-$file->generated < 3600) exit;
}

function handleSourceError($e) {
	return;
}

function fetchJSON($location) {
	$context = stream_context_create(array('https' => array('method' => 'GET', 'timeout' => 10)));
	$json = @file_get_contents($location, false, $context);
	if(!$json) throw new Exception();
	return json_decode($json, true);
}

require_once 'hashcosts.php';
require_once 'market.php';

$prices = json_decode(file_get_contents(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'marketsamples.json'));
$costs = json_decode(file_get_contents(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'miningsamples.json'));
$samples = array_merge($prices,$costs);

file_put_contents(SAMPLE_CACHE_FILE, json_encode((object)array(
	'value' => number_format(array_sum($samples)/count($samples), 8),
	'generated' => time()
)));