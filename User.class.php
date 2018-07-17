<?php

// drupal users imported as wordpress users
// must be done before post so post_author can be recognised


include_once "DB.class.php";
define('MAX_USERS', 20000);

class User {
	
	private $capabilities = [];

	private $db;
	private $drupalUsers;
	private $drupalRoles;
	private $config;
	private $drupal_uid_key = ''; //'drupal_uid';

	private $start;
	private $limit;

	public function __construct($wp, $d7, $config) {
		$this->db = $wp;
		$this->d7 = $d7;
		$this->config = $config;
		$this->drupalUsers = [];
		$this->drupalRoles = [];
		$this->capabilities[0] = 'a:1:{s:13:"administrator";b:1;}';
		$this->capabilities[1] = 'a:1:{s:6:"editor";b:1;}';
		$this->capabilities[2] = 'a:1:{s:6:"author";b:1;}';
		$this->capabilities[3] = 'a:1:{s:11:"contributor";b:1}';
		$this->capabilities[4] = 'a:1:{s:10:"subscriber";b:1;}';

		$this->capabilities['subscriber'] = $this->capabilities[4];
		$this->drupal_uid_key = sprintf('drupal_%d_uid', $config->siteId);;

		$this->limit = 0;
		$this->start = 0;
	}

	public function countDUsers() {
		$sql = "SELECT COUNT(*) as c FROM dusers";
		$record = $this->db->record($sql);
		return $record->c;
	}

	public function getDrupalUsers() {
		$sql = "SELECT u.uid, u.name, u.mail, u.signature, u.timezone, u.language, u.created, r.name as role, r.rid as role_id
			FROM users u
			LEFT JOIN users_roles ur ON ur.uid = u.uid
			LEFT JOIN role r on r.rid = ur.rid";
		$this->drupalUsers = $this->d7->records($sql);

		if (count($this->drupalUsers) > MAX_USERS) {
			debug('Warning: there are more than ' . MAX_USERS);
		}
	}

	public function getTempDrupalUsers($chunk = 0) {

		if ($chunk) {
			$start = $this->start;
			$limit = $chunk;
			$sql = "SELECT uid, name, mail, signature, timezone, language, created, role from dusers LIMIT $start, $limit";
			$this->start = $start + $limit;
		} else {
			$sql = "SELECT uid, name, mail, signature, timezone, language, created, role from dusers";
		}
		$this->drupalUsers = $this->db->records($sql);

//print "\n$sql";

		return count((array)$this->drupalUsers);

	}

	public function countDrupalUsers() {
		$c = 0;
		$uniq = [];
		$roles = [];
		foreach($this->drupalUsers as $u) {
			$uniq[$u->uid] = 1;
			$roles[$u->role] = isset($roles[$u->role]) ? $roles[$u->role]+1 : 1;
			$c++;
		}
		
		$this->drupalRoles = $roles;
		return count($uniq);
	}

