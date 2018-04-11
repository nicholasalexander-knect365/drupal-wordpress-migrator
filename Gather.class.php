<?php

require_once "FieldSetDiscovery.class.php";

// gather additional content type data for a node from Drupal

class Gather extends FieldSetDiscovery {
	
	public $nid;
	private $exclude_migrated = true;
	private $exclude_taxonomy = true;

	public function __construct($db, $table) {

		$this->db = $db;
		$this->discovery($db);
		$this->table = $table;
		$this->exclude_migrated = ! $this->migrated_significant();
	}

	public function setNid($nid) {
		$this->nid = $nid;
	}

	public function migrated_significant() {
		$sql = "SELECT COUNT(*) AS c 
				FROM field_data_field_migrated_original_nid 
				WHERE entity_id != field_data_field_migrated_original_nid";
		$record = $this->db->record($sql);
		return $record['c'] > 2;
	}

	public function __call($name, $args) {

		if ($this->exclude_migrated) {
			if ($this->table === 'migrated_original_nid') {
				return NULL;
			}
		}
		if ($this->exclude_taxonomy) {
			if ($this->table === 'article_type') {
				return NULL;
			}
		}
		
		$table = 'field_data_field_' . $this->table;
		$nid = $this->nid;

		$fields =  $this->getFieldNames(__FUNCTION__);
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