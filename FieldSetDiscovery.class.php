<?php

require_once "DB.class.php";

abstract class FieldSetDiscovery {

	protected $bundle;
	protected $entity;
	protected $table;
	protected $db;

	protected function discovery($db) {
		$this->db = $db;
	}

	public function setEntity($type) {
		$this->entity = $type;
	}

	public function setBundle($bundle) {
		$this->bundle = $bundle;
	}

	public function setTable($table) {
		$this->table = $table;
	}

	protected function findFieldTypesContent() {
		
		$sql = "SHOW TABLES LIKE 'field_data_field_%_%'";
		$tables = $this->db->rows($sql);
		$pairs = [];
		$fieldTypes = [];
		foreach ($tables as $table) {
			if (preg_match('/^field_data_field_([\w]+?)_([\w_]+)/', $table, $matched)) {
				// pair:   key          => label
				$pairs[] = [$matched[1], $matched[2]];
				if (empty($fieldTypes[$matched[1]])) {
					$fieldTypes[$matched[1]] = 0;
				}
				$fieldTypes[$matched[1]]++;
			}
		}
		return [$fieldTypes, $pairs];
	}

	public function getFieldNames() {
		$table = $this->table;
		$sql = "SHOW COLUMNS FROM field_data_field_$table";
		$records = $this->db->records($sql);
		$fields = [];
		foreach($records as $value) {
			if (strpos($value->Field, $table)) {
				$fields[] = $value->Field;
			}
		}
		return $fields;
	}

	public function fieldSetTable($type, $item) {
		$table = 'field_data_field_' . $type . '_' .$item;
		return $table;
	}
	
	public function getAllRecords($type, $item) {

		$table = $this->fieldSetTable($type, $item);
		$sql = "SELECT * FROM $table";
		$records = $this->db->records($sql);

		return $records;
	}

	protected function getElements($bundle) {
		$sql = "";
	}

	protected function findTables() {
		$bundle = $this->bundle;
		$entity = $this->entity;

		$sql = "SHOW TABLES LIKE 'field_data_field_$bundle_%'";
		$this->db->query($sql);
		$tables = $this->db->getRecords();
	}
}