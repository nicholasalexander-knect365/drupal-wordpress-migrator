<?php

require_once "DB.class.php";

class WPTermMeta {
	
	public $db;

	public function __construct($db) {
		$this->db = $db;
	}

	// set the term and return its ID OR get the ID
	public function getSetTerm($term_name, $term_slug) {
		$sql = "SELECT COUNT(*) as c FROM wp_terms WHERE slug='$term_slug'";
		$record = $this->db->record($sql);

		if ($record && $record->c) {
			$sql = "SELECT term_id FROM wp_terms WHERE slug='$term_slug'";
		
			$record = $this->db->record($sql);
	//dd($record);	
			return (integer) $record->term_id;

		} else {			
			$sql = "INSERT INTO wp_terms (name, slug, term_group) VALUES ('$term_name', '$term_slug', 0)";
	//die($sql);	
			$this->db->query($sql);
			$term_id = $this->db->lastInsertId();

			return (integer) $term_id;
		}
	}

	public function checkTermMeta($term_id, $meta_key) {
		$sql = "SELECT COUNT(*) AS c FROM wp_termmeta WHERE term_id=$term_id AND meta_key='$meta_key'";
		$this->db->query($sql);		
		$record = $this->db->record($sql);
		return ($record && $record->c);
	}

	private function getTermMetaId($term_id, $meta_key) {
		$sql = "SELECT meta_id FROM wp_termmeta WHERE term_id=$term_id AND meta_key='$meta_key'";
		$record = $this->db->record($sql);
		return $record->meta_id;
	}

	public function createTermMeta($term_id, $meta_key, $meta_value) {
		if ($this->checkTermMeta($term_id, $meta_key)) {
			return $this->getTermMetaId($term_id, $meta_key);
		}
		$sql = "INSERT INTO wp_termmeta (term_id, meta_key, meta_value) VALUES ($term_id, '$meta_key', '$meta_value')";
		$this->db->query($sql);
		$meta_id = $this->db->lastInsertId();
		return $meta_id;
	}

	public function getTermMetaValue($term_id, $meta_key) {
		$sql = "SELECT meta_value FROM wp_termmeta WHERE term_id='$term_id' AND meta_key='$meta_key'";
		$record = $this->db->record($sql);
		if ($record && $record->meta_value) {
			return $record->meta_value;
		}
		return NULL;
	}

}