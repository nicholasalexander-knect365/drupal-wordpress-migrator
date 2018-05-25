<?php

/* create Wordpress POST elements */

include_once "DB.class.php";
//include_once "User.class.php";

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

	public function replacePostContent($wpPostId, $drupalNode, $includeUser = false, $users = null) {

		$wp_posts = DB::wptable('posts');
		$wp_postmeta = DB::wptable('postmeta');

		$sql = "SELECT *, u.user_email AS user_email FROM $wp_posts p LEFT JOIN wp_users u ON p.post_author=u.ID WHERE p.ID=$wpPostId";
		
		//$sql = "SELECT * FROM $wp_posts WHERE ID=$wpPostId";
		$record = $this->db->record($sql);

//dd(DB::strip($sql));
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

	public function makePost($drupal_data, $options = NULL, $files, $wordpressPath, \User $users) {

		$wp_posts = DB::wptable('posts');

		$values = [];
		$metas = [];

		static $running = 0;

		$nid = $drupal_data->nid;
		$fileSet = $files->fileList($nid);

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

				$value = $this->prepare($value);

				if ($key === 'created' || $key === 'changed') {
					$value = date('Y-m-d h:i:s', $value);
					$values[$wpKey] = $value;
					// TODO: assume the blog is GMT based: if it isn't 
					// - the TZ difference would is needed 
					//   to calculate GMT for this post
					$values[$wpKey . '_gmt'] = $value;
				}
				switch ($key) {

					case 'uid':
						$drupalUser = $users->getDrupalUserByUid($value);
						if ($drupalUser && strlen($drupalUser->mail) > 4) {
							if($value === 0 || $drupalUser->mail ==='steve@adaptive.co.uk') {

								$wordpressUser = $users->getWordpressUserById(185333);
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
							$wordpressUser = $users->getWordpressUserById(185333);
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
						if ($options && $options->clean) {
							$value = preg_replace('/ style\=[\'"].*?[\'"]/', '', $value);
						}

						if ($fileSet) {

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
						$values['post_type'] = static::$mapPostType[$value];
						break;

					case 'author':
						$values['author'] = $value;
debug('<<<author detected>>>');
debug($values);
						break;

					default: 
						$values[$wpKey] = $value;
				}
			}
		}

		//TODO: set to an option - probably global for all
		$values['comment_status'] = true;

		foreach(static::$null_fields as $field) {
			$values[$field] = '';
		}
		foreach(static::$static_fields as $field => $value) {
			$values[$field] = $value;
		}

		$sql = "INSERT into $wp_posts (" . implode(', ', array_keys($values)) . ") VALUES ('" . implode("', '", $values) ."')";
		$this->db->query($sql); 
		$post_id = $this->db->lastInsertId();

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
}
