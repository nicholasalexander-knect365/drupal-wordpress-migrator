<?php 

class Options {

	public $verbose;
	public $quiet;
	public $progress;
	public $s3bucket;
	public $drupalPath;
	public $imageStore;

	public function __construct() {
		$this->verbose = false;
		$this->quiet   = false;
		$this->progress = false;
		$this->s3bucket = 'http://pentontuautodrupalfs.s3.amazonaws.com';
		$this->drupalPath = '../drupal7/tuauto';
		$this->imageStore = 'images/';
	}

	public function setAll() {
		
		global $argv;

		if (count($argv) > 1) {

			$shortOpts = 'vqph';
			$longOpts = ['drupalPath:', 'imageStore:'];
			$options = getopt($shortOpts, $longOpts);

			foreach ($options as $option => $value) {
				switch ($option) {
					case 'p' : 
						$this->progress = true;
						break;
					case 'q' :
						$this->quiet = true;
						break; 
					case 'v' :
						$this->verbose = true;
						break;
					case 'h' :
						$this->help = true;
						break;
					case 'drupalPath':
						$this->drupalPath = $value;
						break;
					case 'imageStore' :
						$this->imageStore = $value;
						break; 
				}
			}

			if (isset($this->help)) {
				die( "\nFormat: php " . $argv[0] . " [-q, -p or -v]\n\n");
			}
			if ($this->progress) {
				$this->verbose = '.';
			}
		}
	}
	public function get($name) {
		return $this->$name;
	}
}
