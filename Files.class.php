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
	private $options;
	private $verbose = false;
	public $s3Bucket = 'http://pentontuautodrupalfs.s3.amazonaws.com';

	public function __construct(DB $connection, $s3bucket = '', $options) {

		$this->connection = $connection;
		$this->nid = null;
		if (strlen($s3bucket)) {
			$this->s3Bucket = $s3bucket;
		}
		$this->type = 'node';
		$this->options = $options;
		$this->verbose = $options->verbose;
	}

	public function isVerbose() {

		return $this->verbose;
	}

	public function setDrupalPath($path) {

		$this->drupalPath = $path;
	}

// initially I was concerned that images are imported freshly each time, but once we have the image, no need to get it again
// deprecate this 
	public function dirEmpty($dir) {
		return;
		// if (!is_readable($dir)) {
		// 	debug("\n\nWARNING: image store directory $dir does not exist or is not writable\n\n");
		// }
  // 		return (count(scandir($dir)) == 2);
	}

	public function setImageStore($path) {

		$this->imageStore = $path;
		return;

		// if ($this->dirEmpty($path)) {
		// 	$this->imageStore = $path;
		// } else {
		// 	die("\nERROR: images directory is not empty\n --images with -f to clear them\n --imageStore=Set to empty directory\n\n");
		// }
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

		switch ($fileType) {
			case 'public' :

				$uri = preg_replace('/public:\/\//', '', $file->uri);
				$path = $this->drupalPath . '/sites/default/files/' . $uri;
				if ($this->verbose) {
					print "\nCopying image ".$path;
				}
				if (file_exists($path)) {
					try {
						copy($path, $this->imageStore . '/' . $file->filename);
						if ($this->verbose) {
							debug('copy from '.$path.' to '.$this->imageStore . '/' .$file->filename);
						}
					} catch(Exception $e) {
						debug('could not copy ' . $path);
					}
				} else {
					if ($this->verbose) {
						print "\nimage path: $path does not exist";
					}
				}
				break;

			case 's3':
				$target = $this->imageStore . '/' . $file->filename;
				if (!file_exists($target)) {
					
					//dd('file does not already exist??  ' . $file->filename);
					$path = $this->s3Bucket . '/' . $file->filename;

					try {
						$fileData = file_get_contents($path);
						if (strlen($fileData) > 14) {

							if ($this->verbose) {
								print "\nImage data size: " . strlen($fileData);
							}

							$fd = fopen($target, 'w+');
							fputs($fd, $fileData);
							fclose($fd);
							if ($this->verbose) {
								debug('stored s3 image in ' .$this->imageStore . '/' . $file->filename );
							}

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
				} else {
					debug($target . ' s3 sourced file has already been imported.');
				}
				break;

			default: 
				print "\nDo not know how to get this image " . $path . " type is " . $fileType;
		}
	}

	public function fileList($nid) {

		$sql = "SELECT fu.fid, fu.module, fu.type, fu.id, fu.count, fm.uid, fm.filename AS filename, fm.uri AS uri, fm.filesize, fm.status, fm.timestamp 
				FROM file_managed fm
				JOIN file_usage fu ON fm.fid=fu.fid
				WHERE fu.id=$nid AND fu.type='node'";

		$files = $this->connection->records($sql);

		return $files;
	}

	public function getMediaEntity($node) {
		$nid = $node->nid;
		// assume node is a media_entity

		// sql to get alt and title of the 
		$sql = "SELECT 	field_penton_media_image_fid as fid, 
						field_penton_media_image_alt as alt, 
						field_penton_media_image_title as title 
				FROM field_data_field_penton_media_image 
				WHERE bundle='media_entity' AND entity_id=$nid";
		$record = $this->connection->record($sql);

		return $record;
	}

	// in drupal, a media_entity can have an associated: 
	//     featured_image 
	//     image_gallery  ... and other entities that 
	// define its use (disposition) and therefore may create wordpress featured images, image gallery 
	public function getMediaEntityParentNodeId($node) {

		$nid = $node->nid;

		$sql = "SELECT entity_id 
				FROM field_data_field_penton_link_media_feat_img 
				WHERE bundle='article' AND field_penton_link_media_feat_img_target_id=$nid";

		$record = $this->connection->record($sql);
		if (isset($record) && $record->entity_id) {
			return $record->entity_id;
		}
		return NULL;
	}

	// ????? may not be required
	public function convertMediaEntityImages($files) {
		foreach ($files as $file) {
			$this->storeImageData($file);
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
					$this->storeImageData($file);

				}
			}
		} catch (ErrorException $e) {
			print("did not get file ".$e->getMessage());
		}
		return $files;
	}


	// moveFiles is deprecated in favour of 
	// composing wp-cli commands in importCmds.php
	// as Wordpress then builds the Media Library
	public function moveFile($fileObject) {

		throw new Exception('Files::moveFile is deprecated in favour of importCmds.sh file');
		// DEPRECATE: use wp-cli to do this

		$wp = new WP($this->connection, $this->options);

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
	// public function getBestVersion($filename) {

	// 	$sql = "SELECT fu.fid, fu.module, fu.type, fu.id, fu.count, fm.uid, fm.filename as filename, fm.uri as uri, fm.filesize, fm.status, fm.timestamp 
	// 			FROM file_managed fm
	// 			JOIN file_usage fu ON fm.fid=fu.fid
	// 			WHERE fm.filename like '$filename'";

	// 	$images = $this->connection->records($sql);

	// 	if (count($images) === 1) {
	// 		return $images[0];
	// 	}
	// 	if (count($images) === 0) {
	// 		print str_replace(["\n", "  ", "\t"],["", " ", ' '], $sql) . "\n";
	// 		throw new Exception('getBestImage should never find no image?');
	// 	}
	// 	$best = null;
	// 	foreach ($images as $image) {
	// 		if (!$best || $image->filesize > $best->filesize) {
	// 			$best = $image;
	// 		}
	// 	}
	// 	return $best;
	// }

}
