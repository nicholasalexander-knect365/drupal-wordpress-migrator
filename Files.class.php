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
	public $s3Bucket = 'http://pentontuautodrupalfs.s3.amazonaws.com';


	public function __construct(DB $connection, $s3 = '', $args = []) {
		$this->connection = $connection;
		$this->nid = null;
		if (strlen($s3)) {
			$this->s3Bucket = $s3;
		}
		$this->type = 'node';
		if ($args['verbose']) {
			$this->verbose = true;
		}
		if ($args['quiet']) {
			$this->verbose = false;
		}
		if ($args['progress']) {
			$this->verbose = '.';
		}
	}

	public function setDrupalPath($path) {
		$this->drupalPath = $path;
	}

	public function isVerbose() {
		return $this->verbose;
	}

	public function getFiles($nid) {

		set_error_handler(function($errno, $errstr, $errfile, $errline, array $errcontext) {
			// error was suppressed with the @-operator
			if (0 === error_reporting()) {
				return false;
			}
			// print 'ERROR!!!' . $errno;
			if ($errno === 2) {
				return '404';
			}
			throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
		});

		try {
	
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
							if ($this->verbose === true) {
								print "\nCopying image ".$path;
							}
							if (file_exists($path)) {	
								if (!copy($path, 'images/'.$file->filename)) {
									throw new Exception( 'could not copy '.$path);
								}
							} else {
								print "\n$path does not exist";
							}
							break;
						case 's3':
							$path = $this->s3Bucket . '/' . $file->filename;
							try {
								$fileData = file_get_contents($path);
								if (strlen($fileData) > 14) {
									if (is_string($this->verbose)) {
										print $this->verbose;
									} else if ($this->verbose === true) {
										print "\nImage data size: " . strlen($fileData);
									}
									$fd = fopen('images/' . $file->filename, 'w+');
									fputs($fd, $fileData);
									fclose($fd);							
								}
								if ($this->verbose === true) {
									print "\nGetting s3 image ".$path;
								}
							} catch (Exception $e) {
								die("could not get file ".$e->getMessage());
							}
							break;
						default: 
							print "\nDo not know how to get this image " . $path;
					}
				}
			}
		} catch (ErrorException $e) {
			print("did not get file ".$e->getMessage());
		}

		return $files;
	}

	private function source($uri) {
		if (preg_match('/^s3:\/\//', $uri)) {
			if ($this->verbose === true) {
				print "\n$uri - file has s3: protocol - attempting to get file from ";
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