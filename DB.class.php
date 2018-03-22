<?php

class DB {

	public $wp = [
		'database' => 'tuwp',
		'username' => 'tuauto',
		'password' => 'tuauto',
		'host' => 'localhost'
    	];

	public $d7 = [
		'database' => 'd7telematics',
		'username' => 'd7telematics',
		'password' => 'zMn5LdPej2pbgqWqEjwmFZ7Y',
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
			assert($rowCount > 0);
		}
		return $rowCount;
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
		$row = $this->result->fetch_object();
		return $row;
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

	static public function strip($sql) {
		return str_replace(["\n", "\t", "  "],["", " ", " "], $sql);
	}
}
