<?php

/* create Wordpress POST elements */

include_once "DB.class.php";

class Post {

	public $db;
	public $wp_post_fields = ['ID', 'post_author', 'post_date', 'post_date_gmt', 'post_content', 'post_title', 'post_excerpt', 'post_status', 'post_password', 'post_name', 'to_ping', 'pinged', 'post_modified', 'post_modified_gmt', 'post_content_filtered', 'post_parent', 'guid', 'menu_order', 'post_type', 'post_mime_type', 'comment_count'];

	public static $mapPostType = [
		'article' 		=> 'post',
		'banners'		=> 'banner',
		'blog'			=> 'post',
		'event'			=> 'event',
		'job'			=> 'job',
		'webinar'		=> 'webinar',
		'report'		=> 'white-paper',
		'slideshow'  	=> 'slideshow',
		'whitepaper' 	=> 'white-paper',
		'podcast'		=> 'podcast',
		'video'	  		=> 'video',
		'article_banner' => 'banner',
		'homepage_tabs'	=> 'homepage_tabs',
		'page'			=> 'page'
	];

	// map drupal fileds to wordpress fields
	public static $mapped = [
		'nid'		=> 'ignore_nid',
		'vid'		=> 'ignore_vid',
		'type' 		=> 'post_type',
		'language' 	=> 'make_language',
		'title' 	=> 'post_title',
		'uid' 		=> 'post_author',
		'status' 	=> 'post_status',
		'created' 	=> 'post_date',
		'changed' 	=> 'post_modified',
		'comment' 	=> 'comment_status',
		'promote' 	=> 'make_meta_featured',
		'sticky'	=> 'make_meta_sticky',
		'tnid'		=> 'ignore_translation_post_id',
		'translate'	=> 'ignore_translate',
		'content'	=> 'post_content',
		'precis'	=> 'post_excerpt'
	];

	/* Wordpress fields that are initialised with empty string */
	public static $null_fields = [
		'to_ping',
		'pinged',
		'post_content_filtered',
		'post_password',
		'guid',
		'post_mime_type',
	];

	public static $static_fields = [
		'ping_status'	=> 'open',
		'comment_count' => 0,
		'menu_order' => 0,
		'post_parent' => 0
	];

	public static $translation_warning;

	public function __construct($db) {
		$this->db = $db;
		$this->timezone_add = 0;
		static::$translation_warning = 0;
	}

	public function purge() {
		$sql = "DELETE FROM wp_posts";
		$this->db->query($sql);
	}

	private function findMakes($item) {
		return strpos('make_', $item);
	}

	private function prepare($str) {
        $str = html_entity_decode($str);
        $str = str_replace(array("\r\n", "\r", "\n"), '', $str);
        $str = preg_replace('/\'/', '&apos;', $str);
        ///$str = preg_replace('/\"/', '&quot;', $str);
        return $str;
	}

	public function makePost($drupal_data) {

		$values = [];
		$metas = [];
		static $running = 0;

		foreach($drupal_data as $key => $value) {
	
			$wpKey = static::$mapped[$key];

			// if drupal fields are prefixed make_ 
			// they are post_meta and are created AFTER post is created
			if (preg_match('/^make_/', $wpKey)) {
				$metas[$key] = $value;

			} else if (preg_match('/^ignore_/', $wpKey) && isset($value)) {

				if (preg_match('/^ignore_trans/', static::$translation_warning === 0)) {
					print "\nWarning: translation data exists in drupal!";
					static::$translation_warning = 1;
				}

			} else {

				$value =$this->prepare($value);

				if ($key === 'created' || $key === 'changed') {
					$value = date('Y-m-d h:i:s', $value);
					$values[$wpKey] = $value;
					// assume the blog is GMT based: if it isn't 
					// - the TZ difference would is needed 
					//   to calculate GMT for this post
					$values[$wpKey . '_gmt'] = $value;
				} 
				switch ($key) {
					case 'status':
						if ($value === '1' || $value === 1) {
							$values[$wpKey] = 'publish';
						} else {
							$values[$wpKey] = 'draft';
						}
						break;
					case 'uid': 
						$values[$wpKey] = 1;
						break;
					case 'comment':
						if ($value === 1) {
							$values[$wpKey] = 'open';
						} else {
							$values[$wpKey] = 'closed';
						}
						break;
					case 'title' :
						$values[$wpKey] = $value;
						$values['post_name'] = Taxonomy::slugify($value);
						if (strlen($values['post_name']) === 0) {
							$values['post_name'] = 'tu-auto-' . $running++;
						}
						break;
					case 'type' : 
						$values['post_type'] = static::$mapPostType[$value];
						break;

					default: 
						$values[$wpKey] = $value;
				}
			}			
		}
		foreach(static::$null_fields as $field) {
			$values[$field] = '';
		}
		foreach(static::$static_fields as $field => $value) {
			$values[$field] = $value;
		}

		$sql = "INSERT into wp_posts (" . implode(', ', array_keys($values)) . ") VALUES ('" . implode("', '", $values) ."')";

		$this->db->query($sql); 
		$post_id = $this->db->lastInsertId();


		// meta processing: 
		// create values in postmeta 
		foreach ($metas as $key => $value) {
			// $action = static::$mapped[$drupalKey];
			if (preg_match('/^make_/', $value)) {
				// rules to derive this field
				switch ($value) {
					case 'make_post_author':
						var_dump($key, $value);
				}
			}
		}
		return $post_id;
	}
}