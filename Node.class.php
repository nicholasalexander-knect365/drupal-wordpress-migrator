<?php
require_once "DB.class.php";

class Node {
	public $db;
	public $node;

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

	public function getNodeType($node) {
		$type = $node->type;
		$sql = "SELECT * FROM node_type WHERE type='$type' LIMIT 1";
		$nodeType = $this->db->record($sql);
		return $nodeType;
	}

	public function getNodeRevisions($nid) {
		$sql = "SELECT * FROM node_revision WHERE nid=$nid";
		$revisions = $this->db->records($sql);
		return $revisions;
	}

	// node for export
	public function setNode($node) {
		$this->node = $node;
	}

	

}