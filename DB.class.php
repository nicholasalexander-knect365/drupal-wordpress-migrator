<?php

class DB {

	public static $wp_prefix;

	public $db;
	public $wp;
	public $d7;

	private $config;
	private $server;
	private $type; 			// wp or d7 expected
	private $host;
	private $credentials;
	private $connection;
	private $result;
	private $rows;

	public function __construct($server = 'local', $type, $config) {
		$this->server = $server;
		$this->type = $type;
		$this->config = $config;
	}

	private function connector($type = '') {
		if (!$type) {
			throw new Exception('Programming error: to connect to a Database, please use a type (wp or d7).');
		}
		$this->type = $type;

		switch ($this->type) {
			case 'wp' :
				$credentials = $this->credentials['wp'];
				break;
			case 'd7' :
				$credentials = $this->credentials['d7'];
				break;
			default:
				die('Programming error: connection type ' . $type . ' has not been defined.');
		}


//debug($credentials);
		$this->connection = new mysqli(
			$credentials['host'],
			$credentials['username'],
			$credentials['password'],
			$credentials['database']);

		if ($this->connection->connect_error) {
			// debug($this->connection);
			// debug($this->type);
			throw new Exception("\nConnection failed: " . $this->type . ' ' . $this->connection->connect_error . "\n");
		}

		return $this->connection;
	}

	private function wpConfig() {

		if ($this->config->server === 'local' && empty($this->config->wordpressPath)) {
			$this->config->wordpressPath = '~/Dev/wordpress/' . $this->config->project;

		} else if ($this->config->server === 'vm' && empty($this->config->wordpressPath)) {
			$this->config->wordpressPath = '/var/www/public';

		} else if ($this->config->server === 'vm2' && empty($this->config->wordpressPath)) {
			$this->config->wordpressPath = '/home/vagrant/Code/client/k365/wp';

		} else if ($this->config->server === 'staging' && empty($this->config->wordpressPath)) {
			$this->config->wordpressPath = '/srv/www/public';
			//throw new Exception("ERROR: this server requires a --wordpressPath setting!");
		}

		$wp_config = $this->config->wordpressPath . '/wp-config.php';

		$this->credentials['wp'] = [];

		if (file_exists($wp_config)) {

			$fd = fopen($wp_config, 'r');
			if (empty($fd)) {
				throw new Exception('can not read wp-config: ' . $wp_config);
			}
			while($line = fgets($fd, 4096)) {
				if (preg_match("/'DB_([A-Z]+)'/", $line, $match)) {
					preg_match("/^define\('DB_[A-Z]+'[\s]*,[\s]*'([0-9A-Za-z\.]+)'\);$/", trim($line), $matched);
					if ($matched && count($matched)) {
						if ($match[1] === 'NAME') {
							$this->credentials['wp']['database'] = $matched[1];
						}
						if ($match[1] === 'USER') {

							$this->credentials['wp']['username'] = $matched[1];
						}
						if ($match[1] === 'PASSWORD') {
							$this->credentials['wp']['password'] = $matched[1];
						}
						if ($match[1] === 'HOST') {
							$this->credentials['wp']['host'] = $matched[1];
						}
					// } else {
					// 	debug($line);
					}
				}
			}
			//debug($this->credentials);
		} else {

			throw new Exception('wp-config does not exist PATH: ' . $wp_config . "\n");
		}
	}

	private function wpMultiSiteConfig($project) {

		// the project's domain name exists in wp_domain_mapping on the staging site,
		// but not necessarily on the vm site (and not on a local)
		// using wp_blogs as a test for local/multisite
		$sql = "SELECT * FROM wp_blogs WHERE domain LIKE '%$project%' LIMIT 1";
		$record = $this->record($sql);

		if (isset($record) && !empty($record) && count($record) === 1) {
			$blog_id = $record->blog_id;
		} else {
			if ($this->config->server === 'local' || $this->config->server === 'vm2') {
				static::$wp_prefix = 'wp_';
				return;
			} else {
				throw new Exception("ERROR: can not find $project in multisite configuration");
			}
		}
		static::$wp_prefix = sprintf('wp_%d_', $blog_id);

	}

