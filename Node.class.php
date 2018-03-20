<?php
require_once "DB.class.php";

class Node {
	public $db;

	public function __construct($db) {
		$this->db = $db;
	}

	public function getNode($nid, $vid = NULL) {

		$sql = "SELECT * FROM node WHERE nid=$nid";
		$clause = '';
		if ($vid) {
			$sql .= " AND vid=$vid";
		}
		$this->db->query($sql);

		$node = $this->db->getRecord();
		return $node;
	}

}