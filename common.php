<?php

define('DRUPAL_WP', 'DRUPAL_WP');

$maxChunk = 10000;
//$init = true;

$once = 0;
$option = NULL;

/* control options */
try {

	$options = new Options();
	$options->setAll();

	$project 		= $options->get('project');			// valid: tuauto, ioti
	$s3bucket 		= $options->get('s3bucket');
	$drupalPath 	= $options->get('drupalPath');		// where the drupal files are
	$wordpressPath 	= $options->get('wordpressPath');
	$imageStore 	= $options->get('imageStore');		// temporary image store
	$server 		= $options->get('server');			// server = [local. vm, staging]
	$verbose 		= $options->get('verbose');			// tell me more

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
	if ($options->dusers) {
		debug("INFO: Drupal database NOT opened in createWPusers mode");
		$d7 = null;
	} else {
		$d7 = new DB($server, 'd7', $options);
	}
} catch (Exception $e) {
	die( 'DB connection error: ' . $e->getMessage());
}

// configure the wordpress environment
$wp->configure($options);
if ($d7) {
	$d7->configure($options);
}

$users = new User($wp, $d7, $options);

//////////////////////////// END OF COMMON ////////////////////////////

// HELPERS
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
	// if ($verbose) {
	// 	print "\n-----------------------------";
	// }
}