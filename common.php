<?php

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