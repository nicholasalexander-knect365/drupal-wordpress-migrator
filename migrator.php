<?php

require "DB.class.php";
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

/*
 * v100 while adding new features,
 * maintain old features:
 * but turn them on selectively
 * for testing new features
 *
 * v101/2 import images
 * v102/3 options 
 * v104 node import
 * v105 fields import (includes tags, images and content_types)
 */

$maxChunk = 1000000;
$init = true;

$debug = false;

/* control options */
$options = new Options();
$options->setAll();

$s3bucket 	= $options->get('s3bucket');
$drupalPath = $options->get('drupalPath');
$imageStore = $options->get('imageStore');
$server 	= $options->get('server');

$verbose    = $options->get('verbose');

$option = [];

foreach ($options->all as $opt) {
	$option[$opt] = $options->get($opt);
}
$options->showAll();

// if ($options->get('defaults')) {
// 	$options->setDefaults();
// 	$options->showAll();
// }

if ($options->get('help')) {
	die("\nHELP Mode\n\n");
}

/* connect databases */
try {
	$wp = new DB($server, 'wp');
	$d7 = new DB($server, 'd7');
} catch (Exception $e) {
	die( 'DB ' . $e->getMessage());
}

if ($option['files']) {
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

	$files->setDrupalPath($drupalPath);
	$files->setImageStore($imageStore);
	if ($verbose) {
		print "\nimages will be imported to $imageStore";
	}
} else {
	if ($option['images']) {
		print "\nimages option -f not selected, images will not be cleared\n";
	}
}

$wp_taxonomy = new Taxonomy($wp, $verbose);
$d7_taxonomy = new Taxonomy($d7);

// If the wordpress instance of Taxonomy needs to get drupal data: 
$wp_taxonomy->setDrupalDb($d7);

/* nodes */
$d7_node = new Node($d7);
$wp_post = new Post($wp);

/* content types ... */
$d7_fields = new Fields($d7);
$fieldSet = new FieldSet($d7);
$wp_fields = new Fields($wp);

// use termmeta to record nodeIds converted to wordpress IDs
$wp_termmeta = new WPTermMeta($wp);
$wp_termmeta_term_id = $wp_termmeta->getSetTerm(DRUPAL_WP, 'Drupal Node ID');

$nodeSource = 'drupal';
if (isset($wp_termmeta_term_id) && !$option['nodes']) {
	message("\nDrupal node data has already been imported to Wordpress.");
	message("You can either clear it with the --init or -d[efaults] flag");
	message("or the wp-posts will be used, and the other tables will be imported...\n");
	$nodeSource = 'wordpress';
} else {
	message("\nImporting Node data from drupal...\n");
}

if ($option['taxonomy']) {
	$wp_taxonomy->initialise($init);
	$vocabularies = $d7_taxonomy->getVocabulary();
	$taxonomyNames = [];
	$taxonomies = $d7_taxonomy->fullTaxonomyList();
	$wp_taxonomy->createTerms($taxonomies);
}

$drupal_nodes = null;

if ($option['initialise']) {
	// build the term_taxonomy if not already present
	if ($wp_taxonomy->checkTerms()) {
		$wp_taxonomy->buildTerms();
	}
	Initialise::purge($wp);
}

