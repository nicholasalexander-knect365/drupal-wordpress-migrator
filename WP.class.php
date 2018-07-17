<?php

require_once "DB.class.php";

class WP {
	
	private $db;
	private $wp;
	private $cmdFile;
	private $options;
	private $cmds;
	private $preventDuplicates;

	public function __construct($db, $options) {
		$this->db = $db;
		$this->wp = $options->wordpressPath;
		$this->options = $options;
		$this->cmds = [];
		$this->preventDuplicates = [];
		
		$cmdPath = 'importCmds.sh';
		$this->cmdFile = fopen($cmdPath, 'w+');
	}

	public function __destruct() {
		fclose($this->cmdFile);
	}

	// passing in the file handle as sometimes it switched databases after doing drupal queries
	public function getWordpressUserId($uid, $drupalUidKey) {

		//$wpress = new DB('wp', $this->options);
//dd($this->options);
		if ($this->options->server === 'multisite') {
			$handle = 'telecoms_local';
		} else if ($this->options->server === 'staging') {
			$handle = 'test2_telecoms_com';
		} else if ($this->options->server === 'beta') {
			$handle = 'test1_telecoms_com';
		} else if ($this->options->server === 'local') {
			$handle = 'ioti';
		} else {
			dd($this->options);
		}
//$handle = 'telecoms_local';

		$drupal_uid = $drupalUidKey; //$this->drupal_uid_key;

		$sql = "SELECT * FROM `$handle`.wp_usermeta WHERE meta_key='$drupal_uid' AND meta_value LIKE '$uid'";

//debug($sql);

		$record = $this->db->record($sql);
		//$record = $wpress->record($sql);

//debug($record);

		if ($record) {
			return $record->user_id;
		} else {

			return NULL;
		}
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
//debug("\naddMedia for the postID: ".$wpPostId . ' url '. $url);

		if (file_exists("$imageStore/$url")) {

			// use wp-cli to add images to the media library
			$wpUrl = $options->wordpressURL;

			if (empty($this->preventDuplicates[sprintf('%s%d', $url, $wpPostId)])) {

				$this->preventDuplicates[sprintf('%s%d', $url, $wpPostId)] = 1;

				if ($wpPostId) {
					$cmd = "wp media import '$imageStore/$url' --post_id=$wpPostId --url='$wpUrl' --title=\"$name\"";
					if ($featured) {
						$cmd .= ' --featured_image';
					}
				} else {
					$cmd = "wp media import '$imageStore/$url' --url='$wpUrl' --title=\"$name\"";
				}
				fputs($this->cmdFile, $cmd . "\n");
			} else {
				debug('Duplicated? '.  sprintf('%s %d', $url, $wpPostId) . '='. $this->preventDuplicates[sprintf('%s%d', $url, $wpPostId)]);
			}

		} else {

			if ($this->options->verbose) {
				debug("$imageStore/$url did not exist???");
			}
		}
	}

	public function addMediaLibrary($wpPostId, $filename, $options, $featured = true, $source = '') {

		// filename is passed in as an object or a string
		if (is_object($filename)) {
			$filename = $filename->filename;
		}

		$wordpressPath = $options->wordpressPath;
		$imageStore = $options->imageStore;
		$url = $filename;

		$this->addMedia($wpPostId, $url, $imageStore, $options, $featured, $source);
	}

}