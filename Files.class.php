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
	public $imagesDestination;
	private $verbose = false;
	public $s3Bucket = 'http://pentontuautodrupalfs.s3.amazonaws.com';


	public function __construct(DB $connection, $s3 = '', $args = []) {

		$this->connection = $connection;
		$this->nid = null;
		if (strlen($s3)) {
			$this->s3Bucket = $s3;
		}
		$this->type = 'node';

		$this->verbose = isset($args['verbose']) ? $args['verbose'] : false;
	}

	public function setDrupalPath($path) {

		$this->drupalPath = $path;
	}

	public function dirEmpty($dir) {

		if (!is_readable($dir)) {
			die("\n\nERROR: image store directory $dir does not exist or is not writable\n\n");
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
			die("\nERROR: images directory is not empty\n --images with -f to clear them\n --imageStore=Set to empty directory\n\n");
		}
	}

	public function setImagesDestination($config) {

		$wordpressDirectory = $config->wordpressPath . '/wp-content/';

		// multisite test -  our multisite is on /var/www/public
		if ($config->wordpressPath !== '/var/www/public') {
			$wordpressDirectory .= 'uploads';
		} else {
			$id = $config->siteId;
			$wordpressDirectory .= sprintf('blogs.dir/%d/files', (integer) $id);

		}
		$this->imagesDestination = $wordpressDirectory;
	}

	public function getImagesDestination() {
		return $this->imagesDestination;
	}

	private function source($uri) {
		
		if (preg_match('/^([\w]+):\/\//', $uri, $matched)) {
			return $matched[1];
		} else {
			throw new Exception('Unsupported image link: ' . $uri);
		}
	}

	private function storeImageData($file) {

		$fileType = $this->source($file->uri);
//debug('STOREIMAGEDATA:' . $file->uri . ' '. $fileType);

		switch ($fileType) {
			case 'public' :
				$path = $this->drupalPath . '/sites/default/files/' . $file->filename;
				if ($this->verbose === true) {
					print "\nCopying image ".$path;
				}
				if (file_exists($path)) {
					try {
						copy($path, $this->imageStore . '/' . $file->filename);
						debug('copy from '.$path.' to '.$this->imageStore . '/' .$file->filename);
					} catch(Exception $e) {
						debug('could not copy ' . $path);
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
						// if (is_string($this->verbose)) {
						// 	print $this->verbose;
						// } else 
						if ($this->verbose === true) {
							print "\nImage data size: " . strlen($fileData);
						}

						$fd = fopen($this->imageStore . '/' . $file->filename, 'w+');
						fputs($fd, $fileData);
						fclose($fd);
						debug('stored s3 image in ' .$this->imageStore . '/' . $file->filename );

					}
					else {
						if ($this->verbose === true) {
							debug("no content in $path - only ".strlen($fileData) . ' bytes??');
						}
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
		//debug('IMG STORE:'. $this->imageStore . '/' . $file->filename);
	}


	public function fileList($nid) {

		$sql = "SELECT fu.fid, fu.module, fu.type, fu.id, fu.count, fm.uid, fm.filename as filename, fm.uri as uri, fm.filesize, fm.status, fm.timestamp 
				FROM file_managed fm
				JOIN file_usage fu ON fm.fid=fu.fid
				WHERE fu.id=$nid AND fu.type='node'";

		$files = $this->connection->records($sql);

		return $files;
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
					$this->storeImageData($file);

				}
			}
		} catch (ErrorException $e) {
			print("did not get file ".$e->getMessage());
		}
		return $files;
	}

	// bad idea: we should use wp-cli to do this
	public function moveFile($fileObject) {

		$wp = new WP($this->db, $options);

		$source = $this->imageStore . $fileObject->filename;

		// get the year and month from the timestamp
		$year = date('Y', $fileObject->timestamp);
		$month = date('m', $fileObject->timestamp); 
		$destination = sprintf('%s/%d/%d', $this->imagesDestination, $year, $month);

		// does the destination directory exist?
		if (!file_exists($destination)) {
			try {
				if ($this->verbose === true) {
					print "\nMaking directory $destination";
				}
				mkdir($destination, 0775, true);
			} catch(Exception $e) {
				throw new Exception('can not make a directory for ' . $destination . " " .$e->getMessage());
			}
		}

		$destination =  sprintf('%s/%d/%d', $this->imagesDestination, $year, $month);
		$destinationFilename = $destination . '/' . $fileObject->filename;

		try {
			if ($this->verbose === true) {
				print "\nMOVE $source >>TO>> $destinationFilename";
			}
			rename($source, $destinationFilename);
			chmod($destinationFilename, 0664);

		} catch(Exception $e) {
			throw new Exception('Problem writing to file '.$destinationFilename . " " . $e->getMessage());
		}
	}

	/* 
	 * TODO: Deprecate this?? - there may be multiple versions but best version appears to be the base path
	 *
	 * ... REDUNDANT?  no reason we can not have multiple images or files, but maybe a way to idenfity featured image?
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