if ($option['fields']) {
	$records = $fieldSet->getFieldData();
	$fieldTables = [];
	foreach($records as $key => $numberFound) {
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

for ($c = 0; $c < $chunks; $c++) {

	$drupal_nodes = $d7_node->getNodeChunk();

	if (isset($drupal_nodes) && count($drupal_nodes)) {

		foreach ($drupal_nodes as $node) {
			
			$wpPostId = null;

			if ($option['nodes'] && $nodeSource === 'drupal') {
				$d7_node->setNode($node);
				$wpPostId = $wp_post->makePost($node, $options);
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
				$images = $files->getFiles($node->nid);
				if ($images) {
					$largest = null;

					foreach ($images as $image) {
						// get all sizes for this image
						$best = $files->getBestVersion($image->filename);

						if (!$option['quiet'] && !$option['progress'] && ($verbose === true || $files->isVerbose())) {
							print "\n" . $best->fid . ' ' . $best->type . ' ' . $best->filename . ' ' . $best->uri . "\n";
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
// print($shorterField);
// debug($parts, 0);

// not sure what this means....
//   why do we get primary with nids that are not this nid??
if ($parts[1]  && $parts[1] === 'primary') {
	$nid_check = $data[1]->$field;
	if ((integer) $nid_check === (integer) $node->nid) {
		debug('nid matched ' . $data);
		dd('check');
	}
}


								// if (preg_match('/_event_/', $data[0])) {

								// 	$event->$shorterField = $data[1]->$field;

								// } else if (preg_match('/_report_/', $data[0])) {

								// 	$event->$shorterField = $data[1]->$field;

								// } else {

								// 	debug('unknown object ' . $data[0]);
								// }
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
							// switch ($data[0]) {
							// 	case 'field_primary_event':
							// 		$event->nid = $data[1]->field_primary_event_nid;
							// 		break;
							// 	case 'field_event_date':
							// 		$event->start_date = date_format(date_create($data[1]->field_event_date_value), 'Y-m-d h:i:s');
							// 		$event->end_date   = date_format(date_create($data[1]->field_event_date_value2), 'Y-m-d h:i:s');
							// 		break;
							// 	case 'field_event_location':
							// 		$event->venue = $data[1]->field_event_location_value;
							// 		break;
							// 	case 'field_event_url':
							// 		$event->url = $data[1]->field_event_url_url;
							// 		break;
							// 	case 'field_event_organiser':
							// 		$event->organiser = $data[1]->field_event_organiser_value;
							// 		break;
							// 	case 'field_event_organiser_email':
							// 		$event->organiser_email = $data[1]->field_event_organiser_email_email;
							// 		break;
							// 	case 'field_event_attendees':
							// 		$event->attendees = $data[1]->field_event_attendees_value;
							// 		break;

							// 	case 'field_report_image_fid':
							// 		$report->image_fid = $data[1]->field_report_image_fid;
							// 		break;
							// 	case 'field_report_image_alt':
							// 		$report->image_alt = $data[1]->field_report_image_alt;
							// 		break;
							// 	case 'field_report_image_title':
							// 		$report->image_title = $data[1]->field_report_image_title;
							// 		break;
							// 	case 'field_report_image_width':
							// 		$report->image_width = $data[1]->field_report_image_width;
							// 		break;
							// 	case 'field_report_image_height':
							// 		$report->image_height = $data[1]->field_report_image_height;
							// 		break;

							// 	case 'field_report_teaser_value':
							// 		$report->teaser_value = $data[1]->field_report_teaser_value;
							// 		break;
							// 	case 'field_report_teaser_format':
							// 		$report->teaser_format = $data[1]->field_report_teaser_format;
							// 		break;

							// 	case 'field_report_url_url':
							// 		$report->report_url_url = $data[1]->field_report_url_url;
							// 		break;								
							// 	case 'field_report_url_title':
							// 		$report->report_url_title = $data[1]->field_report_url_title;
							// 		break;
							// 	case 'field_report_url_attributes':
							// 		$report->field_report_url_attributes = $data[1]->field_report_url_attributes;
							// 		break;
							// 	case 'field_report_teaser_format':
							// 		$report->teaser_format = $data[1]->field_report_teaser_format;
							// 		break;


							// 	default:
							// 		if (empty($unassigned[$data[0]])) {
							// 			$unassigned[$data[0]] = 1;
							// 		} else {
							// 			$unassigned[$data[0]]++;
							// 		}
							// 		break;
							// }
	
					// 	}
					// }

					// if (isset($event->start_date)) {

						// $postmeta->createEventFields($wpPostId, [ 
						// 	'start_date' 	=> isset($event->start_date) ? $event->start_date : '',
						// 	'end_date' 		=> isset($event->end_date) ? $event->end_date : '',
						// 	'venue'			=> isset($event->venue) ? $event->venue : '',
						// 	'url'			=> isset($event->url) ? $event->url : '',
						// 	'organiser' 	=> isset($event->organiser) ? $event->organiser : '',
						// 	'organiser_email' => isset($event->organiser_email) ? $event->organiser_email : '',
						// 	'attendees' 	=> isset($event->attendees) ? $event->attendees : ''
						// ]);
					// }

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