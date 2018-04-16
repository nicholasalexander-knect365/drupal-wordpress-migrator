<?php

include_once "DB.class.php";

class Tag {

	private $db;

	public function __construct($db) {
		$this->db = $db;
	}

	public function tagExists($name) {
		$sql = "SELECT COUNT(*) AS c FROM wp_term_taxonomy WHERE taxonomy='$name'";
		$record = $this->db->record($sql);
		return isset($record && $record->c > 0) ? $record->c : 0; 
	}

	public function getAll() {
		$sql = "SELECT * FROM wp_term_taxonomy WHERE taxonomy='post_tag'";
		$records = $this->db->records($sql);
		return $records;
	}
	
}