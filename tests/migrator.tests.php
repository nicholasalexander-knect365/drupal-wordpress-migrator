<?php

use PHPUnit\Framework\TestCase;
//use PHPUnit\DbUnit\TestCaseTrait;

require "DB.class.php";
require "common.php";

class MigratorTest extends TestCase {
	
	//use TestCaseTrait;

	public function testDirectories() 
	{
		$this->assertDirectoryExists('images');
		$this->assertDirectoryIsWritable('images');
	}
	

	public function testDB()
	{
		$db = new DB('local');
		$db->connect('wp');
		$db->query('SHOW tables');
		$records = $db->getRecords();
		$this->assertGreaterThan(0, count($records));
	}
}