<?php

use PHPUnit\Framework\TestCase;
//use PHPUnit\DbUnit\TestCaseTrait;

require "DB.class.php";
require "WPTermMeta.class.php";
require "Taxonomy.class.php";
require "WPTerms.class.php";
require "Options.class.php";
require "User.class.php";
require "common.php";

//define('DRUPAL_WP', 'DRUPAL_WP');

class MigratorTest extends TestCase {
	
	//use TestCaseTrait;

	private $db;
	private $wp;
	private $d7;
	private $options;

	// public function __construct() {
	// 	$this->connectDB();
	// }

	public function testDirectories() 
	{
		$this->assertDirectoryExists('images');
		$this->assertDirectoryIsWritable('images');
	}
	
	private function connectDB() 
	{
		$this->options = new Options();
		$this->options->setAll();

		$this->wp = new DB('local', 'wp', $this->options);
		$this->d7 = new DB('local', 'd7', $this->options);
		
		// test with mandatory config settings
		$this->options->project = 'tuauto';

		// if (getenv('server') === 'local') {
		// 	$this->options->wordpressPath = '../wordpress/' . $this->options->project;
		// } else {
		// 	$this->options->wordpressPath = '/var/www/public';
		// }


		$this->wp->configure($this->options);
		$this->d7->configure($this->options);
		$this->assertObjectHasAttribute('connection', $this->d7);
		$this->assertObjectHasAttribute('connection', $this->wp);

	}
	public function testDB()
	{
		$this->connectDB();
		$this->wp->query('SHOW tables');
		$records = $this->wp->getRecords();

		$this->assertGreaterThan(0, count($records));
	}

	public function testWPContentExists() 
	{
		$this->connectDB();
		$record = $this->wp->record("SELECT COUNT(*) as c FROM wp_posts");
		if (empty($record) || (integer) $record->c === 0) {
			print "\nNo WP Posts exist yet.\n";
			$this->assertEquals(0, (integer) $record->c);
		} else {
			$this->assertGreaterThan(0, (integer) $record->c);
		}
	}

	public function testTermId() {
		$this->connectDB();
		//$wp_termmeta = new WPTermMeta($this->wp);
		$taxonomy = new Taxonomy($this->wp, $this->options);
		$termMetaId = $taxonomy->getSetTerm(DRUPAL_WP, DRUPAL_WP);
		$this->assertGreaterThan(0, $termMetaId);
	}

	// public function testTagsHaveSlugs() {
	// 	$this->connectDB();
	// 	$taxonomy = new Taxonomy($this->wp, $this->options);
	// 	print "\n\n* * * test Tags have slugs with content";

	// 	$tags = $taxonomy->getTags();
	// 	foreach($tags as $tag) {
	// 		$this->assertGreaterThan(0, strlen($tag->slug));
	// 	}
	// }

	public function testTagsValid() {
		$this->connectDB();
		
		print "\n\n* * * test Tags appear valid";

		$terms = new WPTerms($this->wp, $this->options);
		
		$blankSlugsNotUsed = $terms->testBlankSlugs();

		$this->assertEquals(true, $blankSlugsNotUsed);

		// if ($blankSlugsNotUsed) {
		// 	$terms->removeBlankSlugs();
		// }
	}

	public function testTagsExist() {
		$this->connectDB();
		$taxonomy = new Taxonomy($this->wp, $this->options);
		print "\n\n* * * test Tags";

		$tags = $taxonomy->getTags();
		foreach($tags as $tag) {
			$items = $taxonomy->tagExists($tag->slug);
			if (empty($items)) {
				print "\nPost TAG $taxonomy has NOT yet been used.";
				$this->assertEquals(0, $items);
			} else {
				//print "\nTerm ".$tag->slug." used $items times.";
				$this->assertGreaterThan(0, $items);
			}
		}
	}

	private function checkTaxonomyUse($type) {

		print "\n\n* * * $type Taxonomy Uses:";
		$this->connectDB();
		$taxonomy = new Taxonomy($this->wp, $this->options);
		$taxonomyList = $taxonomy->getTaxonomyList($type);

		foreach ($taxonomyList as $tx) {

			$usage = $taxonomy->countTaxonomyUse($tx);
			if (is_integer($usage)) {
				if ($usage === 0) {
					print "\n* Taxonomy $tx has NOT yet been used.";
					$this->assertEquals(0, $usage);
				} else {
					print "\n* Taxonomy $tx used $usage times";
					$this->assertGreaterThan(0, $usage);
				}
			} else {
				die("\ntestTaxonomyUse returned a NON INTEGER??");
				
			}
		}
	}

	private function checkTaxonomyType($type) {

		print "\n\n* * * test Taxonomy $type exist";
		$taxonomy = new Taxonomy($this->wp, $this->options);
		$taxonomyList = $taxonomy->getTaxonomyList($type);

		foreach($taxonomyList as $tx) {
			$items = $taxonomy->termExists($type, $tx);

			if (empty($items)) {
				print "\n* Taxonomy $type $tx does NOT exist";
				$this->assertEquals(0, $items);
			} else {
				print "\n* Taxonomy $type $tx exists";
				$this->assertGreaterThan(0, $items);
			}
		}
	}

	public function testTaxonomyTypesExist() {
		$this->connectDB();
		$this->checkTaxonomyType('channels');
		$this->checkTaxonomyType('category');
		$this->checkTaxonomyType('subject');
		$this->checkTaxonomyType('type');
		$this->checkTaxonomyType('brief');
	}

	public function testTaxonomyUse() {
		$this->connectDB();
		$this->checkTaxonomyUse('channels');
		$this->checkTaxonomyUse('category');
		$this->checkTaxonomyUse('subject');
		$this->checkTaxonomyUse('type');
		$this->checkTaxonomyUse('brief');
	}
}
