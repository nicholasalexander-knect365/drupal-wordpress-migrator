<?php

/* create Wordpress POST elements */

include_once "DB.class.php";

define('WP_ADMIN_ID', 185333);
define('DRUPAL_ADMIN_EMAIL', 'steve@adaptive.co.uk');

class Post extends DB {

	public $db;
	public $wp_post_fields = ['ID', 'post_author', 'post_date', 'post_date_gmt', 'post_content', 'post_title', 'post_excerpt', 'post_status', 'post_password', 'post_name', 'to_ping', 'pinged', 'post_modified', 'post_modified_gmt', 'post_content_filtered', 'post_parent', 'guid', 'menu_order', 'post_type', 'post_mime_type', 'comment_count', 'comment_status'];

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
		'page'			=> 'page',
		'media_entity'	=> 'media',
		'block_content'	=> 'block',
		'display_admin' => 'page',
		'gating_copy'	=> 'page'
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
	private $userPasswordChanged = [];

	public function __construct($db, $options) {
		$this->db = $db;
		$this->options = $options;
		$this->timezone_add = 0;
		static::$translation_warning = 0;
	}

	private function findMakes($item) {
		return strpos('make_', $item);
	}

	protected function prepare($str) {
		$str = $this->db->prepare($str);
		return $str;
	}

	public function nodeToPost($nodeId) {
		$wp_termmeta = DB::wptable('termmeta');
		$taxonomy = new Taxonomy($this->db, $this->options);
		$term_id = $taxonomy->getSetTerm(DRUPAL_WP, DRUPAL_WP);

		$sql = "SELECT * FROM $wp_termmeta WHERE term_id=$term_id AND FLOOR(meta_key)='$nodeId'";
		$record = $this->db->record($sql);

		if (isset($record) && isset($record->meta_value)) {
			$wpPostId = (integer) $record->meta_value;
			return $wpPostId;
		} else {
			return NULL;
		}
	}

	public function replacePostContent($wpPostId, $drupalNode, $includeUser = false, $users = null) {

		$wp_posts = DB::wptable('posts');
		$wp_postmeta = DB::wptable('postmeta');

		$sql = "SELECT *, u.user_email AS user_email FROM $wp_posts p LEFT JOIN wp_users u ON p.post_author=u.ID WHERE p.ID=$wpPostId";
		$record = $this->db->record($sql);

		$user_email = $record->user_email;

		$post_name = Taxonomy::slugify($drupalNode->title);
		$post_content = $this->prepare($drupalNode->content);

		$userClause = '';

		if ($includeUser && $user_email) {

			// if the drupal nodes UID is 0 - then it is an admin post.  
			// in tu-auto the admin user has no email address but user 1 does
// 			if ($drupalNode->uid === 0) {
//dd($user_email);
// 			}
			// $drupalUser = $users->getDrupalUserByUid($drupalNode->uid);
			// what is the wordpress user for that email address

			$postAuthor = $users->getWordpressUserByEmail($user_email);

			if ($postAuthor && $postAuthor->ID) {
				$postAuthorId = $postAuthor->ID;

				if ($postAuthorId) {
					$userClause = ", post_author='$postAuthorId'";
				}

				if ($this->options && $this->options->resetUserPassword && empty($this->userPasswordChanged[$postAuthor->user_email])) {
					$users->setWordpressUserPassword($postAuthor->user_email);
					$this->userPasswordChanged[$postAuthor->user_email] = 1;
				}
			}
		}

		$sql = "UPDATE $wp_posts 
			SET post_name='$post_name', post_content='$post_content' 
			$userClause
			WHERE ID=$wpPostId LIMIT 1";

		//print "\n$post_name";

		try {
			$this->db->query($sql);
		} catch (Exception $e) {
			throw new Exception($sql . "\nError in SQL statement " . $e->getMessage());
		}
	}

