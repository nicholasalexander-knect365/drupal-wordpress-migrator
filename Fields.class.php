<?php

/* node centric field retrieval */

require_once "DB.class.php";

class Fields {
	
	public $db;
	public $nid;

	public function __construct($db) {
		$this->db = $db;
	}

	// config instances define the field name for the fid
	public function getConfigInstance($fid) {
		$sql = "SELECT id, field_name, entity_type, bundle, data, deleted 
				FROM field_config_instance 
				WHERE field_id=$fid";
		$records = $this->db->records($sql);
		return $records;
	}

	/* node centric */
	public function setNodeId($nid) {
		$this->nid = $nid;
	}

	// appears not used?
	public function CHECKgetFieldDataBody() {
		$nid = $this->nid;
		$sql = "SELECT entity_type, bundle, deleted, entity_id, revision_id, language, delta, body_value, body_summary, body_format 
				FROM field_data_body 
				WHERE entity_id=$nid";

		$records = $this->db->records($sql);
		if ($records && count($records) === 1) {
			return $records[0];
		}
		return $records;
	}

	/* USED?? related to taxonomy? by field_tags_tid */
	public function CHECKgetFieldTags() {
		$nid = $this->nid;
		$sql = "SELECT fd.entity_type, fd.bundle, fd.deleted, fd.entity_id, fd.revision_id, fd.language, fd.delta, fd.field_tags_tid, td.vid, td.name, td.description, td.format, td.weight 
			FROM field_data_body fd
			INNER JOIN taxonomy_term_data td ON td.tid=fd.field_tags_tid 
			WHERE entity_id=$nid";

		$records = $this->db->records($sql);
		if ($records && count($records) === 1) {
			return $records[0];
		}
		return $records;
	}

	public function getFieldImages() {
		$nid = $this->nid;
		$sql = "SELECT entity_type, bundle, deleted, entity_id, revision_id, language, delta, field_image_fid, field_image_alt, field_image_title FROM field_data_field_image WHERE entity_id=$nid";
		$records = $this->db->records($sql);
		if ($records && count($records) === 1) {
			return $records[0];
		}
		return $records;
	}

	public function getFieldComments() {
		$nid = $this->nid;
		$sql = "SELECT entity_type, bundle, deleted, entity_id, revision_id, language, delta, comment_body_value, comment_body_format 
			FROM field_data_comment_body 
			WHERE entity_id=$nid";
		$records = $this->db->records($sql);
		if ($records && count($records) === 1) {
			return $records[0];
		}
		return $records;
	}	

}