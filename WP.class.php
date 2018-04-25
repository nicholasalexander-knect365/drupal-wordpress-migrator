<?php

class WP {
	public $db;
	public function __construct($db, $options) {
		$this->db = $db;
		$this->wp = $options->wordpressPath;
	}

	public function featuredImage($wpPostId, $url) {

		$wp_posts = DB::wptable('posts');
		$wp_postmeta = DB::wptable('postmeta');
		$guid = $this->wp . '/' . $url;
		$sql = "INSERT INTO $wp_posts (post_type, guid) VALUES ('attachment', '$guid')";

		$sql = "INSERT INTO $wp_postmeta (post_id, meta_key, meta_value) VALUES ($wpPostId, '_thumbnail_id', $wpPostId)";
		$this->db->query($sql);

	}
}
