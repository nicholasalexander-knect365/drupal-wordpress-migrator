<?php

// drupal users imported as wordpress users
// must be done before post so post_author can be recognised


include_once "DB.class.php";
define('MAX_USERS', 10000);

class User {
	
	public $db;
	public $drupalUsers;
	public $config;

	public function __construct($wp, $d7, $config) {
		
		$this->db = $wp;
		$this->d7 = $d7;
		$this->config = $config;
		$this->drupalUsers = [];
	}

	public function getDrupalUsers() {
		$sql = "SELECT u.uid, u.name, u.mail, u.signature, u.timezone, u.language, u.created, r.name as role
			FROM users u
			LEFT JOIN users_roles ur ON ur.uid = u.uid
			LEFT JOIN role r on r.rid = ur.rid";
		$this->drupalUsers = $this->d7->records($sql);

		if (count($this->drupalUsers) > MAX_USERS) {
			debug('Warning: there are more than ' . MAX_USERS);
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

	// public function getDrupalUserByUid($id) {
	// 	$sql = "SELECT * FROM users u WHERE u.uid = $id";
	// 	$record = $this->d7->record($sql);
	// 	return $record;
	// }

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
			$sql = "SELECT * from wp_users where user_email = '$email'";
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

	public function doWordpressUsersExist() {

		$sql = "SELECT COUNT(*) AS c FROM wp_users";
		$record = $this->db->record($sql);

		if ($record && $record->c) {
			return $record->c > 0;
		}
		return false;
	}


	private function getUserImage($drupalUser) {
		$uid = $drupalUser->uid;
		$sql = "SELECT * FROM file_managed fm WHERE fm.uid=$uid
				INNER JOIN file_usage fu ON fu.id=fm.uid
				WHERE type='user'";

		$this->d7->record($sql);
	}

	// wordpress UserMeta table
	private function makeUserMeta($drupal_user, $user_id, $blog_id = NULL) {

		$wp_user = $this->getWordpressUserById($user_id);

		if (strpos(' ', $drupal_user->name)) {
			list($first_name, $last_name) = explode(' ', $drupal_user->name);
		} else {
			$first_name = '';
			$last_name = $drupal_user->name;
		}

		if (empty($wp_user)) {
			return false;
		}

		$sourceDomain = $this->config->wordpressDomain;

		if ($blog_id) {
			$usermeta = [
					'nickname' 							=> $wp_user->user_nicename,
					'first_name' 						=> $first_name,
					'last_name' 						=> $last_name,
					'description' 						=> 'imported from drupal',
					'wp_%d_user_avatar' 				=> '',
					'primary_blog' 						=> $blog_id,
					'source_domain' 					=> $sourceDomain,
					'wp_%d_capabilities'				=> 'a:1:{s:6:"editor";b:1;}',
					'wp_%d_user_level'					=> 7,
					'telecoms_author_meta'				=> 'a:2{s:5:"quote";s:0:"";s:8:"position":s:0:""}',
					'googleauthenticator_enabled' 		=> 'disabled',
					'googleauthenticator_hidefromuser'	=> 'disabled',
					'show_admin_bar_front'				=> true,
					'use_ssl'							=> 0,
					'admin_color'						=> 'fresh',
					'comment_shortcuts'					=> false,
					'syntax_highlighting'				=> true,
					'rich_editing'						=> true,
					'aim' 								=> '',
					'yim' 								=> '', 
					'jabber' 							=> ''
			];
		} else {
			$usermeta = [
					'nickname' 							=> $wp_user->user_nicename,
					'first_name' 						=> $first_name,
					'last_name' 						=> $last_name,
					'description' 						=> 'imported from drupal',
					'wp_user_avatar' 					=> '',
					'primary_blog' 						=> $blog_id,
					'source_domain' 					=> $sourceDomain,
					'wp_capabilities'					=> 'a:1:{s:6:"editor";b:1;}',
					'wp_user_level'						=> 7,
					'telecoms_author_meta'				=> 'a:2{s:5:"quote";s:0:"";s:8:"position":s:0:""}',
					'googleauthenticator_enabled' 		=> 'disabled',
					'googleauthenticator_hidefromuser'	=> 'disabled',
					'show_admin_bar_front'				=> true,
					'use_ssl'							=> 0,
					'admin_color'						=> 'fresh',
					'comment_shortcuts'					=> false,
					'syntax_highlighting'				=> true,
					'rich_editing'						=> true,
					'aim' 								=> '',
					'yim' 								=> '', 
					'jabber' 							=> ''
			];
		}

		$clearUserMeta = true;
		$sqlremove = "DELETE FROM wp_usermeta WHERE user_id=%d AND meta_key='%s'";

		$sqlinsertfmt = "INSERT INTO wp_usermeta (user_id, meta_key, meta_value) VALUES (%d, '%s', '%s')";
		$sqlupdatefmt = "UPDATE wp_usermeta SET meta_value='%s' WHERE user_id=%d AND meta_key='%s' LIMIT 1";

		
		foreach ($usermeta as $key => $value) {

			if ($blog_id) {
				$key = preg_replace('/%d/', $blog_id, $key);
			}

			if ($clearUserMeta) {
				$q = sprintf($sqlremove, $user_id, $key);
				$this->db->query($q);
			}

			// usermeta exists?
			$sql = "SELECT * FROM wp_usermeta WHERE user_id=$user_id AND meta_key=$key";
			$usermeta = $this->db->record($sql);
			if (count((array) $usermeta)) {
				$q = sprintf($sqlupdatefmt, $user_id, $key, $value);
			} else {
				$q = sprintf($sqlinsertfmt, $user_id, $key, $value);
			}
			$this->db->query($q);
debug($q);
		}
	}

	private function makeWordpressUser($drupalUser) {

		$user_email = $drupalUser->mail;

		if (strlen($user_email) > 4) {
			$user_login = $drupalUser->name;
			$user_display_name = $user_nicename = $drupalUser->name;

			// set password to a non-deterministic value (it has to be reset)
			$user_pass = $this->temporaryPassword($drupalUser->mail);

			$user_registered = date('Y-m-d H:i:s', $drupalUser->created);
			$user_status = 0;

			$sql = "SELECT ID as id FROM wp_users WHERE user_email='$user_email'";
			$record = $this->db->record($sql);

			if (!$record || !isset($record->id)) {
				$sql = "INSERT INTO wp_users (user_login, user_pass, user_nicename, user_email, user_registered, user_status)
						VALUES ('$user_login', '$user_pass', '$user_nicename', '$user_email', '$user_registered', $user_status)";
				$this->db->query($sql);
				$user_id = $this->db->lastInsertId();
			} else {
				$user_id = $record->id;
			}
			return $user_id;
		}
		return null;
	}

	public function createWordpressUsers($blog_id = null) {

		foreach ($this->drupalUsers as $duser) {

			if ($duser->uid > 0) {

				$user_id = $this->makeWordpressUser($duser);

				if (empty($user_id)) {
					debug("\nDrupal user " . $duser->uid . " was not imported as there is no email address for that Drupal user.");
				}
				if ($this->makeUserMeta($duser, $user_id, $blog_id)) {
					debug("usermeta created for $duser");
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
