<?php


/* drupal has three tables for files: 
   files: not used in Project 1
   file_usage: relates the fid to the (object, ie node) id
   file_managed: filename and uri 
*/

require_once "DB.class.php";

class Files {
	public $nid;
	public $type;
	public $connection;
	public $drupalPath;
	private $verbose = false;

	public function __construct(DB $connection, $args = []) {
		$this->connection = $connection;
		$this->nid = null;
		$this->type = 'node';
		if ($args['verbose']) {
			$this->verbose = true;
		}
		if ($args['quiet']) {
			$this->verbose = false;
		}
	}

	public function setDrupalPath($path) {
		$this->drupalPath = $path;
	}

	public function isVerbose() {
		return $this->verbose;
	}

	public function getFiles($nid) {
		$sql = "SELECT fu.fid, fu.module, fu.type, fu.id, fu.count, fm.uid, fm.filename as filename, fm.uri as uri, fm.filesize, fm.status, fm.timestamp 
				FROM file_managed fm
				JOIN file_usage fu ON fm.fid=fu.fid
				WHERE fu.id=$nid AND fu.type='node'";

		$files = $this->connection->records($sql);
		if ($files) {

			foreach ($files as $file) {
				switch ($this->source($file->uri)) {
					case 'public' :
						$path = $this->drupalPath . '/sites/default/files/' . $file->filename;
						if ($this->verbose) {
							print "\nCopying path ".$path;
						}
						if (file_exists($path)) {	
							if (!copy($path, 'images/'.$file->filename)) {
								throw new Exception( 'could not copy '.$path);
							}
						} else {
							print "\n$path does not exist";
						}

						break;
				}
			}
		}

		return $files;
	}

	private function source($uri) {
		if (preg_match('/^s3:\/\//', $uri)) {
			if ($this->verbose) {
				print "\n$uri - file has s3: protocol - implementation required.";
			}
			return 's3';
		}
		if (preg_match('/^http:\/\//', $uri)) {
			die('file has http: protocol - check if implementation required.');
			return 'http';
		}
		if (preg_match('/^public:\/\//', $uri)) {
			return 'public';
		}
	}

	/* 
	 * best version is the largest version
	 *
	 * ... may not be required as all larger images appear to be:
	 * DRUPAL_HOME . '/sites/default/files'
	 */
	public function getBestVersion($filename) {
		$sql = "SELECT fu.fid, fu.module, fu.type, fu.id, fu.count, fm.uid, fm.filename as filename, fm.uri as uri, fm.filesize, fm.status, fm.timestamp 
				FROM file_managed fm
				JOIN file_usage fu ON fm.fid=fu.fid
				WHERE fm.filename like '$filename'";

		$images = $this->connection->records($sql);

		if (count($images) === 1) {
			return $images[0];
		}
		if (count($images) === 0) {
			print str_replace(["\n", "  ", "\t"],["", " ", ' '], $sql) . "\n";
			throw new Exception('getBestImage should never find no image?');
		}
		$best = null;
		foreach ($images as $image) {
			if (!$best || $image->filesize > $best->filesize) {
				$best = $image;
			}
		}
		return $best;
	}

}