<?php

class DB {

	public static $wp_prefix = 'wp_38_';

	public $wp = [
		'database' => 'newprod_local',
		'username' => 'root',
		'password' => 'root',
		'host' => 'localhost'
    	];

	public $d7 = [
		'database' => 'd7telematics',
		'username' => 'root',
		'password' => 'root',
		'host' => 'localhost'
	];

	public $type;
	public $db;
	public $connection;
	public $result;
	public $rows;

	public function __construct($type = '') {
		if (!$type) {
			throw new Exception('to instantiate a DB, use a type!');
		}
		$this->type = $type;
		switch ($this->type) {
			case 'wp' :
				$this->db = $this->wp;
				break;
			case 'd7' :
				$this->db = $this->d7;
				break;
			default:
				die('unknown connection type');
		}

		$this->connection = new mysqli(
				$this->db['host'],
				$this->db['username'],
				$this->db['password'],
				$this->db['database']);

		// Check connection
		if ($this->connection->connect_error) {
		    throw new Exception("Connection failed: " . $this->type . ' ' . $this->connection->connect_error);
		}
	}

	public static function wptable($type) {
		switch($type) {
			case 'postmeta':
			case 'posts':
			case 'termmeta':
			case 'terms':
			case 'term_relationships':
			case 'term_taxonomy':
				return static::$wp_prefix . $type;
			default:
				die('unknown table type for wordpress : '.$type);
		}
	}

	public function close() {
		$this->connection->close();
	}

	public function getConnection() {
		return $this->connection;
	}

	public function query($sql) {

		$rowCount = 0;

		try {

			$result = $this->connection->query($sql);

		} catch (Exception $e) {

			if ($result === false) {
				print "\nQuery failed! $sql \n";
			}
		
			die($e->getMessage());

		}

		if ($result) {

			$this->result = $result;

			$rowCount = $this->connection->affected_rows;

			// if the rowCount < 1 
			// and it is a DELETE of an empty table
			// the assert will be a problem...
			if (!strncmp('DELETE FROM', $sql)) {
				assert($rowCount > 0);
			}

		} else {

			return false;
		}

		return $rowCount;
	}

	/* for low level calls such as show tables, 
	   do not populate $this->rows 
	   and each row returned is an array element
	*/
	public function rows($sql) {

		$numRows = $this->query($sql);
		$rows = [];
		if ($numRows) {
			for ($c = 0; $c< $numRows; $c++) {
				$rowSet = $this->result->fetch_row();
				while ($row = array_pop($rowSet)) {
					$rows[] = $row;
				}
			}
			return $rows;
		} else {
			return NULL;
		}
	}

	private function getObjects() {
		$this->rows = [];
		while ( $row = $this->result->fetch_object()) {
			$this->rows[] = $row;
		}
	}

	public function record($sql) {
		$numRows = $this->query($sql);
		if ($numRows > 1) {
			throw new Exception('record query returned more rows than the expected single row: ' . $sql);
		}
		$record = $this->getRecord();
		return $record;
	} 

	public function records($sql) {
		$numRows = $this->query($sql);
		if ($numRows) {
			return $this->getRecords();
		} else {
			return NULL;
		}
	}

	public function getRecord() {
		if ($this->result) {
			$row = $this->result->fetch_object();
			return $row;
		} else {

			throw new Exception('DB::getRecord() but no result variable?');

		}
	}

	public function getRecords() {
		if ($this->result) {
			$this->getObjects();
			return $this->rows;
		} else {
			throw new Exception('send a query before getting Rows!');
		}
	}

	public function lastInsertId() {
		return $this->connection->insert_id;
	}

	static public function strip($sql, $crlf = false) {
		$sql = str_replace(["\n", "\t", "  "],["", " ", " "], $sql);
		if ($crlf) {
			$sql = "\n" . $sql . "\n";
		}
		return $sql;
	}
}
