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
			$this->config->wordpressPath = '/home/nicholas/Dev/wordpress/' . $this->config->project;

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
				if (preg_match("/'DB_([A-Z_]+)'/", $line, $match)) {
					preg_match("/^define\('DB_[A-Z]+'[\s]*,[\s]*'([0-9A-Za-z\._]+)'\);$/", trim($line), $matched);
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
				print "\n: Wordpress MultiSite loading siteId: ".$this->config->siteId;
			} else if ($this->config->server === 'local') {
				print "\n: Local mode";
			} else if ($this->config->server === 'vm2') {
				print "\n: Alternative VM mode (i.e. Homestead)";
			} else {
				throw new Exception('Not multisite, yet server=local not set.  Please check your configuration!');
			}
		} else if ($this->type === 'd7') {
			if ($once++ === 0) {
				print "\n: Drupal 7 configured database connection.";
			}
		} else {
			throw new Exception($this->type . ' database configuration supported?');
		}
	}

	public static function wptable($type, $siteId = null) {

		if ($siteId === null) {
			$siteId = 'wp_' . static::$wp_prefix;
		} else {
			$siteId = sprintf('wp_%d_', (integer)$siteId);
		}
		switch($type) {
			case 'postmeta':
			case 'posts':
			case 'termmeta':
			case 'terms':
			case 'term_relationships':
			case 'term_taxonomy':
			case 'users':
			case 'usermeta':
				return $siteId . $type;
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

	/**
	 * Helper function for decode_entities_full()
	 *
	 * This contains the full HTML 4 Recommendation listing of entities, so the default to discard
	 * entities not in the table is generally good. Pass false to the second argument to return
	 * the faulty entity unmodified, if you're ill or something.
	 * Per: http://www.lazycat.org/software/html_entity_decode_full.phps
	 */
	private function convert_entity($matches, $destroy = true) {

		static $table = array('quot' => '&#34;','amp' => '&#38;','lt' => '&#60;','gt' => '&#62;','OElig' => '&#338;','oelig' => '&#339;','Scaron' => '&#352;','scaron' => '&#353;','Yuml' => '&#376;','circ' => '&#710;','tilde' => '&#732;','ensp' => '&#8194;','emsp' => '&#8195;','thinsp' => '&#8201;','zwnj' => '&#8204;','zwj' => '&#8205;','lrm' => '&#8206;','rlm' => '&#8207;','ndash' => '&#8211;','mdash' => '&#8212;','lsquo' => '&#8216;','rsquo' => '&#8217;','sbquo' => '&#8218;','ldquo' => '&#8220;','rdquo' => '&#8221;','bdquo' => '&#8222;','dagger' => '&#8224;','Dagger' => '&#8225;','permil' => '&#8240;','lsaquo' => '&#8249;','rsaquo' => '&#8250;','euro' => '&#8364;','fnof' => '&#402;','Alpha' => '&#913;','Beta' => '&#914;','Gamma' => '&#915;','Delta' => '&#916;','Epsilon' => '&#917;','Zeta' => '&#918;','Eta' => '&#919;','Theta' => '&#920;','Iota' => '&#921;','Kappa' => '&#922;','Lambda' => '&#923;','Mu' => '&#924;','Nu' => '&#925;','Xi' => '&#926;','Omicron' => '&#927;','Pi' => '&#928;','Rho' => '&#929;','Sigma' => '&#931;','Tau' => '&#932;','Upsilon' => '&#933;','Phi' => '&#934;','Chi' => '&#935;','Psi' => '&#936;','Omega' => '&#937;','alpha' => '&#945;','beta' => '&#946;','gamma' => '&#947;','delta' => '&#948;','epsilon' => '&#949;','zeta' => '&#950;','eta' => '&#951;','theta' => '&#952;','iota' => '&#953;','kappa' => '&#954;','lambda' => '&#955;','mu' => '&#956;','nu' => '&#957;','xi' => '&#958;','omicron' => '&#959;','pi' => '&#960;','rho' => '&#961;','sigmaf' => '&#962;','sigma' => '&#963;','tau' => '&#964;','upsilon' => '&#965;','phi' => '&#966;','chi' => '&#967;','psi' => '&#968;','omega' => '&#969;','thetasym' => '&#977;','upsih' => '&#978;','piv' => '&#982;','bull' => '&#8226;','hellip' => '&#8230;','prime' => '&#8242;','Prime' => '&#8243;','oline' => '&#8254;','frasl' => '&#8260;','weierp' => '&#8472;','image' => '&#8465;','real' => '&#8476;','trade' => '&#8482;','alefsym' => '&#8501;','larr' => '&#8592;','uarr' => '&#8593;','rarr' => '&#8594;','darr' => '&#8595;','harr' => '&#8596;','crarr' => '&#8629;','lArr' => '&#8656;','uArr' => '&#8657;','rArr' => '&#8658;','dArr' => '&#8659;','hArr' => '&#8660;','forall' => '&#8704;','part' => '&#8706;','exist' => '&#8707;','empty' => '&#8709;','nabla' => '&#8711;','isin' => '&#8712;','notin' => '&#8713;','ni' => '&#8715;','prod' => '&#8719;','sum' => '&#8721;','minus' => '&#8722;','lowast' => '&#8727;','radic' => '&#8730;','prop' => '&#8733;','infin' => '&#8734;','ang' => '&#8736;','and' => '&#8743;','or' => '&#8744;','cap' => '&#8745;','cup' => '&#8746;','int' => '&#8747;','there4' => '&#8756;','sim' => '&#8764;','cong' => '&#8773;','asymp' => '&#8776;','ne' => '&#8800;','equiv' => '&#8801;','le' => '&#8804;','ge' => '&#8805;','sub' => '&#8834;','sup' => '&#8835;','nsub' => '&#8836;','sube' => '&#8838;','supe' => '&#8839;','oplus' => '&#8853;','otimes' => '&#8855;','perp' => '&#8869;','sdot' => '&#8901;','lceil' => '&#8968;','rceil' => '&#8969;','lfloor' => '&#8970;','rfloor' => '&#8971;','lang' => '&#9001;','rang' => '&#9002;','loz' => '&#9674;','spades' => '&#9824;','clubs' => '&#9827;','hearts' => '&#9829;','diams' => '&#9830;','nbsp' => '&#160;','iexcl' => '&#161;','cent' => '&#162;','pound' => '&#163;','curren' => '&#164;','yen' => '&#165;','brvbar' => '&#166;','sect' => '&#167;','uml' => '&#168;','copy' => '&#169;','ordf' => '&#170;','laquo' => '&#171;','not' => '&#172;','shy' => '&#173;','reg' => '&#174;','macr' => '&#175;','deg' => '&#176;','plusmn' => '&#177;','sup2' => '&#178;','sup3' => '&#179;','acute' => '&#180;','micro' => '&#181;','para' => '&#182;','middot' => '&#183;','cedil' => '&#184;','sup1' => '&#185;','ordm' => '&#186;','raquo' => '&#187;','frac14' => '&#188;','frac12' => '&#189;','frac34' => '&#190;','iquest' => '&#191;','Agrave' => '&#192;','Aacute' => '&#193;','Acirc' => '&#194;','Atilde' => '&#195;','Auml' => '&#196;','Aring' => '&#197;','AElig' => '&#198;','Ccedil' => '&#199;','Egrave' => '&#200;','Eacute' => '&#201;','Ecirc' => '&#202;','Euml' => '&#203;','Igrave' => '&#204;','Iacute' => '&#205;','Icirc' => '&#206;','Iuml' => '&#207;','ETH' => '&#208;','Ntilde' => '&#209;','Ograve' => '&#210;','Oacute' => '&#211;','Ocirc' => '&#212;','Otilde' => '&#213;','Ouml' => '&#214;','times' => '&#215;','Oslash' => '&#216;','Ugrave' => '&#217;','Uacute' => '&#218;','Ucirc' => '&#219;','Uuml' => '&#220;','Yacute' => '&#221;','THORN' => '&#222;','szlig' => '&#223;','agrave' => '&#224;','aacute' => '&#225;','acirc' => '&#226;','atilde' => '&#227;','auml' => '&#228;','aring' => '&#229;','aelig' => '&#230;','ccedil' => '&#231;','egrave' => '&#232;','eacute' => '&#233;','ecirc' => '&#234;','euml' => '&#235;','igrave' => '&#236;','iacute' => '&#237;','icirc' => '&#238;','iuml' => '&#239;','eth' => '&#240;','ntilde' => '&#241;','ograve' => '&#242;','oacute' => '&#243;','ocirc' => '&#244;','otilde' => '&#245;','ouml' => '&#246;','divide' => '&#247;','oslash' => '&#248;','ugrave' => '&#249;','uacute' => '&#250;','ucirc' => '&#251;','uuml' => '&#252;','yacute' => '&#253;','thorn' => '&#254;','yuml' => '&#255;');

		if (isset($table[$matches[1]])) return $table[$matches[1]];

		return $destroy ? '' : $matches[0];
	}

	/**
	 * Helper function for drupal_html_to_text().
	 *
	 * Calls helper function for HTML 4 entity decoding.
	 * Per: http://www.lazycat.org/software/html_entity_decode_full.phps
	 */
	private function decode_entities_full($string, $quotes = ENT_COMPAT, $charset = 'ISO-8859-1') {
		$that = $this;
	  	return html_entity_decode(preg_replace_callback('/&([a-zA-Z][a-zA-Z0-9]+);/', array($this,'convert_entity'), $string), $quotes, $charset); 
	}

	protected function prepare($str) {

		$str = $this->decode_entities_full($str);
		$str = str_replace(array("\r\n", "\r", "\n"), '', $str);
		$str = preg_replace('/&rdquo;/', '&quot;', $str);
		$str = preg_replace('/&rsquo;/', '&apos;', $str);
		$str = preg_replace('/&ldquo;/', '&quot;', $str);
		$str = preg_replace('/&lsquo;/', '&apos;', $str);
		$str = preg_replace('/&ndash;/', '-', $str);
		$str = preg_replace('/&mdash;/', '--', $str);
		$str = preg_replace('/\'/', '&apos;', $str);
		$str = preg_replace('/\"/', '&quot;', $str);
        // $str = $this->convert_smart_quotes($str);
        // $str = html_entity_decode($str);
		return $str;
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
			debug('WARNING: record query returned more rows than the expected single row: ' . $sql);
			$numRows = $this->query($sql . ' LIMIT 1');
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
