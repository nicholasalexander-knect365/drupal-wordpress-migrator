<?php
/***
 * php migrator.php Version 1.08
 *
 * by Nicholas Alexander for Informa Knect365
 *
 *purpose: migrate a drupal instance into a wordpress instance
 *
 * options -d default mode 
 * location settings:
 * --wordpressPath= --drupalPath= --wordpressURL= --imageStore=
 * conversions included settings:
 * -f files (images)
 * -c ACF fields
 * -t taxonomy
 * -n nodes
 * -d default modes (with --server)
 */
require "DB.class.php";
require "WP.class.php";

require "Initialise.class.php";
require "Options.class.php";
require "Post.class.php";
require "PostMeta.class.php";
require "WPTermMeta.class.php";
require "User.class.php";

require "Node.class.php";
require "Files.class.php";
require "Taxonomy.class.php";

require "Fields.class.php";
require "FieldSet.class.php";
require "Gather.class.php";

// common routines include script initialisation
require "common.php";

// databases are now available as $wp and $d7
$wordpress = new WP($wp, $options);

/* nodes */
$d7_node = new Node($d7);
$wp_post = new Post($wp, $options);

// use termmeta to record nodeIds converted to wordpress IDs
$wp_termmeta = new WPTermMeta($wp);

// do not clear users unless it is specified
// read and transfer all users if -u specified
if ($options->users) {

	if ($users->doWordpressUsersExist()) {
		debug('Importing Drupal users to existing Wordpress users');
	}

	$users->getDrupalUsers(); //debug($users->drupalUsersLoaded() . ' users loaded from Drupal');

	$users->createWordpressUsers($options->siteId);  //debug($users->wordpressUsers() . '... users created in Wordpress');

	$users->makeAdminUser();

} else {
	if (!$users->doWordpressUsersExist()) {
		die("\nERROR: wordpress users do not yet exist - you need to run with a -u flag\n");
	}
}

// the files option is required to clear images
if ($option['files']) {

	$cmdPath = 'importCmds.sh';
	$cmdFile = fopen($cmdPath, 'w+');

	$files = new Files($d7, $s3bucket, [
		'verbose' 	=> $option['verbose'],
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
}

$wp_taxonomy = new Taxonomy($wp, $options);
$d7_taxonomy = new Taxonomy($d7, $options);

// If the wordpress instance of Taxonomy needs to get drupal data: 
$wp_taxonomy->setDrupalDb($d7);

/* content types ... */
$d7_fields = new Fields($d7);
$fieldSet = new FieldSet($d7);

$wp_fields = new Fields($wp);

$drupal_nodes = null;

if ($option['initialise']) {
	// if ($once++ > 1) {
	// 	throw new Exception('Initialise called more than once???');
	// }
	$initialise = new Initialise($wp, $options);

	// build the term_taxonomy if not already present
	if ($wp_taxonomy->checkTerms()) {
		$wp_taxonomy->buildTerms();
	}
	$initialise->cleanUp($wp);
}

$wp_termmeta_term_id = $wp_taxonomy->getSetTerm(DRUPAL_WP, DRUPAL_WP);

$nodeSource = 'drupal';
if (isset($wp_termmeta_term_id) && $wp_termmeta_term_id && (!$option['nodes'] && !$option['initialise'])) {
	message("\nDrupal node data has already been imported to Wordpress.");
	message("You can either clear it with the --initialise or -d[efaults] flag");
	message("or the wp-posts will be used, and the other tables will be imported...\n");
	$nodeSource = 'wordpress';
} else {
	message("\nImporting Node data from drupal...\n");
}

if ($option['taxonomy']) {
	if ($verbose) {
		message("\nGetting Taxonomies...");
	}
	$vocabularies = $d7_taxonomy->getVocabulary();
	$taxonomyNames = [];
	$taxonomies = $d7_taxonomy->fullTaxonomyList();
	$wp_taxonomy->createTerms($taxonomies);
}

if ($option['fields']) {
	if ($verbose) {
		message("\nGetting fields...");
	}
	$records = $fieldSet->getFieldData();
	$fieldTables = [];
	foreach ($records as $key => $numberFound) {
		$fields = $fieldSet->getFieldData($key);
		foreach ($fields as $field) {
			$fieldTables[] = $key . '_' . $field;
		}
	}
	if ($verbose) debug($fieldTables);
}

// how many nodes to process?
$nodeCount = $d7_node->nodeCount();

if ($nodeCount > $maxChunk) {
	$chunk = floor($nodeCount / $maxChunk);
} else {
	$chunk = $nodeCount;
}

$d7_node->setNodeChunkSize($nodeCount);
$chunks = floor($nodeCount / $chunk) + 1;

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

	// if chunking is not required, read all records
	//$drupal_nodes = $d7_node->getAllNodes();
	//debug("\nNodes read: ". count((array)$drupal_nodes));

	if (isset($drupal_nodes) && count($drupal_nodes)) {

		foreach ($drupal_nodes as $node) {

			$wpPostId = null;
			$fileSet = null;

			if ($option['nodes'] && $nodeSource === 'drupal') {
				$d7_node->setNode($node);
				$wpPostId = $wp_post->makePost($node, $options, $files, $options->imageStore, $users);

				$files->getImagesDestination();
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

			if ($option['files']) {
				// getFiles stores a local copy
				$fileSet = $files->getFiles($node->nid);

				if (isset($fileSet)) {
					foreach ($fileSet as $file) {
						//$files->moveFile($file);
						if ($wpPostId) {
							$wordpress->addMediaLibrary($wpPostId, $file, $options);
						}
					}
				}
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
					$event = new stdClass();
					$report = new stdClass();

					foreach($fieldTables as $fieldDataSource) {

						$gather = new Gather($d7, $fieldDataSource);
						$gather->setNid($node->nid);

						$tableName = 'field_data_field_' . $fieldDataSource;
						$func = 'get_' . $fieldDataSource;

						$data = $gather->$func($node->nid);

						if (isset($data) && count($data)) {

							$object = new stdClass();
							foreach ($data[1] as $field => $value) {

								if (strlen($value) && $value !== 'a:0:{}') {
									$shorterField = preg_replace('/^field_/', '', $field);
									
									if (preg_match('/_date_/', $field)) {
										$data[1]->$field = date_format(date_create($data[1]->$field), 'U');
									}
									
									//e.g. $event->report_url_url
									preg_match('/^(.*)_/', $shorterField, $match);

									//$object = new stdClass(); //$match[1];
									$object->$shorterField = $data[1]->$field;
									$fieldUpdate = [];
									foreach($object as $key => $value) {
										$fieldUpdate[$key] = isset($value) ? $value : '';
									}
									if (count($fieldUpdate)) {
										$postmeta->createFields($wpPostId, $fieldUpdate);
									}
								}
							}
						}
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
