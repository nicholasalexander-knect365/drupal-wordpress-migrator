<?php 

class PostMeta {
	public $db;
	public $wp_postmeta;

	public function __construct($db, $table) {
		$this->wp_postmeta = $table;
		$this->db = $db;
	}

	// wordpress entities create
	public function createFields($wpPostId, $data) {
		$wp_postmeta = $this->wp_postmeta;
		foreach($data as $key => $value) {
			$sql = "INSERT INTO $wp_postmeta (post_id, meta_key, meta_value) VALUES ($wpPostId, '$key', '$value')";
			$this->db->query($sql);

			$id = $this->db->lastInsertId();
			assert($id > 0);
		}
	}
}