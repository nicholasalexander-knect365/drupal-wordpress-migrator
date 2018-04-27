<?php

class WP {
	
	public $db;
	public $wp;
	private $cmdFile;

	public function __construct($db, $options) {
		$this->db = $db;
		$this->wp = $options->wordpressPath;

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

		// $wp_posts = DB::wptable('posts');
		// $wp_postmeta = DB::wptable('postmeta');

		$wordpressPath = $options->wordpressPath;
		$imagePath = $options->imageStore;

		$url = $file->filename;

		
		//$guid = $this->wp . '/' . $url;
		
		// $parts = explode($url, '.');
		// $name = $parts[0];
		// $type = $parts[count($parts)];
		
		// if (!in_array($type, ['jpg', 'jpeg', 'gif', 'png', 'svg', 'webp'])) {
		// 	throw new Exception('Unsupported image type attempt to add to Media Library, image url: '.$url);
		// }
		// if ($type === 'jpg') {
		// 	$type = 'jpeg';
		// }
		// if ($type === 'svg') {
		// 	$type = 'svg+xml';
		// }

		// $thumb = preg_replace*
		// $unserialised = [
		// 	'width' => 500,
		// 	'height' => 300,
		// 	'hwstring_small' => "height='96' width='96'",
		// 	'file' => $url, 
		// 	'sizes' => [
		// 		'thumbnail' => [
		// 			'file' => ]]
		// ];

		// $sql = "INSERT INTO $wp_posts (post_type, post_title, post_name, post_status, guid, post_mime_type) VALUES ('attachment', '$name', '$name', 'inherit', '$guid', 'image/'.$type)";
		// $this->db->query($sql);
		// $image_id = $this->db->lastInsertId();

		// $sql = "INSERT INTO $wp_postmeta (post_id, meta_key, meta_value) VALUES ($wpPostId, '_wp_attached_file', '$url')";
		// $this->db->query($sql);

		// $sql = "INSERT INTO $wp_postmeta (post_id, meta_key, meta_value) VALUES ($wpPostId, '_wp_attachment_metadata', '$jsonSpecs')";
		// $this->db->query($sql);


		$name = basename($url);

		if (file_exists("$imagePath/$url")) {
		// use wp-cli to add images to the media library
			$cmd = "wp media import $imagePath/$url --post_id=$wpPostId --title=\"$name\"";

			// guess??
			$featured = $file->type === 'node';

			if ($featured) {
				$cmd .= ' --featured_image';
			}
			fputs($this->cmdFile, $cmd . "\n");
		} else {
			debug("$imagePath/$url does not exist???");
		}
	}
}
