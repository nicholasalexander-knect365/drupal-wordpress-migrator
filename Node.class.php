<?php
require_once "DB.class.php";

class Node {
	public $db;
	public $node;
	public $limit;

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


	public function setNodeChunkSize($limit = NULL) {
		if (!$limit) {
			$limit = 1000;
		}
		$this->limit = $limit;
	}

	public function getNodeChunkSize() {
		if (!$this->limit) {
			$this->setNodeChunk();
		}
		$limit = $this->limit;
	}

	public function getNodeChunk($start) {

		$limit = $this->limit;
		$sql = "SELECT nid, vid, type, language, title, uid, status, created, changed, comment, promote, sticky, tnid, translate, node_comment_statistics_nid
				FROM node n
				INNER JOIN node_type t ON n.type=t.type
				OUTER JOIN node_revision r ON r.nid=n.nid

				ORDER by nid
				LIMIT $start, $limit";

		$nodes = $this->db->records($sql);
var_dump($nodes);
		return $nodes;
	}
}