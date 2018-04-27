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
		
		$wp_users = DB::wptable('users');

		$sql = "SELECT COUNT(*) AS c FROM $wp_users";

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
		
		$wp_users = DB::wptable('users');

		$sql = "SELECT * from $wp_users where user_id = $id";
		$record = $this->db->record($sql);

		return $record;
	}

	public function getWordpressUserByEmail($email) {
		
		$wp_users = DB::wptable('users');

		$sql = "SELECT * from $wp_users where user_email = '$email'";
		$record = $this->db->record($sql);

		return $record;
	}
	public function doWordpressUsersExist() {
		
		$wp_users = DB::wptable('users');

		$sql = "SELECT COUNT(*) AS c FROM $wp_users";
		$record = $this->db->record($sql);

		if ($record && $record->c) {
			return $record->c > 0;
		}
		return false;
	}

	private function makeWordpressUser($drupalUser) {
		
		$wp_users = DB::wptable('users');

		$user_email = $drupalUser->mail;
		$user_login = $drupalUser->name;

		$user_pass = md5( $user_email . '-imported');
		$user_display_name = $user_nicename = $drupalUser->name;
		$user_registered = date('Y-m-d H:i:s', $drupalUser->created);
		$user_status = 0;

		$sql = "SELECT ID as id FROM $wp_users WHERE user_email='$user_email'";
		$record = $this->db->record($sql);

		if (!$record || !isset($record->id)) {
			$sql = "INSERT INTO $wp_users (user_login, user_pass, user_nicename, user_email, user_registered, user_status)
					VALUES ('$user_login', '$user_pass', '$user_nicename', '$user_email', '$user_registered', $user_status)";
			$this->db->query($sql);
			$user_id = $this->db->lastInsertId();
		} else {
			$user_id = $record->id;
		}
		return $user_id;
	}

	public function createWordpressUsers() {
		foreach ($this->drupalUsers as $duser) {
			$this->makeWordpressUser($duser);
		}
	}
}