<?php
$prices = json_decode(file_get_contents('marketsamples.json'));
$costs = json_decode(file_get_contents('miningsamples.json'));
$samples = array_merge($prices,$costs);
echo json_encode((object)array(
	'value' => number_format(array_sum($samples)/count($samples), 8),
	'generated' => time()
));