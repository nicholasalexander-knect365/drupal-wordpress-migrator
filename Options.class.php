<?php 

class Options {

	public $verbose;
	public $defaults;
	public $quiet;
	public $progress;
	
	public $initialise;

	public $nodes;
	public $taxonomy;
	public $files;
	public $events;

	public $s3bucket;
	public $drupalPath;
	public $imageStore;

	public function __construct() {
		// these are the defaults, use options to override
		$this->help  		= false;
		$this->verbose 		= false;
		$this->quiet   		= false;
		$this->progress 	= false;
		
		$this->initialise 	= false;
		
		$this->nodes 		= false;
		$this->taxonomy 	= false;
		$this->files 		= false;
		$this->events 		= false;

		$this->s3bucket 	= 'http://pentontuautodrupalfs.s3.amazonaws.com';
		$this->drupalPath 	= '../drupal7/tuauto';
		$this->imageStore 	= 'images/';
	}

	public function show($opt) {
		print "\nOption " . $opt . " is ";
		print $this->$opt ? 'set' : 'not set';
	}

	public function get($name) {
		return $this->$name;
	}

	public function setDefaults() {
		$this->progress = true;
		$this->quiet    = true;
		$this->verbose  = true;
		$this->help     = false;
		$this->files    = true;
		$this->nodes    = true;
		$this->taxonomy = true;
		$this->events 	= true;
		$this->initialise = true;
	}

	public function setAll() {
		
		global $argv;

		if (count($argv) > 1) {

			$shortOpts = 'dvqpfnteh';
			$longOpts = ['drupalPath:', 'imageStore:', 'initialise'];
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

					case 'f' :
						$this->files = true;
						break;

					case 'n' :
						$this->nodes = true;
						break;

					case 't' :
						$this->taxonomy = true;
						break;

					case 'e' : 
						$this->events = true;
						break;

					case 'initialise':
						$this->initialise = true;
						break;

					case 'drupalPath':
						$this->drupalPath = $value;
						break;

					case 'imageStore' :
						$this->imageStore = $value;
						break; 

					case 'd' :
						$this->defaults = true;
						$this->setDefaults();
						return;
						break;

				}
			}



			if (isset($this->help)) {
				print "\nFormat:   php " . $argv[0] . " [-v -d -h -q -p -f -n -t -e]\n";
				print "\nSettings:  --drupalPath=setDrupalPath --imageStore=[set images directory]";
				print "\nControls:  --initialise=[clear data]  --noFiles=[no files]\n";
			}
			if ($this->progress) {
				$this->verbose = '.';
			}
		} else {
			$this->setDefaults();
		}
	}

}
