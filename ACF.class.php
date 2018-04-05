<?php

class ACF {
	
	public $postId;

	public function __construct($db) {
		$this->db = $db;
	}

	public function setPostId($post_id) {
		$this->postId = $post_id;
	}


}