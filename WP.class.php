<?php

class WP {
	
	public $db;
	public $wp;
	private $cmdFile;
	private $options;

	public function __construct($db, $options) {
		$this->db = $db;
		$this->wp = $options->wordpressPath;
		$this->options = $options;
		
		$cmdPath = 'importCmds.sh';
		$this->cmdFile = fopen($cmdPath, 'w+');
	}

	public function __destruct() {
		fclose($this->cmdFile);
	}

	// public function featuredImage($wpPostId, $url) {

	// 	$wp_posts = DB::wptable('posts');
	// 	$wp_postmeta = DB::wptable('postmeta');
	// 	$guid = $this->wp . '/' . $url;

	// 	$sql = "INSERT INTO $wp_posts (post_type, guid) VALUES ('attachment', '$guid')";
	// 	$this->db->query($sql);
	// 	$image_id = $this->db->lastInsertId();

	// 	$sql = "INSERT INTO $wp_postmeta (post_id, meta_key, meta_value) VALUES ($wpPostId, '_thumbnail_id', $image_id)";
	// 	$this->db->query($sql);

	// }

	public function addMediaLibrary($wpPostId, $file, $options) {

		$wordpressPath = $options->wordpressPath;
		$imageStore = $options->imageStore;

		$url = $file->filename;

		$name = basename($url);

		if (file_exists("$imageStore/$url")) {
		// use wp-cli to add images to the media library
			$wpUrl = $options->wordpressURL;
			$cmd = "wp media import $imageStore/$url --post_id=$wpPostId --url='$wpUrl' --title=\"$name\"";

			// guess??
			$featured = $file->type === 'node';

			if ($featured) {
				$cmd .= ' --featured_image';
			}
			fputs($this->cmdFile, $cmd . "\n");
		} else {
			if ($this->options->verbose) {
				debug("$imageStore/$url did not exist???");
			}
		}
	}
}