	public function configure($config = null) {

		static $once = 0;

		$this->config = $config;
		if ($this->config->verbose) {
			print "\n" . ucfirst($this->config->server) . ' : ' . $this->type . ' connect request... for project '.$this->config->project;
		}

//dd($this->config);

		switch ($this->config->project) {
			case 'tuauto':
				if ($this->config->server === 'staging') {
					$this->credentials['d7'] = [
						'database' => 'd7telematics',
						'username' => 'd7telematics',
						'password' => '9FbTCPWWTQi2',
						'host' => 'mysql'
					];
				} else {
					$this->credentials['d7'] = [
						'database' => 'd7telematics',
						'username' => 'd7telematics',
						'password' => 'zMn5LdPej2pbgqWqEjwmFZ7Y',
						'host' => 'localhost'
					];
				}
				break;
			default: 
				throw new Exception('Have to know which site you are migrating with --project setting');
		}

		$this->wpConfig();

		$this->db = $this->connector($this->type);
		if ($this->db && $this->config->verbose) {
			print "connected.";
		}
		
		if ($this->type === 'wp') {

			$this->wpMultiSiteConfig($this->config->project);

			$sql = "SHOW TABLES like 'wp_blogs'";
			$record = $this->record($sql);

			if ($record && count($record) && ($this->config->wordpressPath === '/var/www/public' || $this->config->wordpressPath === '/srv/www/test1.telecoms.com')) {
				if ($this->config->server === 'local') {
					throw new Exception('CHECK FOR CONFIG ERROR: local server is not usually multisite.  If you are running on another server, please specify it with a --server=[vm,staging,live] directive');
				}
				print "\nWordpress MultiSite loading siteId: ".$this->config->siteId;
			} else if ($this->config->server === 'local' || $this->config->server === 'vm2') {
				print "\nWordpress local loading data";
			} else {
				throw new Exception('Not multisite, yet server=local not set.  Please check your configuration!');
			}
		} else if ($this->type === 'd7') {
			if ($once++ === 0) {
				print "\nDrupal 7 configured database connection.";
			}
		} else {
			throw new Exception($this->type . ' database configuration supported?');
		}
	}

	public static function wptable($type) {
		switch($type) {
			case 'postmeta':
			case 'posts':
			case 'termmeta':
			case 'terms':
			case 'term_relationships':
			case 'term_taxonomy':
			case 'users':
			case 'usermeta':
				return static::$wp_prefix . $type;
			default:
				die('unknown table type for wordpress : '.$type);
		}
	}

	public function close() {
		$this->connection->close();
	}

	public function getConnection() {
		return $this->connection;
	}

	public function query($sql) {

		$rowCount = 0;

		if ($this->config->sqlDebug) {
			debug("\n".$this->type . ': ' .static::strip($sql) . "\n");
		}

		if (empty($sql)) {
			throw new Exception('DB::query ... call with empty string?');
		}
		if (empty($this->connection)) {
			throw new Exception('DB::query ... no connection?');
		}

		try {
			$result = $this->connection->query($sql);
		} catch (Exception $e) {
			print "\nQuery failed! $sql \n";
			die($e->getMessage());
		}

		if ($result) {

			$this->result = $result;
			$rowCount = $this->connection->affected_rows;

			// if the rowCount < 1 
			// and it is a DELETE of an empty table
			// the assert will be a problem...
			// if (!strncmp('DELETE FROM', $sql, 11)) {
			// 	assert($rowCount > 0);
			// }

		} else {

			return false;
		}

		return $rowCount;
	}

	/* for low level calls such as show tables, 
	   do not populate $this->rows 
	   and each row returned is an array element
	*/
	public function rows($sql) {

		$numRows = $this->query($sql);
		$rows = [];
		if ($numRows) {
			for ($c = 0; $c< $numRows; $c++) {
				$rowSet = $this->result->fetch_row();
				while ($row = array_pop($rowSet)) {
					$rows[] = $row;
				}
			}
			return $rows;
		} else {
			return NULL;
		}
	}

	private function getObjects() {
		$this->rows = [];
		while ( $row = $this->result->fetch_object()) {
			$this->rows[] = $row;
		}
	}

	public function record($sql) {

		$numRows = $this->query($sql);

		if ($numRows > 1) {
			throw new Exception('record query returned more rows than the expected single row: ' . $sql);
		}
		if ($numRows) {
			$record = $this->getRecord();
			return $record;
		} else {
			return null;
		}
	} 

	public function records($sql) {
		$numRows = $this->query($sql);
		if ($numRows) {
			return $this->getRecords();
		} else {
			return NULL;
		}
	}

	public function getRecord() {

		if ($this->result) {

			$row = $this->result->fetch_object();
			return $row;
		} else {

			throw new Exception('DB::getRecord() but no result variable?');

		}
	}

	public function getRecords() {
		if ($this->result) {
			$this->getObjects();
			return $this->rows;
		} else {
			throw new Exception('send a query before getting Rows!');
		}
	}

	public function lastInsertId() {
		return $this->connection->insert_id;
	}

	static public function strip($sql, $crlf = false) {
		$sql = str_replace(["\n", "\t", "  "],["", " ", " "], $sql);
		if ($crlf) {
			$sql = "\n" . $sql . "\n";
		}
		return $sql;
	}
}
