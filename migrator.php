<?php

require "DB.class.php";
require "WP.class.php";

require "Initialise.class.php";
require "Options.class.php";
require "Post.class.php";
require "PostMeta.class.php";
require "WPTermMeta.class.php";

require "Node.class.php";
require "Files.class.php";
require "Taxonomy.class.php";

require "Fields.class.php";
require "FieldSet.class.php";
require "Gather.class.php";
//require "ACF.class.php";

require "common.php";

define('DRUPAL_WP', 'DRUPAL_WP');

$maxChunk = 1000000;
//$init = true;

$debug = false;
$once = 0;

/* control options */
try {
	$options = new Options();
	$options->setAll();

	// [tuauto, ioti, ...]
	$project 	= $options->get('project');
	// where the s3 files are
	$s3bucket 	= $options->get('s3bucket');
	// where the drupal files are
	$drupalPath = $options->get('drupalPath');
	// temporary image store
	$imageStore = $options->get('imageStore');
	// server = [local. vm, staging ] now only advisory as we read wp-config.php
	$server 	= $options->get('server');
	// tell me more
	$verbose    = $options->get('verbose');

	$option = [];

	foreach ($options->all as $opt) {
		$option[$opt] = $options->get($opt);
	}
	$options->showAll();

	if ($options->get('help')) {
		die("\nHELP Mode\n\n");
	}
} catch (Exception $e) {
	debug("Option setting error\n" . $e->getMessage() . "\n\n");
	die;
}
/* connect databases */
try {
	$wp = new DB($server, 'wp');
	$d7 = new DB($server, 'd7');
} catch (Exception $e) {
	die( 'DB connection error: ' . $e->getMessage());
}

// configure the wordpress environment
$wp->configure($options);
$d7->configure($options);

// the files option is required to clear images
if ($option['files']) {
	// the images option clears images
	if ($option['images']) {
		if (is_dir($imageStore)) {
			$files = glob($imageStore . '/*');
			foreach ($files as $file) {
				if (is_file($file)) {
					unlink($file);
				}
			}
			print "\n" . $imageStore . ' cleared of files.';
		} else {
			dd("ERROR: $imageStore is not a directory");
		}
	}
	$files = new Files($d7, $s3bucket, [
		'verbose' 	=> $verbose,
		'quiet' 	=> $option['quiet'],
		'progress' 	=> $option['progress']
	]);

	$options->dbPrefix = DB::$wp_prefix;

	$files->setDrupalPath($drupalPath);
	$files->setImageStore($imageStore);
	$files->setImagesDestination($options);

	if ($verbose) {
		print "\nimages will be imported to $imageStore";
	}
} else {
	if ($option['images']) {
		print "\nimages option -f not selected, images will not be cleared\n";
	}
}

$wp_taxonomy = new Taxonomy($wp, $options);
$d7_taxonomy = new Taxonomy($d7, $options);

// If the wordpress instance of Taxonomy needs to get drupal data: 
$wp_taxonomy->setDrupalDb($d7);

/* nodes */
$d7_node = new Node($d7);
$wp_post = new Post($wp);

/* content types ... */
$d7_fields = new Fields($d7);
$fieldSet = new FieldSet($d7);
$wp_fields = new Fields($wp);

$drupal_nodes = null;

if ($option['initialise']) {
	if ($once++ > 1) {
		throw new Exception('Initialise called more than once???');
	}
	$initialise = new Initialise($wp, $options);

	// build the term_taxonomy if not already present
	if ($wp_taxonomy->checkTerms()) {
		$wp_taxonomy->buildTerms();
	}
	$initialise->cleanUp($wp);
}

// use termmeta to record nodeIds converted to wordpress IDs
$wp_termmeta = new WPTermMeta($wp);
$wp_termmeta_term_id = $wp_termmeta->getSetTerm(DRUPAL_WP, 'Drupal Node ID');

$nodeSource = 'drupal';
if (isset($wp_termmeta_term_id) && $wp_termmeta_term_id && (!$option['nodes'] && !$option['initialise'])) {
	message("\nDrupal node data has already been imported to Wordpress.");
	message("You can either clear it with the --init or -d[efaults] flag");
	message("or the wp-posts will be used, and the other tables will be imported...\n");
	$nodeSource = 'wordpress';
} else {
	message("\nImporting Node data from drupal...\n");
}

if ($option['taxonomy']) {
	$vocabularies = $d7_taxonomy->getVocabulary();
	$taxonomyNames = [];
	$taxonomies = $d7_taxonomy->fullTaxonomyList();
	$wp_taxonomy->createTerms($taxonomies);
}

if ($option['fields']) {
	$records = $fieldSet->getFieldData();
	$fieldTables = [];
	foreach ($records as $key => $numberFound) {
		$fields = $fieldSet->getFieldData($key);
		foreach ($fields as $field) {
			$fieldTables[] = $key . '_' . $field;
		}
	}
}

// how many nodes to process?
$nodeCount = $d7_node->nodeCount();