	// make an attachment with a URL returning its postmeta ID
	public function makeAttachment($wpPostId, $url) {

		$wp_posts = DB::wptable('posts');
		$wp_postmeta = DB::wptable('postmeta');

		$guid = $this->options->get('wordpressPath') . '/' . $url;
		$sql = "INSERT INTO $wp_posts (post_type, guid) VALUES ('attachment', $url)";
		$this->db->query($sql);
		$post_id = $this->db->lastInsertId();
		$sql = "INSERT INTO $wp_postmeta (post_id, meta_key, meta_value) VALUES ($wpPostId, '_thumbnail_id', $post_id)";
		$this->db->query($sql);
		return $this->db->lastInsertId();
	}

	private function mediaPathsInContent($drupal_data, $deprecate = true) {

		$postContent = $this->prepare($drupal_data->content);

		if ($deprecate) {
			return $postContent;
		}

		if (preg_match('/<img.*?src="http:\/\/(www\.)?ioti\.com/', $postContent, $src)) {

			// debug($postContent);
			// debug($src);
			preg_match('/<img .*?src\=["](.*?)["]$/', $postContent, $parts);
//dd($parts);

			//<img src="http://www.ioti.com/sites/iot-institute.com/files/Enterprise%20IoT%20World.png"
			$postContent = preg_replace('/src="http:\/\/(www\.)?ioti\.com\/sites\/iot\-institute\.com\/files\//','src="files/2018/06/', $postContent);
		// get the image

// debug("\n---------------------------------");
// debug($postContent);

			return $postContent;

		}
	}

	public function makePost($drupal_data, $options = NULL, $files, $wordpressPath, \User $users) {

		$wp_posts = DB::wptable('posts');

		$values = [];
		$metas = [];

		static $running = 0;

		$nid = $drupal_data->nid;
		$fileSet = $files->fileList($nid);

		$postContent = $this->mediaPathsInContent($drupal_data);

		foreach($drupal_data as $key => $value) {

			$wpKey = static::$mapped[$key];
//debug($wpKey . ' ==> ' .$key);

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

				// $value = $this->prepare($value);

				if ($key === 'created' || $key === 'changed') {
					$value = date('Y-m-d h:i:s', $value);
					$values[$wpKey] = $value;
					// TODO: assume the blog is GMT based: if it isn't 
					// - the TZ difference would is needed 
					//   to calculate GMT for this post
					$values[$wpKey . '_gmt'] = $value;
				}


// debug($value);
				switch ($key) {

					case 'uid':
						$drupalUser = $users->getDrupalUserByUid($value);

						if ($drupalUser && strlen($drupalUser->mail) > 4) {

							// TODO: tu-auto specifc
							if($value === 0 || $drupalUser->mail === DRUPAL_ADMIN_EMAIL) {
								$wordpressUser = $users->getWordpressUserById(WP_ADMIN_ID);
								if ($this->options->verbose) {
									print "\nWP user:";
									debug($wordpressUser);
								}

							} else {
								$wordpressUser = $users->getWordpressUserByEmail($drupalUser->mail);
							}
							if ($wordpressUser) {
								$values[$wpKey] = $wordpressUser->ID;
							} else {
								/// makeWordpressUser is private .... we only want to now make them
								$values[$wpKey] = $users->makeWordpressUser($drupalUser);
							}
						} else {
							debug("$value user with this uid can not be found in the Drupal Database, post assigned to default user in Wordpress");
							//
							//TODO: replace with getWordpressUserById($adminEmail);
							// where $adminEmail = 'administrator@domain' (it would have to be added)
							$wordpressUser = $users->getWordpressUserById(WP_ADMIN_ID);
						}
						break;

					case 'title':
						$values[$wpKey] = $value;
						$values['post_name'] = substr(Taxonomy::slugify($values[$wpKey]), 0, 200);
						if (strlen($values['post_name']) === 0) {
							$values['post_name'] = $options->project . '-' . $running++;
						}

						break;

					case 'content':
						$value = $this->prepare($value);
						if ($options && $options->clean) {
							$value = preg_replace('/ style\=[\'"].*?[\'"]/', '', $value);
						}
						if (isset($fileSet) && count($fileSet)) {
// debug($values);
// dd($fileSet);
							// replace the filename in content: 
							// drupal uses image.preview.png for thumbnails
							// but we only want the actual filename

							foreach ($fileSet as $file) {

								$filename = basename($file->filename);
								$replaceFilename = $filename;
								$preview  = preg_replace(['/.jpg$/', '/.gif$/', '/.png$/'], ['.preview.jpg', '.preview.gif', '.preview.png'], basename($file->filename));

								if (preg_match('/src=["]([\w:\/\-\.\_]+)?["]/i', $value, $matched)) {
									if (count($matched)>0 && strpos($matched[1], $preview)) {
										$replaceFilename = $preview;
// dd($value);
									}
								}

								$value = preg_replace("/src=\".*?$replaceFilename\"/", "src=\"$wordpressPath/$filename\"" , $value);
							}
						}

						$values[$wpKey] = $value;
						break;

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

					// comment activation is ON for this post
					// to control this may be best to use an option setting that is global
					case 'comment':
						$values[$wpKey] = 'open';
						break;

					case 'type' : 

						if ($value === 'media_entity') {
// debug($value);
// debug($drupal_data->title);
							if (is_numeric($drupal_data->title)) {
								$value = sprintf('ThinkstockPhotos-%d.jpg', $drupal_data->title);
//debug($value);
							}
							$values['post_image'] = $value;
							// medi entities are featured images
							return NULL;
						} else {
//debug("\n>>>>" . $key . ' >>>>>> ' .$value . ' <<<<< ' . static::$mapPostType[$value]);
							$values['post_type'] = static::$mapPostType[$value];
						}

						break;

					case 'author':
						$values['author'] = $value;
debug('<<<author detected>>>');
debug($values);
						break;

					default: 
						$values[$wpKey] = $value;
				}
//if ($wpKey !== 'post_content') debug($values[$wpKey]);
//debug("\nkey = " . $wpKey . ' value = ' .$value);
			}
		}

