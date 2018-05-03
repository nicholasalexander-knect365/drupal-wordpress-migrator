<?php


define('DRUPAL_WP', 'DRUPAL_WP');

$maxChunk = 1000000;
//$init = true;

$debug = false;
$once = 0;

/* control options */
try {
	$options = new Options();
	$options->setAll();

	// $project 	= $options->get('project');			// valid: tuauto, ioti
	$s3bucket 	= $options->get('s3bucket');
	$drupalPath = $options->get('drupalPath');		// where the drupal files are
	// $wordpressPath = $options->get('wordpressPath');

	$imageStore = $options->get('imageStore');		// temporary image store
	$server 	= $options->get('server');			// server = [local. vm, staging]
	// $verbose    = $options->get('verbose');			// tell me more

	$option = [];

	foreach ($options->all as $opt) {
		$option[$opt] = $options->get($opt);
	}
	$options->showAll();

	if ($options->get('help')) {
		die("\nHELP Mode\n\n");
	}
} catch (Exception $e) {
	debug("Option setting error\n" . $e->getMessage() . "\n\n");
	die;
}
/* connect databases */
try {
	$wp = new DB($server, 'wp', $options);
	$d7 = new DB($server, 'd7', $options);
} catch (Exception $e) {
	die( 'DB connection error: ' . $e->getMessage());
}

// configure the wordpress environment
$wp->configure($options);
$d7->configure($options);

// common
function dd($v) {
	var_dump($v);
	die;
}

function message($v) {
	global $verbose;
	
	if ($verbose) {
		print "\n-----------------------------";
	}
	print "\n" . $v;
	if ($verbose) {
		print "\n-----------------------------";
	}
}

function home() {
	return getenv('HOME');
}

function debug($v, $singleton = 0) {
	global $verbose;
	
	if ($singleton) {
		return;
	}

	if ($verbose) {
		print "\n-----------------------------";
	}

	if (is_object($v)) {
		print "\n" . print_r((array)$v,1);
	} else if (is_array($v)) {
		print "\n" . print_r($v,1);
	} else {
		print "\n" . $v;
	}
	if ($verbose) {
		print "\n-----------------------------";
	}
}