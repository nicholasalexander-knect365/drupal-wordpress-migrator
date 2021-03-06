<?php
require_once "DB.class.php";

class Node {
	public $db;
	public $node;
	public $limit;
	public $start;
	public $users;

	public function __construct($db) {
		$this->db = $db;
		$this->start = 0;
		$this->users = [];
	}

	public function getNode($nid, $vid = NULL) {

		$sql = "SELECT * FROM node WHERE nid=$nid";
		$clause = '';
		if ($vid) {
			$sql .= " AND vid=$vid";
		}
		$this->db->runQuery($sql);

		$node = $this->db->getRecord();
		$this->users[$node->nid] = $node;
		return $node;
	}


	public function getAuthor($node) {
		if (empty($this->users[$node->nid])) {
			$this->users[$node->nid] = 0;
		}
		$this->users[$node->nid]++;
		return $node->nid;
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

	public function nodeCount() {

		$sql = "SELECT COUNT(*) AS c FROM node WHERE status = 1";
		$items = $this->db->record($sql);

		return $items->c;
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

	public function getNodeChunk($limiter = null) {
		
		$start = $this->start;
		if ($limiter) {
			$limit = $limiter;
		} else {
			$limit = $this->limit;
		}

		$sql = "SELECT n.nid, n.vid, n.type, n.language, n.title, n.uid, n.status, n.created, n.changed, n.comment, n.promote, n.sticky, n.tnid, n.translate, b.body_value as content,p.field_precis_value as precis
				FROM node n
				INNER JOIN node_type t ON n.type=t.type
				LEFT JOIN node_revision r ON r.nid=n.nid
				LEFT JOIN field_data_body b on b.entity_id=n.nid
				LEFT JOIN content_field_precis p on p.nid=n.nid
				ORDER by nid
				LIMIT $start, $limit";

		$nodes = $this->db->records($sql);
		$this->start = $start + $limit;

		return $nodes;
	}
	public function getAllNodes() {
		$sql = "SELECT n.nid, n.vid, n.type, n.language, n.title, n.uid, n.status, n.created, n.changed, n.comment, n.promote, n.sticky, n.tnid, n.translate, b.body_value as content,p.field_precis_value as precis
				FROM node n
				INNER JOIN node_type t ON n.type=t.type
				LEFT JOIN node_revision r ON r.nid=n.nid
				LEFT JOIN field_data_body b on b.entity_id=n.nid
				LEFT JOIN content_field_precis p on p.nid=n.nid
				ORDER by nid";
		$nodes = $this->db->records($sql);
		return $nodes;
	}
}