<?php 

class Options {

	public $verbose;
	public $defaults;
	public $quiet;
	public $progress;
	
	public $initialise;
	public $server;
	public $project;
	public $siteId;

	public $nodes;
	public $taxonomy;
	public $files;
	public $fields;

	public $s3bucket;
	public $wordpressPath;
	public $drupalPath;
	public $imageStore;
	public $clean;
	public $images;

	public $sqlDebug;

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
		'acf' => 'fields',
		'project' => 'project',
		'initialise'=> 'initialise',
		'clean' 	=> 'clean',
		'images' 	=> 'images'
	];

	public function __construct() {
		// these are the defaults, use options to override
		$this->defaults 	= false;
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

		$this->wordpressPath = '/home/nicholas/Dev/wordpress/tuauto';
		$this->drupalPath 	= '../drupal7/tu-auto';
		$this->s3bucket 	= 'http://pentontuautodrupalfs.s3.amazonaws.com';
		$this->imageStore 	= 'images/';
		$this->project 		= 'tu-auto';

		$this->clean  		= false;
		$this->images 		= false;
		$this->sqlDebug		= false;
	}

	public function showAll() {
		foreach ($this->all as $key => $opt) {
			if ($this->verbose) {
				print "\nOption " . $opt . " is ";
				print $this->$opt ? 'set' : 'NOT set';
			} else {
				if (isset($this->$opt)) {
					print " " . $key;
					print $this->$opt ? '+' : '-';
				} else {
					throw new Exception($key . ' has no option??');
				}
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
		$this->sqlDebug = false;
	}

	private function serverOptions() {
		$this->project = isset($options['project']) ? $options['project'] : 'tuauto';
		$this->server = isset($options['server']) ? $options['server'] : 'local';
		if ($this->project === 'tuauto' && $this->server !== 'local') {
			$this->siteId = 39;
		}
		if ($this->projecct === 'ioti' && $this->server !== 'local') {
			$this->siteid = 38;
		}

	}

	public function setAll() {
		
		global $argv;

		if (count($argv) > 1) {

			$shortOpts = 'dvqpfntch';
			$longOpts  = ['server:', 'project:', 'wordpressPath:', 'drupalPath:', 'imageStore:', 'initialise', 'clean', 'images', 'acf', 'sql'];
			$options = getopt($shortOpts, $longOpts);

			if (empty($options)) {
				dd('nothing set');
			}

			if (in_array('h', array_keys($options))) {
			//if ($this->help) {
				print "\nFormat:   php " . $argv[0] . " [-v -d -h -q -p -f -n -t -c]";
				print "\n";

				print "\nServer:";
				print "\n --server=[local,vm,staging,live]";
				print "\n --project=[name of project, e.g. tuauto, ioti]";
				print "\n";

				print "\nSettings:";
				print "\n --wordpressPath=set Wordpress path (must contain wp-config.php)";
				print "\n --drupalPath=set Drupal path";
				print "\n --imageStore=set images directory";
				print "\n";

				print "\nControls:";
				print "\n --initialise   ... clears ALL data";
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
				print "\n-c Field Content (or use --acf)";
				print "\n";
				print "\n";
				die;

			} else {

				$this->wordpressPath = isset($options['wordpressPath']) ? $options['wordpressPath'] : '';
				if (empty($this->wordpressPath)) {
					throw new Exception('Need to know the wordpress path, use --wordpressPath=/path/to/wp-config');
				}

				// default option
				if (in_array('d', array_keys($options))) {
					$this->defaults = true;
					$this->setDefaults();
					return;
				}

				foreach ($options as $option => $value) {
					switch ($option) {
						case 'p':
							$this->progress = true;
							break;

						case 'q':
							$this->quiet = true;
							break;

						case 'v':
							$this->verbose = true;
							break;

						case 'h':
							$this->help = true;
							break;

						case 'f':
							$this->files = true;
							break;

						case 'n':
							$this->nodes = true;
							break;

						case 't':
							$this->taxonomy = true;
							break;

						case 'c':
						case 'acf':
							$this->fields = true;
							break;

						case 'server':
							$this->server = $value;
							break; 

						case 'project':
							$this->project = $value;
							break;

						case 'initialise':
							$this->initialise = true;
							break;

						case 'clean':
							$this->clean = true;
							break;

						case 'wordpressPath':
							$this->wordpressPath = $value;
							break;

						case 'drupalPath':
							$this->drupalPath = $value;
							break;

						case 'imageStore':
							$this->imageStore = $value;
							break; 

						case 'images':
							$this->images = true;
							break;
						case 'sql':
							$this->sqlDebug = true;
							break;

						default: 
							throw new Exception('invaid option? ' . $option);
							break;
					}
				}
			}

			if ($this->progress) {
				$this->verbose = '.';
			}
		}
	}
}