if ($nodeCount > $maxChunk) {
	$chunk = floor($nodeCount / $maxChunk);
} else {
	$chunk = $nodeCount;
}

$d7_node->setNodeChunkSize($nodeCount);
$chunks = floor($nodeCount / $chunk);

if ($option['fields']) {
	$postmeta = new PostMeta($wp, DB::wptable('postmeta'));
}
if ($verbose) {
	print "\nConverting $nodeCount Drupal nodes\n";
}

$unassigned = [];

// set a value ONLY for a test version that only does a few posts
$TESTLIMIT = null;

for ($c = 0; $c < $chunks; $c++) {

	$drupal_nodes = $d7_node->getNodeChunk($TESTLIMIT);

	if (isset($drupal_nodes) && count($drupal_nodes)) {

		foreach ($drupal_nodes as $node) {
			
			$wpPostId = null;
			$fileSet = null;

			if ($option['files']) {
				// getFiles stores a local copy
				$fileSet = $files->getFiles($node->nid);
				if (isset($fileSet)) {
					foreach ($fileSet as $file) {
						$files->moveFile($file);

					}
				}
			}

			if ($option['nodes'] && $nodeSource === 'drupal') {
				$d7_node->setNode($node);
				$wpPostId = $wp_post->makePost($node, $options, $fileSet, $files->getImagesDestination());
				if ($wpPostId) {
					$metaId = $wp_termmeta->createTermMeta($wp_termmeta_term_id, $node->nid, $wpPostId);
				} else {
					debug('makePost returned no value for this node??');
					dd($node);
				}
			} else {
				// find the wpPostId for this node??
				$wpPostId = $wp_termmeta->getTermMetaValue($wp_termmeta_term_id, $node->nid);
			}



			if ($option['taxonomy']) {

				$taxonomies = $d7_taxonomy->nodeTaxonomies($node);
				if ($taxonomies && count($taxonomies)) {
					foreach ($taxonomies as $taxonomy) {
						$wp_taxonomy->makeWPTermData($taxonomy, $wpPostId);
						if ($verbose) {
							print "\n" . $taxonomy->category . ' : ' . $taxonomy->name;
						}
					}

					if (!$option['quiet'] && !$option['progress'] && ($verbose === true) ) {
						print "\nImported " . count($taxonomies) . " taxonomies.\n";
					}
				}
			}

			/* each node has a bunch of "fields" attached which can be additional content
			   for the content type and can be text fields, images. comments, tags
			*/
			if ($wpPostId && $option['fields']) {

				// check each field table for content types and make WP POSTMETA
				if ($fieldTables && count($fieldTables)) {

					$object = new stdClass();

					// $events = [];
					// $reports = [];
					$event = new stdClass();
					$report = new stdClass();

					foreach($fieldTables as $fieldDataSource) {

						$gather = new Gather($d7, $fieldDataSource);
						$gather->setNid($node->nid);

						$tableName = 'field_data_field_' . $fieldDataSource;
						$func = 'get_' . $fieldDataSource;

						$data = $gather->$func($node->nid);

						if (isset($data) && count($data)) {

// TODO - look at WHY not generalise this  $data[1]->$data[0]
							$verbose = false;
if ($debug && $verbose) {
	debug($node->nid .  print_r($data,1));
}
							$verbose = false;
							foreach ($data[1] as $field => $value) {

								$shorterField = preg_replace('/field_/', '', $field);
if ($debug && $verbose) {
	debug($shorterField);
}

								if (preg_match('/_date_/', $field)) {

									$data[1]->$field = date_format(date_create($data[1]->$field), 'Y-m-d h:i:s');
								}

								// e.g. $event->report_url_url
								preg_match('/^(.*)_/', $shorterField, $match);
								$object = new stdClass(); //$match[1];
								$object->$shorterField = $data[1]->$field;
if ($debug && $verbose) {
	debug($object->$shorterField);
}

								preg_match('/(.*?)_(.*)/', $shorterField, $parts);
								

								// this does not trigger - may want to look at why?
								if ($parts[1]  && $parts[1] === 'primary') {
									$nid_check = $data[1]->$field;
									if ((integer) $nid_check === (integer) $node->nid) {
										debug('nid matched ' . $data);
										dd('check');
									}
								}


							}
						}

						if ($debug && $verbose) {
							if (count((array) $object)) {
								debug($object);
							}
							if (count((array) $event)) {
								print "\n";
								print 'event:';
								debug($event);
							}
							if (count((array) $report)) {
								print "\n";
								print 'report:';
								debug($report);
							}
						}

						$fieldUpdate = [];
						foreach($object as $key => $value) {
								$fieldUpdate[$key] = isset($value) ? $value : '';
						}

						$postmeta->createFields($wpPostId, $fieldUpdate);
					}
				}
			}
		}
	}
}

if (count($unassigned)) {
	debug($unassigned);
}

$wp->close();
$d7->close();

$wp_taxonomy->__destroy();

die("\n\nMigrator programme ends.\n\n");