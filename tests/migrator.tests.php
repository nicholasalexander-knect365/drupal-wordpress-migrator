<?php

use PHPUnit\Framework\TestCase;
//use PHPUnit\DbUnit\TestCaseTrait;

require "DB.class.php";
require "WPTermMeta.class.php";
require "Taxonomy.class.php";
require "common.php";

define('DRUPAL_WP', 'DRUPAL_WP');

class MigratorTest extends TestCase {
	
	//use TestCaseTrait;

	private $db;
	private $wp;
	private $d7;

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
		$this->wp = new DB('local', 'wp');
		$this->d7 = new DB('local', 'd7');
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
		$this->assertGreaterThan(0, (integer) $record->c);
	}

	public function testTermId() {
		$this->connectDB();
		$wp_termmeta = new WPTermMeta($this->wp);
		$termMetaId = $wp_termmeta->getSetTerm(DRUPAL_WP, 'Drupal Node ID');
		$this->assertGreaterThan(0, $termMetaId);
	}

	public function testTagsExist() {
		$this->connectDB();
		print "\n\n* * * test Tags";

$tags = [];
		foreach($tags as $tag) {
			$items = $this->termExists('post_tag', $taxonomy);
			if (empty($items)) {
				print "\nPost TAG $taxonomy has NOT yet been used.";
			} else {
				$this->assertEquals(0, $items);
				print "\nTerm $taxonomy used $items times.";
				$this->assertGreaterThan(0, $items);
			}
		}
	}

	private function checkTaxonomyUse($type) {

		print "\n\n* * * $type Taxonomy Uses:";

		$this->connectDB();
		$taxonomy = new Taxonomy($this->wp);
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
		$taxonomy = new Taxonomy($this->wp);
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
		$this->checkTaxonomyType('category');
		$this->checkTaxonomyType('subject');
		$this->checkTaxonomyType('brief');
	}

	public function testTaxonomyUse() {
		$this->connectDB();
		$this->checkTaxonomyUse('category');
		$this->checkTaxonomyUse('subject');
		$this->checkTaxonomyUse('brief');
	}
}
