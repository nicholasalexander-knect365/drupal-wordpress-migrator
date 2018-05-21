<?php

// gather additional content type data for a node from Drupal

require_once "FieldSetDiscovery.class.php";

class Gather extends FieldSetDiscovery {
	
	public $nid;

	public function __construct($db, $table) {

		$this->db = $db;
		$this->discovery($db);
		$this->table = $table;
	}

	public function setNid($nid) {
		$this->nid = $nid;
	}

	public function __call($name, $args) {

		$table = 'field_data_field_' . $this->table;
		$nid = $this->nid;

		$fields =  $this->getFieldNames();
		$fieldStr = implode(',', $fields);

		$records = [];
		if (isset($fields) && count($fields)) {
			$sql = "SELECT $fieldStr from $table WHERE entity_id=$nid";
			$records = $this->db->records($sql);
		}

		// should only return an object field => value pair
		if ($records && count($records) === 1) {
			$tableParts = explode('field_data_', $table);
			return [$tableParts[1], $records[0]];
		}

		return $records;
	}
	

	// example of how this call resolves
	// public function get_article_image() {
		
	// 	$table = 'field_data_field_' . $this->table;
	// 	$nid = $this->nid;

	// 	$fields =  $this->getFieldNames(__FUNCTION__);
	// 	$fieldStr = implode(',', $fields);

	// 	$records = [];
	// 	if (isset($fields) && count($fields)) {
	// 		$sql = "SELECT $fieldStr from $table WHERE entity_id=$nid";
	// 		$records = $this->db->records($sql);
	// 	}
	// 	return $records;
	// }
}