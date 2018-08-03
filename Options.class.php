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
	public $users;
	public $files;
	public $fields;

	public $dusers;

	public $s3bucket;
	public $wordpressPath;
	public $wordpressURL;
	public $wordpressDomain;
	public $drupalPath;
	public $imageStore;
	public $clean;
	public $clearImages;
	public $resetUserPassword;
	public $resetTerms;

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
		'u' => 'users',
		'acf' => 'fields',
		'project' => 'project',
		'initialise'=> 'initialise',
		'resetTerms' => 'resetTerms',
		'clean' 	=> 'clean'
	];

	public function __construct() {

		$this->defaults 	= false;
		$this->help  		= false;
		$this->verbose 		= false;
		$this->quiet   		= false;
		$this->progress 	= false;

		$this->initialise 	= false;
		$this->server 		= 'local';
		$this->resetUserPassword = false;
		$this->resetTerms = false;

		$this->nodes 		= false;
		$this->taxonomy 	= false;
		$this->files 		= false;
		$this->fields 		= false;
		$this->users 		= false;
		$this->dusers 		= false;

		$this->wordpressPath = '';
		$this->wordpressURL  = '';
		$this->wordpressDomain = 'iotworldtoday.com';

		$this->drupalPath 	= '../drupal7/ioti';
		$this->s3bucket 	= 'http://pentontuautodrupalfs.s3.amazonaws.com';
		$this->imageStore 	= getcwd() . '/images';
		$this->project 		= '';

		$this->clean  		= false;
		$this->clearImages 	= false;
		$this->sqlDebug		= false;
		$this->siteId 		= NULL;
	}

	public function showAll() {

		print "\n: Options: ";
		foreach ($this->all as $key => $opt) {
			if ($this->verbose) {
				print "\n: " . sprintf('%-12s ', $opt);
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

	private function serverOptions() {

		$this->project = isset($options['project']) ? $options['project'] : 'ioti';
		$this->server = isset($options['server']) ? $options['server'] : 'local';
		if ($this->project === 'tu-auto') {
			$this->siteId = 39;
		}
		if ($this->project === 'ioti' || $this->project === 'iotworldtoday') {
			$this->siteId = 38;
		}
	}

	private function setDefaults() {

		$this->progress 	= true;
		$this->quiet 		= true;
		$this->verbose 		= false;

		$this->help 		= false;
		$this->users 		= false;
		$this->files 		= true;
		$this->nodes 		= true;
		$this->taxonomy 	= true;
		$this->fields 		= true;
		$this->initialise 	= true;
		$this->clean 		= true;
		$this->resetTerms 	= false;
		$this->clearImages 	= false;
		$this->sqlDebug 	= false;

		// $this->resetUserPassword 	= false;
	}

	public function setAll() {

		global $argv;

		$firstArg = 1;

		if ($argv[1] === 'tests/migrator.tests.php') {
			$firstArg = 2;
		}

		if (count($argv) > $firstArg) {

			$shortOpts = 'dvqpfntuh';
			$longOpts  = ['server:', 'project:', 'wordpressURL:', 'wordpressPath:', 'drupalPath:',
			 			  'imageStore:', 'initialise', 'clean', 'clearImages', 'acf', 'sql', 'resetTerms', 'resetUserPassword', 'dusers'];
			$options = getopt($shortOpts, $longOpts);

			if (empty($options)) {
				dd('No options were set '.print_r($argv,1));
			}

			if (in_array('h', array_keys($options))) {

				print "\nFormat:   php " . $argv[0] . " [-v -d -h -q -p -f -n -t -c -u]";
				print "\n*  mandatory switches";
				print "\n";

				print "\nServer:";
				print "\n*  --server=[local,vm,staging,beta]";
				print "\n*  --project=[name of project, e.g. tu-auto, ioti]";
				print "\n";

				print "\nSettings:";
				print "\n* --wordpressPath=set Wordpress path (must contain wp-config.php)";
				print "\n* --wordpressURL=the target site base URL (required for Multisite)";
				print "\n* --drupalPath=set Drupal path";
				print "\n  --imageStore=set temp images directory (default ./images)";
				print "\n  --includeS3import gets images from S3 (explicitly gets S3 images)";
				print "\n  --sql show sql statments in verbose mode";
				print "\n  --resetUserPassword is available in replaceContent.php with -u switch";
				print "\n";

				print "\nControls:";
				print "\n  --initialise   ... clears ALL data";
				print "\n  --clean        ... strips html content";
				print "\n  --clearImages  ... clears default images directory";
				print "\n  --resetTerms   ... resets terms taxonomy counts";
				print "\n  --noFiles=[no files]\n";
				print "\n  -q Quiet";
				print "\n  -v Verbose";
				print "\n  -p Progress indicators";
				print "\n";

				print "\n  -u Users (will only import users, then stop)";
				print "\n  -d Defaults (will import nodes, taxonomy, files, acf fields and exclude users)";
				print "\n  -n Nodes";
				print "\n  -t Taxonomy";
				print "\n  -f Files (Images)";
				print "\n  --acf ACF Fields";
				print "\n";
				print "\n  -d --server=local - sets wordpressPath=~/Dev/wordpress/tuauto --project='PROJECT' --drupalPath=~/Dev/drupal7/PROJECT/ +verbose +files +nodes -taxonomy +fields +users +initialise +clean -resetTerms";
				print "\n --d --server=vm sets defaults +progress +quiet -verbose -help +files +nodes -users +taxonomy +fields +initialise -clean +clearImages -sqlDebug --wordpressPath=/var/www/public --wordpressURL=http://tuauto.telecoms.local --drupalPath=/vagrant/drupal7/PROJECT";
				print "\n --d --server=staging - use explicit args";
				print "\n";
				die;

			} else {

				$this->project = $options['project'];

				foreach ($options as $option => $value) {
					switch ($option) {
						case 'd':
							$this->defaults = true;
							break;

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

						case 'acf':
							$this->fields = true;
							break;

						case 'u':
							$this->users = true;
							break;

						case 'dusers':
							$this->dusers = true;
							break;

						case 'resetUserPassword':
							$this->resetUserPassword = true;
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

						case 'wordpressURL':
							$this->wordpressURL = $value;
							break;

						case 'drupalPath':
							$this->drupalPath = $value;
							break;

						case 'imageStore':
							$this->imageStore = $value;
							break; 

						case 'clearImages':
							$this->clearImages = true;
							break;

						case 'resetTerms':
							$this->resetTerms = true;
							break;

						case 'sql':
							$this->sqlDebug = true;
							break;


						default: 
							throw new Exception('invalid option? ' . $option);
							break;
					}
				}

				if ($this->project === 'ioti' || $this->project === 'iotworldtoday') {

					include "ioti-options.partial.php";

				} else if ($this->project === 'tuauto') {

					include "tuauto-options.partial.php";
				}

				if (empty($this->wordpressPath)) {
					throw new Exception('Need to know the wordpress path, use --wordpressPath=/path/to/wp-config');
				}

			}
		}
	}
}