	public function makeDrupalUsers() {
	
		$sql = "DROP TABLE dusers";
		$this->db->query($sql);

		$sql = "CREATE TABLE `dusers` (
			`uid` int(10) unsigned NOT NULL DEFAULT '0',
			`name` varchar(60) NOT NULL DEFAULT '',
			`pass` varchar(128) NOT NULL DEFAULT '',
			`mail` varchar(254) DEFAULT '',
			`theme` varchar(255) NOT NULL DEFAULT '',
			`signature` varchar(255) NOT NULL DEFAULT '',
			`signature_format` varchar(255) DEFAULT NULL,
			`created` int(11) NOT NULL DEFAULT '0',
			`access` int(11) NOT NULL DEFAULT '0',
			`login` int(11) NOT NULL DEFAULT '0',
			`status` tinyint(4) NOT NULL DEFAULT '0',
			`timezone` varchar(32) DEFAULT NULL,
			`language` varchar(12) NOT NULL DEFAULT '',
			`init` varchar(254) DEFAULT '',
			`data` longblob,
			`picture` int(11) NOT NULL DEFAULT '0',
			`role` varchar(254) NOT NULL DEFAULT '',
			PRIMARY KEY (`uid`),
			UNIQUE KEY `name` (`name`),
			KEY `access` (`access`),
			KEY `created` (`created`),
			KEY `mail` (`mail`),
			KEY `picture` (`picture`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8";
		try {
			$this->db->query($sql);

		} catch (Exception $e) {
			throw new Exception("\nCould not create duser table\n".$e->getMessage());
		}

		foreach ($this->drupalUsers as $u) {
			$uid = $u->uid;
			$name = $u->name;
			$mail = $u->mail;
			$signature = $u->signature;
			$timezone = $u->timezone;
			$language = $u->language;
			$created = $u->created;
			$role = $u->role;

			$sql = "INSERT INTO dusers (uid, name, mail, signature, timezone, language, created, role) VALUES ($uid, '$name', '$mail', '$signature', '$timezone', '$language', '$created', '$role')";

			try {
				$this->db->query($sql);
			} catch (Exception $e) {
				throw new Exception("\nCould not insert into duser table\n" . $e->getMessage());
			}
		}
	}

	public function drupalUsersLoaded() {
		return count($this->drupalUsers);
	}

	public function wordpressUsers() {

		$sql = "SELECT COUNT(*) AS c FROM wp_users";

		$record = $this->db->record($sql);

		if (isset($record) && $record->c) {
			return $record->c;
		}
		return false;
	}

	public function getDrupalUserByUid($uid) {
		if (!is_numeric($uid)) {
			$uid = 0;
		}
		$sql = "SELECT * FROM users u WHERE u.uid = $uid";
		$record = $this->d7->record($sql);
		if ($record->uid === $uid) {
			return $record;
		} else {
			return NULL;
		}
	}

	public function getDrupalUserByEmail($email) {
		$sql = "SELECT * FROM users u WHERE u.mail = '$email'";
		$record = $this->d7->record($sql);
		return $record;
	}

	public function getWordpressUserById($id) {

		$sql = "SELECT * from wp_users where ID = $id";
		$record = $this->db->record($sql);

		return $record;
	}

	public function user_exists($email) {
		if (strlen($email)>3) {
			$sql = "SELECT COUNT(*) AS c FROM wp_users WHERE user_email = '$email'";
			$record = $this->db->record($sql);
			return $record->c;
		} else {
			throw new Exception("\nuser:user_exists called with empty email address.");
		}
	}

	public function getWordpressUserByEmail($email) {
		
		if (strlen($email)>3) {

			$sql = "SELECT * from wp_users where user_email = '$email' ORDER BY ID LIMIT 1";
			$record = $this->db->record($sql);

			return $record;
		} else {
			throw new Exception("\ngetWordpressUserByEmail called with empty email address.");
		}
	}

	public function removeWordpressUser($id) {
		try {
			$sql = "DELETE FROM wp_users WHERE ID=$id LIMIT 1";
			$this->db->query($sql);
		} catch (Exception $e) {
			throw new Exception("\nERROR: can not remove a wordpress user with ID ".$id. "\n".$e->getMessage());
		}
		$rows = $this->db->affectedRows();
		return $rows;
	}

	private function temporaryPassword($email) {
		$str = sprintf('%s%d%s', $email, date('U'), ' new password ');
		return md5($str);
	}

	public function setWordpressUserPassword($email) {
		$user = $this->getWordpressUserByEmail($email);
		if ($user) {
			$user_id = $user->ID;
			if (!$user_id || $user->user_pass === '*DISABLED*') {
				return false;
			}
		} else {
			return false;
		}

		$password = md5($this->temporaryPassword($email));

		try {
			$sql = "UPDATE wp_users SET user_pass = '$password' WHERE ID=$user_id LIMIT 1";
			$this->db->query($sql);
		} catch (Exception $e) {
			throw new Exception("\nCould not update a user password with \n" . $sql . "\n");
		}
		// mail the password setting to the user?
		return true;
	}

	public function countWordpressUsers() {

		$sql = "SELECT COUNT(*) AS c FROM wp_users";
		$record = $this->db->record($sql);

		if ($record && $record->c) {
			return $record->c;
		}
		return 0;
	}

	public function doWordpressUsersExist() {
		return $this->countWordpressUsers() > 0;
	}

	private function getUserImage($drupalUser) {
		$uid = $drupalUser->uid;
		$sql = "SELECT * FROM file_managed fm WHERE fm.uid=$uid
				INNER JOIN file_usage fu ON fu.id=fm.uid
				WHERE type='user'";

		$this->d7->record($sql);
	}

	private function existantUser($user_id, $email = '', $blog_id) {
		$sql = "SELECT COUNT(*) as c FROM wp_users WHERE user_id = $user_id";
		$record = $this->db->record($sql);

		if ($record && $record->c && strlen($email)) {
			$sql = "SELECT ID FROM wp_users WHERE user_email = '$email' ORDER BY ID LIMIT 1";
			$record = $this->db->record($sql);
			$user_id = $record->ID;
			return $user_id;
		} else {
			return FALSE;
		}
	}

	private function userHasBlogCapabilities($blog_id) {

		// checks to see if a user exists in any other capacity than this blog
		$wp_capabilities = sprintf('wp_%d_capabilities', $blog_id);
		$sql = "SELECT COUNT(*) as c FROM wp_usermeta WHERE user_id = $user_id AND meta_key LIKE '$wp_capabilities'";
		$record = $this->db->record($sql);

		$user_in_this_blog = isset($record) && $record->c > 0;
		$sql = "SELECT COUNT(*) as c FROM wp_usermeta WHERE user_id = $user_id AND meta_key LIKE 'wp_%_capabilities'";

		$record = $this->db->record($sql);
		$user_has_other_blogs = isset($record) && $record->c > $user_in_this_blog;

		if ($user_has_other_blogs) {
			return true;
		} else if ($user_in_this_blog) {

		}
	}

	private function updateUserMeta($usermeta, $user_id, $blog_id) {

		$sqlremovefmt	= "DELETE FROM wp_usermeta WHERE user_id=%d AND meta_key='%s'";
		$sqlinsertfmt 	= "INSERT INTO wp_usermeta (user_id, meta_key, meta_value) VALUES (%d, '%s', '%s')";
		$sqlupdatefmt 	= "UPDATE wp_usermeta SET meta_value='%s' WHERE user_id=%d AND meta_key='%s' LIMIT 1";

		if (!is_array($usermeta)) {
			throw new Exception('user meta is not an array?');
		}

//debug($usermeta);

		foreach ($usermeta as $key => $value) {

			if ($blog_id) {
				$key = preg_replace('/%d/', $blog_id, $key);
				$capabilityKey = sprintf('wp_%d_capabilities', $blog_id);
				$roleKey = sprintf('wp_%d_roles', $blog_id);
			} else {
				$capabilityKey = 'wp_capabilities';
				$roleKey = 'wp_roles';
			}
// debug($key);
// debug($value);
			$sqlremove = sprintf($sqlremovefmt, $user_id, $key);
			$sqlinsert = sprintf($sqlinsertfmt, $user_id, $key, $value);
			$sqlupdate = sprintf($sqlupdatefmt, $value, $user_id, $key);

			// usermeta exists?
			$sql = "SELECT COUNT(*) AS c FROM wp_usermeta WHERE user_id=$user_id AND meta_key='$key'";
			$record = $this->db->record($sql);
			$count_usermeta = (integer) $record->c;

//debug("\nFound ".$count_usermeta . ' instances of metadata for '.$user_id . ' key ' . $key);

			// is it a normal update
			if ($count_usermeta === 1) {
				$q = sprintf($sqlupdate, $value, $user_id, $key);
//debug('making update: '.$sqlupdate);
			// if there is more then one usermeta for this key/user, clear them and insert
			} else if ($count_usermeta > 1) {
				$q = sprintf($sqlremove, $user_id, $key);
//debug($q);
				$this->db->query($q);
				$q = sprintf($sqlinsert, $user_id, $key, $value);
			// otherwise, if it aint there, insert it
			} else {
				$q = sprintf($sqlinsert, $user_id, $key, $value);
			}
//debug($q);
			$this->db->query($q);
		}

		// 1. load existing meta for this user
		$sqlfind = sprintf("SELECT * from wp_usermeta WHERE user_id=%d", $user_id);
		$existingUsermeta = $this->db->records($sqlfind);

		if (count((array)$existingUsermeta)) {

			foreach($existingUsermeta as $eUser) {
				if ($eUser->meta_key === $capabilityKey) {
					$max_capability = 4;
					while ($max_capability >= 0 && $eUser->meta_value !== $this->capabilities[$max_capability]) {
						$max_capability--;
					}
					if ($this->capabilities[$max_capability] !== $eUser->meta_value) {
						$sql = sprintf($sqlupdatefmt, $this->capabilities[$max_capability], $user_id, $capabilityKey);
						//debug($sql);
						$this->db->query($sql);
					}
				}
			}
		}
	}

	private function determineCapability($role, $uid) {

		$mapDrupalCapabilities = FALSE;
		$user_level = NULL;

		// initial spec was to assign roles, this has since been changed for ioti 
		// TODO: remove (unless they change their mind...)
		if ($mapDrupalCapabilities && strlen($role)) {

			switch (strtolower($role)) {

				case 'user care':
				case 'administrator':
				case 'associate administrator':
					$capability = $this->capabilities[0];
					$user_level = 10;
					break;

				case 'editor':
				case 'feeds manager':
				case 'production user':
				case 'content manager':
				case 'content moderator':
					$capability = $this->capabilities[1];
					$user_level = 7;
					break;

				case 'author':
					$capability = $this->capabilities[2];
					$user_level = 5;
					break;

				case 'contributor';
					$capability = $this->capabilities[3];
					$user_level = 3;
					break;

				default:
					$capability = $this->capabilities[4];
					$user_level = 1;
					break;
			}
		} else {
			// // admin user
			// if ((integer) $uid === 1) {

			// 	$capability = $this->capabilities[0];
			// 	$user_level = 10;

			// } else {

				$capability = $this->capabilities[4];
				$user_level = 1;
			// }
		}

		return [$capability, $user_level];
	}

	// wordpress UserMeta table
	private function makeUserMeta($drupal_user, $user_id, $blog_id) {

		$wp_user = $this->getWordpressUserById($user_id);

		if (strpos($drupal_user->name, ' ')) {
			list($first_name, $last_name) = explode(' ', $drupal_user->name);
		} else {
			$first_name = '';
			$last_name = $drupal_user->name;
		}

		if (empty($wp_user)) {
			return false;
		}

		$sourceDomain = $this->config->wordpressDomain;

		$uid = $drupal_user->uid;
		if ($uid === 0) {
			return;
		}

		$role = $drupal_user->role;
		//$role_id = $drupal_user->role_id;
		list($capability, $user_level) = $this->determineCapability($role, $uid);


		// TODO: check if drupal_user->uid should be checked here
		if ($blog_id && $drupal_user->uid) {

			// $capabilityKey = sprintf('wp_%d_capabilities', $blog_id);
			// $roleKey = spirntf('wp_%d_roles', $blog_id);

			$usermeta = [
				'nickname' 							=> $wp_user->user_nicename,
				'first_name' 						=> $first_name,
				'last_name' 						=> $last_name,
				'description' 						=> $first_name . ' ' . $last_name,
				'primary_blog' 						=> $blog_id,
				'source_domain' 					=> $sourceDomain,
				'changed_password'					=> true,
				'drupal_migration'					=> date('Y-m-d H:i:s'),
				'wp_%d_user_avatar' 				=> '',
				/* 'wp_%d_role'						=> '', */
				'wp_%d_capabilities'				=> $capability,
				'wp_%d_user_level'					=> $user_level,
				'telecoms_author_meta'				=> 'a:2{s:5:"quote";s:0:"";s:8:"position":s:0:""}',
				// 'googleauthenticator_enabled' 		=> 'disabled',
				// 'googleauthenticator_hidefromuser'	=> 'disabled',
				'show_admin_bar_front'				=> true
				// 'use_ssl'							=> 0,
				// 'admin_color'						=> 'fresh',
				// 'comment_shortcuts'					=> false,
				// 'syntax_highlighting'				=> true,
				// 'rich_editing'						=> true,
				// 'aim' 								=> '',
				// 'yim' 								=> '', 
				// 'jabber' 							=> '',
				// 'locale'							=> '',
				// 'dismissed_wp_pointers'				=> '',
				// 'googleauthenticator_enabled'		=> false,
				// 'googleauthenticator_hidefromuser'	=> false
			];

		}
		// else {

		// 	$usermeta = [
		// 		'nickname' 							=> $wp_user->user_nicename,
		// 		'first_name' 						=> $first_name,
		// 		'last_name' 						=> $last_name,
		// 		'description' 						=> 'imported from drupal',
		// 		'wp_user_avatar' 					=> '',
		// 		'primary_blog' 						=> $blog_id,
		// 		'source_domain' 					=> $sourceDomain,
		// 		/* 'wp_role'							=> '', */
		// 		'wp_capabilities'					=> $capability,
		// 		'wp_user_level'						=> $user_level,
		// 		'telecoms_author_meta'				=> 'a:2{s:5:"quote";s:0:"";s:8:"position":s:0:""}',
		// 		'googleauthenticator_enabled' 		=> 'disabled',
		// 		'googleauthenticator_hidefromuser'	=> 'disabled',
		// 		'show_admin_bar_front'				=> true,
		// 		'use_ssl'							=> 0,
		// 		'admin_color'						=> 'fresh',
		// 		'comment_shortcuts'					=> false,
		// 		'syntax_highlighting'				=> true,
		// 		'rich_editing'						=> true,
		// 		'aim' 								=> '',
		// 		'yim' 								=> '', 
		// 		'jabber' 							=> ''
		// 	];
		// }
		$this->updateUserMeta($usermeta, $user_id, $blog_id);
	}

	private function userMetaExists($user_id, $meta_key) {
		$sql = "SELECT umeta_id from wp_usermeta WHERE user_id = $user_id AND meta_key = '$meta_key'";
		$record = $this->db->record($sql);
		if ($record) {
			return $record->umeta_id;
		}
		return null;
	}

	// private function updateUserMeta($user_id, $meta_key, $meta_value) {

	// 	if (empty($user_id) || empty($meta_key)) {
	// 		throw new Exception("\nUser->getSetUserMeta ERROR: user_id and meta_key must be set.  Invalid parameter user_id = $user_id, meta_key=$meta_key");
	// 	}

	// 	if ($this->userMetaExists($user_id, $meta_key)) {
	// 		$countUserMeta = $this->countUserMeta($user_id, $meta_key);
	// 		if ($countUserMeta > 1) {
	// 			$sql = "DELETE wp_usermeta WHERE meta_key='$meta_key' AND user_id=$user_id";
	// 			$this->db->query($sql);
	// 		}
	// 		$sql = "UPDATE wp_usermeta SET meta_value='$meta_value' WHERE meta_key='$meta_key' AND user_id=$user_id";
	// 	} else {
	// 		$sql = "INSERT INTO wp_usermeta (user_id, meta_key, meta_value) VALUES ($user_id, '$meta_key', '$meta_value')";
	// 	}
	// 	$this->db->query($sql);
	// }

	private function insertOrUpdateUserMeta($user_id, $key, $value) {
		// exists?
		$sql = "SELECT umeta_id  FROM wp_usermeta WHERE meta_key = '$key' AND user_id = $user_id";
//debug($sql);
		$record = $this->db->record($sql);
		if ($record && $record->umeta_id) {
			$umeta_id = $record->umeta_id;
			$sql = "UPDATE wp_usermeta SET meta_value = '$value' WHERE umeta_id = $umeta_id";
		} else {
			$sql = "INSERT INTO wp_usermeta (user_id, meta_key, meta_value) VALUES ($user_id, '$key', $value)";
		}
//debug($sql);
		try {
			$this->db->query($sql);
		} catch (Exception $e) {
			throw new Exception("\nError in query ".$sql. "\n".$e->getMessage());
		}
	}

	public function getDrupalUid($user_id){
		$drupal_uid = $this->drupal_uid_key;
		$sql = "SELECT meta_value AS s FROM wp_usermeta WHERE user_id = $user_id AND meta_key = '$drupal_uid'";
		$record = $this->db->record($sql);
		return $record->s;
	}




// deprecate?
		// // create or update a user's meta
		// public function getOtherUserId($user_id = null, $key, $value = NULL) {
		// 	if (empty($user_id) && empty($value)) {
		// 		throw new Exception("\nERROR: getOtherUserId() requires either a Drupal or Wordpress ID!");
		// 	}
		// 	if ($user_id) {
		// 		$sql = "SELECT meta_value AS s FROM wp_usermeta WHERE user_id = $user_id AND meta_key = 'drupal_uid'";
		// 	} else {
		// 		$sql = "SELECT user_id AS s FROM wp_usermeta WHERE meta_key='drupal_uid' AND meta_value='$value'";
		// 	}
		// 	$record = $this->db->record($sql);
		// 	return $record->s;
		// }
	// private function addUserMeta($drupal_user, $user_id, $blog_id = NULL) {

	// 	$role = $drupal_user->role;

	// 	list($capability, $user_level) = $this->determineCapability($role, $drupal_user->uid);
	// 	$usermeta = [
	// 		'wp_%d_user_avatar' 				=> '',
	// 		/* 'wp_%d_role'						=> '', */
	// 		'wp_%d_capabilities'				=> $capability,
	// 		'wp_%d_user_level'					=> $user_level,
	// 	];
	// 	$this->updateUserMeta($usermeta, $user_id);
	// }

	private function makeUserName($name, $email) {

		$names = explode(' ', $name);

		if (count($names) > 1) {
			$newname = strtolower($names[count($names)-1] . substr($names[0],0,1));
		} else {
			$newname = $email;
		}

		// apostropies in username?
		$newname = preg_replace('/[\']/','',$newname);

		return $newname;
	}

	public function makeWordpressUser($drupalUser, $blog_id) {

		$user_email = $drupalUser->mail;

		if (strlen($user_email) > 4) {
			$user_login = $this->makeUserName($drupalUser->name, $drupalUser->mail);

			$sql = "SELECT COUNT(*) as c FROM wp_users WHERE user_login = '$user_login'";
			$record = $this->db->record($sql);

			if ($record && $record->c) {
				$user_login = $user_email;
			}

			$user_display_name = $user_nicename = addslashes($drupalUser->name);
			$user_nicename = substr($user_nicename, 0, 50);

			// set password to a non-deterministic value (it has to be reset)
			$user_pass = $this->temporaryPassword($drupalUser->mail);

			$user_registered = date('Y-m-d H:i:s', $drupalUser->created);
			$user_status = 0;

			$sql = "SELECT ID as user_id FROM wp_users WHERE user_email='$user_email'";
			$record = $this->db->record($sql);

			if (!$record || !isset($record->user_id)) {

				$sql = "INSERT INTO wp_users (user_login, user_pass, user_nicename, user_email, user_registered, user_status)
						VALUES ('$user_login', '$user_pass', '$user_nicename', '$user_email', '$user_registered', $user_status)";
				$this->db->query($sql);
//debug($sql);
				$user_id = $this->db->lastInsertId();
//debug($user_id);
				$last_name = $first_name = '';
				if (strlen($drupalUser->name) && strpos(' ', $drupalUser->name)) {
					list($first_name, $last_name) = explode(' ', $drupalUser->name);
					$first_name = addslashes($first_name);
				} else {
					$last_name = addslashes($drupalUser->name);
				}

				if (strlen($first_name)) { 
					$sql = "INSERT INTO wp_usermeta (user_id, meta_key, meta_value) VALUES ($user_id, 'first_name', '$first_name')";
					$this->db->query($sql);
				} 
				if (strlen($last_name)) {
					$sql = "INSERT INTO wp_usermeta (user_id, meta_key, meta_value) VALUES ($user_id, 'last_name', '$last_name')";
					$this->db->query($sql);
				}

				if ($this->config->verbose === true) {
					debug("\nWordpress user $user_id created");
				}
			} else {
				$user_id = $record->user_id;

				if ($this->config->verbose === true) {
					debug("\nWordpress user $user_id already exists \n");
				}
			}
			if ($this->config->progress && ($user_id % 100 === 0)) {

				print ".";
			}
			return $user_id;
		}
		return null;
	}

	// TODO: something did not work here on IOTI - 
	public function createWordpressUsers($blog_id) {

		foreach ($this->drupalUsers as $drupal_user) {

			if ($drupal_user->uid > 0) {

				$user = $this->getWordpressUserByEmail($drupal_user->mail);

				if (isset($user) && $user->ID) {

					$user_id = $user->ID;

				} else {

					$user_id = $this->makeWordpressUser($drupal_user, $blog_id);

					if (empty($user_id)) {
						debug("\nDrupal user " . $drupal_user->uid . " was not imported as there is no email address for that Drupal user.");
					}
				}

				$this->makeUserMeta($drupal_user, $user_id, $blog_id);
				$this->insertOrUpdateUserMeta($user_id, $this->drupal_uid_key, $drupal_user->uid);

				if ($this->config->verbose) {
					debug("usermeta created for user $user_id");
				}
			}
		}
	}

	private function addAdminCapabilities($id) {
		$sql = "INSERT INTO wp_usermeta (user_id, meta_key, meta_value) VALUES ($id, 'wp_capabilities', 'a:1:{s:13:\"administrator\";s:1:\"1\";}')";
		$this->db->query($sql);
		$sql = "INSERT INTO wp_usermeta (user_id, meta_key, meta_value) VALUES ($id, 'wp_user_level', '10')";
		$this->db->query($sql);

	}
	public function makeAdminuser() {

		$sql = "SELECT ID, user_login from wp_users where user_login LIKE 'admin%' LIMIT 1";
		$record = $this->db->record($sql);

		if ($record && $record->ID) {
			$username = $record->user_login;
			$id = $record->ID;
			$this->addAdminCapabilities($id);
			print "\nINFO: admin user $username now an administrator";
		} else {
			print "\nWARNING: No admin user candidate found";
		}
	}
}
