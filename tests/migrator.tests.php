<?php

use PHPUnit\Framework\TestCase;
//use PHPUnit\DbUnit\TestCaseTrait;

require "DB.class.php";
require "WPTermMeta.class.php";
require "common.php";

define('DRUPAL_WP', 'DRUPAL_WP');

class MigratorTest extends TestCase {
	
	//use TestCaseTrait;

	private $db;
	private $wp;
	private $d7;

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
}