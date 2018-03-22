<?php 

class Options {
	public $verbose;
	public $quiet;
	public $progress;
	public $help;

	public function __construct() {
		$this->verbose = false;
		$this->quiet   = false;
		$this->progress = false;
	}

	public function setAll() {
		
		global $argv;

		if (count($argv) > 1) {
			$verbose = in_array('-v', $argv);
			$quiet = in_array('-q', $argv);
			$progress = in_array('-p', $argv);
			$help = in_array('-?', $argv);
		} else {
			$verbose = $quiet = $help = $progress = false;
		}

		if ($help) {
			die( "\nFormat: php " . $argv[0] . " [-q, -p or -v]\n\n");
		}
		if ($progress) {
			$verbose = '.';
		}
		$this->verbose = $verbose;
		$this->quiet   = $quiet;
		$this->progress = $progress;
	}
	public function get($name) {
		return $this->$name;
	}
}
