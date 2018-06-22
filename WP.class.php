<?php

class WP {
	
	public $db;
	public $wp;
	private $cmdFile;
	private $options;
	private $cmds;

	public function __construct($db, $options) {
		$this->db = $db;
		$this->wp = $options->wordpressPath;
		$this->options = $options;
		$this->cmds = [];
		
		$cmdPath = 'importCmds.sh';
		$this->cmdFile = fopen($cmdPath, 'w+');
	}

	public function __destruct() {
		fclose($this->cmdFile);
	}

	// TODO: is this deprecated and we use wp-cli instead
	public function DEPRECATEDfeaturedImage($wpPostId, $url) {

		throw new Exception('featuredImage: call to deprecated function');

		$wp_posts = DB::wptable('posts');
		$wp_postmeta = DB::wptable('postmeta');
		$guid = $this->wp . '/' . $url;
		$today = date("Y-m-d H:i:s");

		$sql = "INSERT INTO $wp_posts (post_type, guid) VALUES ('attachment', '$guid')";
		$this->db->query($sql);
		$image_id = $this->db->lastInsertId();

		if ($image_id) {
			$sql = "INSERT INTO $wp_postmeta (post_id, meta_key, meta_value) VALUES ($wpPostId, '_thumbnail_id', $image_id)";
			$this->db->query($sql);

		} else {
			throw new Exception("No image_id from insert into posts? " . $sql);
		}
	}

	private function addMedia($wpPostId, $url, $imageStore, $options, $featured, $source) {

		if (!$wpPostId) {
			throw new Exception('No wpPostId in call to addMedia');
		}

		$name = basename($url);

		if (file_exists("$imageStore/$url")) {
			// use wp-cli to add images to the media library
			$wpUrl = $options->wordpressURL;
			if (empty($this->cmds[$url][$wpPostId])) {
				$this->cmds[$url][$wpPostId] = 1;
				if ($wpPostId) {
					$cmd = "wp media import '$imageStore/$url' --post_id=$wpPostId --url='$wpUrl' --title=\"$name\"";
					if ($featured) {
						$cmd .= ' --featured_image';
					}
				} else {
					$cmd = "wp media import '$imageStore/$url' --url='$wpUrl' --title=\"$name\"";
				}
				fputs($this->cmdFile, $cmd . "\n");
			}

		} else {

			if ($this->options->verbose) {
				debug("$imageStore/$url did not exist???");
			}
		}
	}

	public function addMediaLibrary($wpPostId, $file, $options, $featured = true, $source = '') {

		$wordpressPath = $options->wordpressPath;
		$imageStore = $options->imageStore;
		$url = $file->filename;

		$this->addMedia($wpPostId, $url, $imageStore, $options, $featured, $source);
	}

	public function addUrlMediaLibrary($wpPostId, $url, $options, $featured = true, $source = '') {

		$wordpressPath = $options->wordpressPath;
		$imageStore = $options->imageStore;

		$this->addMedia($wpPostId, $url, $imageStore, $options, $featured, $source);
	}
}
