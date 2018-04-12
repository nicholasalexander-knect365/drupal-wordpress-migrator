<?php 

class Options {

	public $verbose;
	public $defaults;
	public $quiet;
	public $progress;
	
	public $initialise;
	public $server;

	public $nodes;
	public $taxonomy;
	public $files;
	public $fields;

	public $s3bucket;
	public $drupalPath;
	public $imageStore;
	public $clean;
	public $images;

	public $all = [
		'd' => 'defaults', 
		'h' => 'help', 
		'q' => 'quiet', 
		'v' => 'verbose', 
		'p' => 'progress', 
		'f' => 'files', 
		'n' => 'nodes', 
		't' => 'taxonomy', 
		'c' => 'fields', 
		'init' => 'initialise', 
		'clean' => 'clean', 
		'images' => 'images'
	];


	public function __construct() {
		// these are the defaults, use options to override
		$this->help  		= false;
		$this->verbose 		= false;
		$this->quiet   		= false;
		$this->progress 	= false;
		
		$this->initialise 	= false;
		
		$this->server 		= 'local';
		$this->nodes 		= false;
		$this->taxonomy 	= false;
		$this->files 		= false;
		$this->fields 		= false;

		$this->s3bucket 	= 'http://pentontuautodrupalfs.s3.amazonaws.com';
		$this->drupalPath 	= '../drupal7/tu-auto';
		$this->imageStore 	= 'images/';
		$this->clean  		= false;
		$this->images 		= false;
	}

	public function show($opt) {
		dd('show is deprecated');
		if ($this->verbose) {

		} else {

		}
	}

	public function showAll() {
		foreach ($this->all as $key => $opt) {
			if ($this->verbose) {
				print "\nOption " . $opt . " is ";
				print $this->$opt ? 'set' : 'NOT set';
			} else {
				print " " . $key;
				print $this->$opt ? '+' : '-';
			}
		}
	}

	public function get($name) {
		return $this->$name;
	}

	public function setDefaults() {
		$this->progress = true;
		$this->quiet    = true;
		$this->verbose  = false;
		$this->help     = false;
		$this->files    = true;
		$this->nodes    = true;
		$this->taxonomy = true;
		$this->fields 	= true;
		$this->initialise = true;
		$this->clean 	= false;
		$this->images 	= true;
	}

	public function setAll() {
		
		global $argv;

		if (count($argv) > 1) {

			$shortOpts = 'dvqpfntch';
			$longOpts  = ['server:', 'drupalPath:', 'imageStore:', 'init', 'clean', 'images'];
			$options = getopt($shortOpts, $longOpts);

			if (empty($options)) {
				dd('nothing set');
			}

			// default option
			if (in_array('d', array_keys($options))) {
				$this->defaults = true;
				$this->server = isset($options['server']) ? $options['server'] : 'local';
				$this->setDefaults();
				return;
			}

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

					case 'c' : 
						$this->fields = true;
						break;

					case 'server' :
						$this->server = $value;
						break; 

					case 'init':
						$this->initialise = true;
						break;

					case 'clean':
						$this->clean = true;
						break;

					case 'drupalPath':
						$this->drupalPath = $value;
						break;

					case 'imageStore' :
						$this->imageStore = $value;
						break; 

					case 'images' : 
						$this->images = true;
						break;

					default: 
						throw new Exception('invaid option? ' . $option);
						break;
				}
			}

			if ($this->help) {
				print "\nFormat:   php " . $argv[0] . " [-v -d -h -q -p -f -n -t -c]\n";
				print "\nServer:";
				print "\n --server=[local,vm,staging,live]";
				print "\nSettings:";
				print "\n --drupalPath=set Drupal path";
				print "\n --imageStore=set images directory";
				print "\nControls:";
				print "\n --init   ... clears ALL data";
				print "\n --clean  ... strips html content";
				print "\n --images ... clears default images directory";
				print "\n --noFiles=[no files]\n";
				print "\n-v Verbose";
				print "\n-d Defaults";
				print "\n-q Quiet";
				print "\n-p Progress indicators";
				print "\n-f Files (Images)";
				print "\n-n Nodes";
				print "\n-t Taxonomy";
				print "\n-c Field Content";
				print "\n";
			}

			if ($this->progress) {
				$this->verbose = '.';
			}

		} else {
			$this->setDefaults();
		}
	}
}
