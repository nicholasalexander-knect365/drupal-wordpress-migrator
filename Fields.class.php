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

	/* 
		field_data_field_penton_media_[
			PRIMARY image,(fid, alt, title, width, height)
			caption, 
			content (media_conent_fid -> file), 
			credit, 
			embed (3 records - ignore these as no Video Content type),
			type type_value youtube, whitepaper, image*]
		tables provide:
		a) featured image
		b) caption for featured image
		c) drupal node_id it belongs to
		d) correlate by entity/revision id to a node

		therefore: gather all elements and return an object 
	*/
// 	public function penton_media_gather($media_node_id) {
// 		$sql = "SELECT i.entity_id as nid, i.revision_id as vid, i.field_penton_media_image_fid as fid, 
// 				i.field_penton_media_image_alt as alt, i.field_penton_media_image_title as title, 
// 				c.field_penton_media_caption_value as caption, 
// 				mc.field_penton_media_content_fid as fid2, 
// 				cr.field_penton_media_credit_value as credit 
// 				FROM field_data_field_penton_media_image i
// 				LEFT JOIN field_data_field_penton_media_caption c ON c.entity_id = i.entity_id
// 				LEFT JOIN field_data_field_penton_media_content mc on mc.entity_id = i.entity_id
// 				LEFT JOIN field_data_field_penton_media_credit cr ON cr.entity_id = i.entity_id
// 				LEFT JOIN field_data_field_penton_media_type t ON t.entity_id = i.entity_id
// 				LEFT JOIN file_managed f ON f.fid = fid
// 				WHERE t.field_penton_media_type_value = 'image' AND i.entity_id = $media_node_id
// 				GROUP BY i.entity_id";
// 		$records = $this->db->records($sql);
// dd(DB::strip($sql));
// dd($records);
// 		return $records;
// 	}

	public function penton_media_images($media_node_id) {

		$sql = "SELECT i.entity_id as nid, 
				i.revision_id as vid, 
				i.field_penton_media_image_fid as fid, 
				i.field_penton_media_image_alt as alt, 
				i.field_penton_media_image_title as title,
				c.field_penton_media_caption_value as caption,
				cr.field_penton_media_credit_value as credit,
				f.filename as filename,
				f.uri as uri
				FROM field_data_field_penton_media_image i 
				INNER JOIN file_managed f ON f.fid = i.field_penton_media_image_fid
				LEFT JOIN field_data_field_penton_media_caption c ON c.entity_id = i.entity_id
				LEFT JOIN field_data_field_penton_media_content mc on mc.entity_id = i.entity_id
				LEFT JOIN field_data_field_penton_media_credit cr ON cr.entity_id = i.entity_id
				LEFT JOIN field_data_field_penton_media_type t ON t.entity_id = i.entity_id
				WHERE i.entity_id = $media_node_id";

		$records = $this->db->records($sql);
		return $records;
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