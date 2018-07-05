<?php

require_once "DB.class.php";

class WPTermMeta {
	
	public $db;

	public function __construct($db) {
		$this->db = $db;
	}

	public function checkTermMeta($term_id, $meta_key) {
		
		$wp_termmeta = DB::wptable('termmeta');

		$sql = "SELECT COUNT(*) AS c FROM $wp_termmeta WHERE term_id=$term_id AND meta_key='$meta_key'";
		$this->db->query($sql);
		$record = $this->db->record($sql);
		return ($record && $record->c);
	}

	private function getTermMetaId($term_id, $meta_key) {
		
		$wp_termmeta = DB::wptable('termmeta');

		$sql = "SELECT meta_id FROM $wp_termmeta WHERE term_id=$term_id AND meta_key='$meta_key'";
		$record = $this->db->record($sql);
		return $record->meta_id;
	}

	public function createTermMeta($term_id, $meta_key, $meta_value) {

		$wp_termmeta = DB::wptable('termmeta');

		if ($this->checkTermMeta($term_id, $meta_key)) {
			return $this->getTermMetaId($term_id, $meta_key);
		}

		if (strlen($meta_key) > 255) {
			$meta_key = substr($meta_key, 0, 255);
		}

		$sql = "INSERT INTO $wp_termmeta (term_id, meta_key, meta_value) VALUES ($term_id, '$meta_key', '$meta_value')";

		$this->db->query($sql);
		$meta_id = $this->db->lastInsertId();
		return $meta_id;
	}

	public function getTermMetaKey($term_id, $meta_value) {

		$wp_termmeta = DB::wptable('termmeta');

		$sql = "SELECT meta_key FROM $wp_termmeta WHERE term_id=$term_id AND meta_value = $meta_value";
		$record = $this->db->record($sql);

		if ($record && $record->meta_key) {
			return $record->meta_key;
		}
		return NULL;
	}

	public function getTermMetaValue($term_id, $meta_key) {

		$wp_termmeta = DB::wptable('termmeta');

		$sql = "SELECT meta_value FROM $wp_termmeta WHERE term_id='$term_id' AND meta_key='$meta_key'";
		$record = $this->db->record($sql);

		if ($record && $record->meta_value) {
			return $record->meta_value;
		}
		return NULL;
	}
}