		//TODO: set to an option - probably global for all
		$values['comment_status'] = 'open';

		// if (!isset($values['post_excerpt'])) {
		// 	$values['post_excerpt'] = substr($values['post_content'], 0, 120);
		// }
		$values['post_excerpt'] = '';

		foreach(static::$null_fields as $field) {
			$values[$field] = '';
		}
		foreach(static::$static_fields as $field => $value) {
			$values[$field] = $value;
		}

		$sql = "INSERT into $wp_posts (" . implode(', ', array_keys($values)) . ") VALUES ('" . implode("', '", $values) ."')";
		$this->db->query($sql); 
		$post_id = $this->db->lastInsertId();
//debug($sql);
		return $post_id;

		// TODO: deprecate this?
		// if (isset($filename)) {
		// 	$featured = !$nomatch;
		// 	$wp->addMediaLibrary($post_id, $filename, $featured);
		// }

		// // // set featured image
		// // if (isset($featuredImage)) {
		// // 	$wp->featuredImage($post_id, $featuredImage);
		// // }

		// // meta processing: 
		// // create values in postmeta 
		// foreach ($metas as $key => $value) {
		// 	// $action = static::$mapped[$drupalKey];
		// 	if (preg_match('/^make_/', $value)) {
		// 		// rules to derive this field
		// 		switch ($value) {
		// 			case 'make_post_author':
		// 				var_dump($key, $value);
		// 		}
		// 	}
		// }
		// if (!$post_id) {
		// 	debug($sql);
		// }
		// return $post_id;
	}

	public function updatePost($post_id, $field, $data) {

		$wp_posts = DB::wptable('posts');
		$sql = "UPDATE $wp_posts SET $field = '$data' WHERE ID = $post_id";
		$this->db->query($sql);
debug($sql);

	}

}
