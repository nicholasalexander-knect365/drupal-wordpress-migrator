<?php

// drupal users imported as wordpress users
// must be done before post so post_author can be recognised


include_once "DB.class.php";
define('MAX_USERS', 10000);
class User {
	
	public $db;
	public $drupalUsers;

	public function __construct($wp, $d7) {
		
		$this->db = $wp;
		$this->d7 = $d7;

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

		$sql = "SELECT * from wp_users where user_id = $id";
		$record = $this->db->record($sql);

		return $record;
	}

	public function getWordpressUserByEmail($email) {
		
		$sql = "SELECT * from wp_users where user_email = '$email'";
		$record = $this->db->record($sql);

		return $record;
	}
	public function doWordpressUsersExist() {

		$sql = "SELECT COUNT(*) AS c FROM wp_users";
		$record = $this->db->record($sql);

		if ($record && $record->c) {
			return $record->c > 0;
		}
		return false;
	}

	private function makeWordpressUser($drupalUser) {

		$user_email = $drupalUser->mail;
		$user_login = $drupalUser->name;

		$user_pass = md5( $user_email . '--temp');
		$user_display_name = $user_nicename = $drupalUser->name;
		$user_registered = date('Y-m-d H:i:s', $drupalUser->created);
		$user_status = 0;

		$sql = "SELECT ID as id FROM wp_users WHERE user_email='$user_email'";
		$record = $this->db->record($sql);

		if (strlen($user_login) > 0) {
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

	public function createWordpressUsers() {
		foreach ($this->drupalUsers as $duser) {
			$this->makeWordpressUser($duser);
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