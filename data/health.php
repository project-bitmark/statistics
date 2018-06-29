#!/usr/bin/php
<?php
/**
 * Simple Block Summary Generator - Quick & Dirty.
 * Execute on the command line to generate and cache network performance summary data.
 * Data produced is stored in a cached JSON file, each execution of this script updates the data from the last entry.
 * First execution will take some time, depending on the number of blocks in the network.
 * 
 * @author Mark Pfennig
 * @license http://unlicense.org/ 
 */
define('DAEMON_PATH', '/opt/bitmarkd');
define('STATS_CACHE',  dirname(__FILE__) . DIRECTORY_SEPARATOR );
define('STATS_CACHE_FILENAME', 'blocksummary.json');
define('STATS_CACHE_FILE', STATS_CACHE . STATS_CACHE_FILENAME);
define('STATS_CONSIDERED_BLOCKS', 60);
define('HASHRATE_CONSIDERED_BLOCKS_SHORT', STATS_CONSIDERED_BLOCKS);
define('HASHRATE_CONSIDERED_BLOCKS_MEDIUM', STATS_CONSIDERED_BLOCKS*2);
define('HASHRATE_CONSIDERED_BLOCKS_LONG', STATS_CONSIDERED_BLOCKS*4);


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
			'considered' => 0,
			'data' => array()
	);
	return json_decode(file_get_contents(STATS_CACHE_FILE));
}

function updateCache() {
	$chaindata = getCache();
	
	$last_considered = $chaindata->considered;
	$chaindata->generated = askDaemon('getblockcount');
	$chaindata->considered = floor($chaindata->generated/STATS_CONSIDERED_BLOCKS);
	if($chaindata->considered == $last_considered) return; // no new entries can be generated yet
	
	$new = min($last_considered + 100, $chaindata->considered);
	
	for($i=$last_considered;$i<$new;$i++) {
		$start = $i*STATS_CONSIDERED_BLOCKS;			// first block height to consider in entry
		$end = $start + (STATS_CONSIDERED_BLOCKS-1); 	// last block height to consider in entry
		$first = getBlockByHeight($start);
		$last = getBlockByHeight($end);
		$entry = (object)array(
			'start' => $start,
			'end' => $end,
			'starttime' => $first->time,
			'endtime' => $last->time,
			'difficulty' => $last->difficulty,
			'hashrate_s' => askDaemon('getnetworkhashps ' . HASHRATE_CONSIDERED_BLOCKS_SHORT . ' ' . $end),
			'hashrate_m' => askDaemon('getnetworkhashps ' . HASHRATE_CONSIDERED_BLOCKS_MEDIUM . ' ' . $end),
			'hashrate_l' => askDaemon('getnetworkhashps ' . HASHRATE_CONSIDERED_BLOCKS_LONG . ' ' . $end)
		);
		$chaindata->data[] = $entry;
	}
	file_put_contents(STATS_CACHE_FILE, json_encode($chaindata));
}

updateCache();
?>
