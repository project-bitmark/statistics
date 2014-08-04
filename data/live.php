#!/usr/bin/php
<?php
/**
 * Simple Live Network Summary Generator
 * Execute on the command line to generate and cache live recent network performance summary data.
 * Contains, network hashrate metrics, latest block, 3 block samples further away, block from last difficulty change
 * Data produced is stored in a cached JSON file, each execution of this script updates the data from the last block.
 * 
 * @author Mark Pfennig
 * @license http://unlicense.org/ 
 */
define('DAEMON_PATH', '/opt/bitmarkd');
define('STATS_CACHE',  dirname(__FILE__) . DIRECTORY_SEPARATOR );
define('STATS_CACHE_FILENAME', 'livesummary.json');
define('STATS_CACHE_FILE', STATS_CACHE . STATS_CACHE_FILENAME);
define('STATS_CONSIDERED_BLOCKS', 60);
define('HASHRATE_CONSIDERED_BLOCKS_SHORT', STATS_CONSIDERED_BLOCKS/4);
define('HASHRATE_CONSIDERED_BLOCKS_MEDIUM', STATS_CONSIDERED_BLOCKS/2);
define('HASHRATE_CONSIDERED_BLOCKS_LONG', STATS_CONSIDERED_BLOCKS);


function askDaemon($command) {
	$ask = DAEMON_PATH . ' ' . $command;
	$response = trim(shell_exec($ask), PHP_EOL);
	$data = json_decode($response);
	if(!$data) $data = $response;
	return $data;
}

function getBlockByHeight($height) {
	$hash = askDaemon('getblockhash '. (string)$height);
	return askDaemon('getblock ' . (string)$hash);
}

function getCache() {
	if(!file_exists(STATS_CACHE_FILE)) return (object)array(
			'generated' => 0,
			'data' => array()
	);
	return json_decode(file_get_contents(STATS_CACHE_FILE));
}

function updateCache() {
	$chaindata = getCache();
	
	$last_generated = $chaindata->generated;
	$chaindata->generated = askDaemon('getblockcount');
	if($chaindata->generated == $last_generated) return; // no new stats can be generated yet
	
	$block = getBlockByHeight($chaindata->generated);
	$entry = (object)array(
		'current' => getBlockByHeight($chaindata->generated),
		'block_s' => getBlockByHeight($chaindata->generated-HASHRATE_CONSIDERED_BLOCKS_SHORT),
		'block_m' => getBlockByHeight($chaindata->generated-HASHRATE_CONSIDERED_BLOCKS_MEDIUM),
		'block_l' => getBlockByHeight($chaindata->generated-HASHRATE_CONSIDERED_BLOCKS_LONG),
		'lastchange' => getBlockByHeight(720*floor($chaindata->generated/720)),
		'hashrate_s' => askDaemon('getnetworkhashps ' . ($chaindata->generated-HASHRATE_CONSIDERED_BLOCKS_SHORT) . ' ' . $chaindata->generated),
		'hashrate_m' => askDaemon('getnetworkhashps ' . ($chaindata->generated-HASHRATE_CONSIDERED_BLOCKS_MEDIUM) . ' ' . $chaindata->generated),
		'hashrate_l' => askDaemon('getnetworkhashps ' . ($chaindata->generated-HASHRATE_CONSIDERED_BLOCKS_LONG) . ' ' . $chaindata->generated)
	);
	$chaindata->data = $entry;
	file_put_contents(STATS_CACHE_FILE, json_encode($chaindata));
}

updateCache();
?>