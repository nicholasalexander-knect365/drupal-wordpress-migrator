<?php
/* 
	Files module: reading and writing from the file sysstem

	Support for image files 
*/

require_once "DB.class.php";

class Files {

	public $nid;
	public $type;
	public $connection;
	public $drupalPath;
	public $imageStore;
	private $verbose = false;
	public $s3Bucket = 'http://pentontuautodrupalfs.s3.amazonaws.com';


	public function __construct(DB $connection, $s3 = '', $args = []) {

		$this->connection = $connection;
		$this->nid = null;
		if (strlen($s3)) {
			$this->s3Bucket = $s3;
		}
		$this->type = 'node';

		$this->verbose = isset($args['verbose']);
		$this->verbose = isset($args['quiet']);
		if ($args['progress']) {
			$this->verbose = '.';
		}
	}

	public function setDrupalPath($path) {

		$this->drupalPath = $path;
	}

	private function dirEmpty($dir) {

		if (!is_readable($dir)) {
			die("\nERROR: image store directory $dir does not exist or is not writable\n\n");
		}
  		return (count(scandir($dir)) == 2);
	}

	public function isVerbose() {

		return $this->verbose;
	}

	public function setImageStore($path) {

		if ($this->dirEmpty($path)) {
			$this->imageStore = $path;
		} else {
			die("\nERROR: images directory is not empty, please use --imageStore=EMPTY_DIRECTORY\n\n");
		}
	}

	private function fileList($nid) {

		$sql = "SELECT fu.fid, fu.module, fu.type, fu.id, fu.count, fm.uid, fm.filename as filename, fm.uri as uri, fm.filesize, fm.status, fm.timestamp 
				FROM file_managed fm
				JOIN file_usage fu ON fm.fid=fu.fid
				WHERE fu.id=$nid AND fu.type='node'";

		$files = $this->connection->records($sql);

		return $files;
	} 


	private function source($uri) {
		
		if (preg_match('/^([\w]+):\/\//', $uri, $matched)) {
			return $matched[1];
		} else {
			throw new Exception('Unsupported image link: ' . $uri);
		}
	}


	public function getFiles($nid) {

		set_error_handler(function($errno, $errstr, $errfile, $errline, array $errcontext) {
			// error was suppressed with the @-operator
			if (0 === error_reporting()) {
				return false;
			}
			if ($errno === 2) {
				return '404';
			}
			throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
		});

		try {
			
			if ($files = $this->fileList($nid)) {

				foreach ($files as $file) {

					$fileType = $this->source($file->uri); 
					
					switch ($fileType) {

						case 'public' :
							$path = $this->drupalPath . '/sites/default/files/' . $file->filename;
							if ($this->verbose === true) {
								print "\nCopying image ".$path;
							}
							if (file_exists($path)) {	
								if (!copy($path, $this->imageStore . '/' . $file->filename)) {
									throw new Exception('could not copy ' . $path);
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
									$fd = fopen($this->imageStore . '/' . $file->filename, 'w+');
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
							print "\nDo not know how to get this image " . $path . " type is " . $fileType;
					}
				}
			}
		} catch (ErrorException $e) {
			print("did not get file ".$e->getMessage());
		}

		return $files;
	}

	/* 
	 * TODO: Deprecate this?? - there may be multiple versions but best version appears to be the base path
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