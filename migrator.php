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
 * exclusive flag:
 * -u users - ONLY creates users reading the dusers table (run on live)
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

$users = new User($wp, $d7, $options);

// databases are now available as $wp and $d7
$wordpress = new WP($wp, $options);

/* nodes */
$d7_node = new Node($d7);
$wp_post = new Post($wp, $options);

// use termmeta to record nodeIds converted to wordpress IDs
$wp_termmeta = new WPTermMeta($wp);

if ($options->users) {

	// do not clear users unless it is specified
	// read and transfer all users if -u specified

	if ($users->doWordpressUsersExist()) {
		debug('Importing Drupal users to existing Wordpress users');
	} 

	// if dusers flag is set, read the users from the dusers temporary table
	if ($options->dusers) {
		$users->getTempDrupalUsers();
		debug($users->countDrupalUsers() . ' users from temporary table (dusers)');
	} else {
		$users->getDrupalUsers(); //debug($users->drupalUsersLoaded() . ' users loaded from Drupal');
	}
	debug("\nDrupal users loaded: " . $users->countDrupalUsers() . "\n\n");

	$users->createWordpressUsers($options->siteId);  
	//debug($users->wordpressUsers() . '... users created in Wordpress');
	$users->makeAdminUser();

	die("\n\nUsers imported, now run without the -u switch to do imports using these users.\n\n");

} else {
	if (!$users->doWordpressUsersExist()) {
		die("\nERROR: wordpress users do not yet exist - you need to run with a -u flag\n");
	}
}

$files = new Files($d7, $s3bucket, $options);

// the files option is required to clear images
if ($options->files) {

	$cmdPath = 'importCmds.sh';
	$cmdFile = fopen($cmdPath, 'w+');

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

if ($options->initialise) {
	$initialise = new Initialise($wp, $options);
	$initialise->cleanUp($wp);
}

$wp_termmeta_term_id = $wp_taxonomy->getSetTerm(DRUPAL_WP, DRUPAL_WP);

$nodeSource = 'drupal';

if (isset($wp_termmeta_term_id) && $wp_termmeta_term_id && (!$options->nodes && !$options->initialise)) {
	message("\nDrupal node data has already been imported to Wordpress.");
	message("You can either clear it with the --initialise or -d[efaults] flag");
	message("or the wp-posts will be used, and the other tables will be imported...\n");
	$nodeSource = 'wordpress';
} else {
	message("\nImporting Node data from drupal...\n");
}

if ($options->taxonomy) {
	if ($verbose) {
		message("\nGetting Taxonomies from Drupal...");
	}

	$vocabularies = $d7_taxonomy->getVocabulary();
	$taxonomies = $d7_taxonomy->fullTaxonomyList();

	$wp_taxonomy->createTerms($taxonomies);

}

$showDebug = false;

if ($options->fields) {
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
	if ($showDebug && $verbose) {
		debug($fieldTables);
	}
}

// how many nodes to process?  - override default status=published
$nodeCount = $d7_node->nodeCount(NULL);

if ($nodeCount > $maxChunk) {
	$chunk = floor($nodeCount / $maxChunk);
} else {
	$chunk = $nodeCount;
}

$d7_node->setNodeChunkSize($nodeCount);
$chunks = floor($nodeCount / $chunk) + 1;

if ($options->fields) {
	$postmeta = new PostMeta($wp, DB::wptable('postmeta'));
}

if ($verbose) {
	print "\nConverting $nodeCount Drupal nodes\n";
}

$unassigned = [];

// set a value ONLY for a test version that only does a few posts
$TESTLIMIT = null;
$media_flag = null; $node_flag = null;

for ($c = 0; $c < $chunks; $c++) {

	$drupal_nodes = $d7_node->getNodeChunk($TESTLIMIT);

	//if chunking is not required, read all records
	//$drupal_nodes = $d7_node->getAllNodes();
	if ($verbose) {
	 	debug("\nNodes read: ". count((array)$drupal_nodes));
	}

	if (isset($drupal_nodes) && count($drupal_nodes)) {

		$galleries = [];

		foreach ($drupal_nodes as $node) {

if (preg_match('/Faith/', $node->title)) {
	debug($node);
	$node_flag = $node;
}

			$wpPostId = null;
			$fileSet = null;
			$nid = $node->nid;

			if ($options->nodes && $nodeSource === 'drupal') {

				$d7_node->setNode($node);

				// TODO: test if addMediaLibrary is working for media_entity posts
				if ($node->type === 'media_entity') {

// $media_name = $node->title;
					$media_set = $d7_fields->penton_media_images($node->nid);


// $wpPostId = $wp_termmeta->getTermMetaValue($wp_termmeta_term_id, $node->nid);

// if(!$wpPostId) {
// 	debug($wp_termmeta_term_id);
// 	//debug($node);
// 	//dd('check');
// }

					$featuredInNode = $files->getMediaEntityParentNodeId($node);
					$featuredImages[$featuredInNode] = $media_set;
// if (preg_match('/faith/', $media_set[0]->filename)) {
// 	$media_flag = $node;
// 	debug($featuredImages);
// }
					// 	if (!empty($media_set)) {
					// 		$file_set = $files->getFiles($node->nid);
					// 		if (isset($file_set)) {
					// 			foreach ($file_set as $file) {
					// 				// to add media_entity to the media library - we may need to know the wpPostId - but how?
					// 				$wordpress->addMediaLibrary($wpPostId, $file, $options, $node->type);
					// 			}
					// 		}
					// 	}
					// }
				} else {

					$wpPostId = $wp_post->makePost($node, $options, $files, $options->imageStore, $users);
					if ($wpPostId) {
						$metaId = $wp_termmeta->createTermMeta($wp_termmeta_term_id, $node->nid, $wpPostId);
					}
				}

			} else {
				// find the wpPostId for this node??
				$wpPostId = $wp_termmeta->getTermMetaValue($wp_termmeta_term_id, $node->nid);
			}

			if ($options->files) {
				// getFiles stores a local copy
				$fileSet = $files->getFiles($nid);

if (false && $node->type === 'media_entity') {
	$sql = "SELECT fm.filename, fm.uri, field_penton_media_image_fid AS fid, field_penton_media_image_title AS title, field_penton_media_image_alt AS al
		FROM field_data_field_penton_media_image fdfmi
		JOIN file_managed fm ON fm.fid=fdfmi.field_penton_media_image_fid
		WHERE entity_id=$nid";
	debug($fileSet);
		$imgs = $d7->records($sql);
	debug($imgs);
	if ($media_flag) {
		dd('check');
	}
}
				if (isset($fileSet)) {
					foreach ($fileSet as $file) {
						// TODO: moveFile is deprecated
						//$files->moveFile($file);
						if ($wpPostId) {
							$wordpress->addMediaLibrary($wpPostId, $file, $options);
						}
					}
				}
			}

			if ($options->taxonomy) {
				$taxonomies = $d7_taxonomy->nodeTaxonomies($node);

				if ($taxonomies && count($taxonomies)) {
					foreach ($taxonomies as $taxonomy) {
						$wp_taxonomy->makeWPTermData($taxonomy, $wpPostId);
						if ($verbose) {
							print "\n" . $taxonomy->category . ' : ' . $taxonomy->name;
						}
					}

					if (!$options->quiet && !$options->progress && ($verbose === true) ) {
						print "\nImported " . count($taxonomies) . " taxonomies.\n";
					}
				}
			}

			/* each node has a bunch of "fields" attached which can be additional content
			   for the content type and can be text fields, images. comments, tags
			*/
			if ($wpPostId && $options->fields) {

				// check each field table for content types and make WP POSTMETA
				if ($fieldTables && count($fieldTables)) {

					$object = new stdClass();
					$event = new stdClass();
					$report = new stdClass();

					$gallery = [];

					foreach($fieldTables as $fieldDataSource) {

						$gather = new Gather($d7, $fieldDataSource);
						$gather->setNid($node->nid);

						$tableName = 'field_data_field_' . $fieldDataSource;
						$func = 'get_' . $fieldDataSource;

						//Gather brings in the fields with softdata
						$data = $gather->$func($node->nid);


						if (isset($data) && count($data)) {
							$image->featured_image_id = null;
							$image = new stdClass();
							if ($data[0] === 'field_penton_media_image') {
								if ($fieldDataSource === 'penton_media_image') {
									$image->fid = $data[1]->field_penton_media_image_fid;
									$image->alt = $data[1]->field_penton_media_image_alt;
									$image->title = $data[1]->field_penton_media_image_title;
									$image->width = $data[1]->field_penton_media_image_width;
									$image->height = $data[1]->field_penton_media_image_height;
								}
							} else if ($data[0] === 'field_penton_media_type') {
								$image->type = $data[1]->field_penton_media_type_value;
								$image->nid = $node->nid; //$data[1]->entity_id;
							
							} else if ($data[0] === 'field_penton_media_credit') {
								if ($data[1]->field_penton_media_credit_value) {
									$image->credit = $data[1]->field_penton_media_credit_value;
								}
							} else if ($data[0] === 'field_penton_media_caption') {
								if ($data[1]->field_penton_media_caption_value) {
									$image->caption = $data[1]->field_penton_media_caption_value;
								}
							} else if ($data[0] === 'field_penton_link_media_feat_img') {
									$image->featured_image_id = $data[1]->field_penton_link_media_feat_img_target_id;
							} else if ($data[0] === 'field_penton_article_type_tid') {
									$article_types = ['Article', 'Gallery', 'Audio', 'Video', 'Webinar', 'Data Table', 'White Paper'];
									// TODO make wordpress postmeta elements
							}

							// create a featured image
							if (isset($image->featured_image_id)) {

								$image_url = $d7_node->getNode($image->featured_image_id)->title;
								// 
								//$post->makeAttachment($wpPostId, $image_url);
								//$postmeta->createFields($wpPostId, ['_thumbnail_id' => $mediaId]);
 								//$fileSet = $files->getFiles($node->nid);

								// the actual import of images is done with wp-cli - addUrlMediaLibary writes these commands
								$wordpress->addUrlMediaLibrary($wpPostId, $image_url, $options, true);

							}

							if (count((array)$image)) {
								$gallery[$wpPostId] = $image;
								continue;
							}

							$object = new stdClass();
							foreach ($data[1] as $key => $value) {

								if (strlen($value) && $value !== 'a:0:{}') {

									$shorterField = preg_replace('/^field_/', '', $key);
									
									if (preg_match('/_date_/', $key)) {
										$data[1]->$key = date_format(date_create($data[1]->$key), 'U');
									}

									//e.g. $event->report_url_url
									preg_match('/^(.*)_/', $shorterField, $match);

									//$object = new stdClass(); //$match[1];
									$object->$shorterField = $data[1]->$key;
									$fieldUpdate = [];
									foreach($object as $k => $v) {
										$fieldUpdate[$k] = isset($v) ? $v : '';
									}
									if (count($fieldUpdate)) {
										$postmeta->createFields($wpPostId, $fieldUpdate);
									}
								}
							}
						}
					}

					if (count($gallery)) {
						$galleries[] = $gallery;
					}
				}
			}
			// // process media_entitys  - 
			// if ($options->project === 'ioti') {
			// 	$sql = "SELECT fm.filename, fm.uri, field_penton_media_image_fid AS fid, field_penton_media_image_title AS title, field_penton_media_image_alt as al
			// 			FROM field_data_field_penton_media_image fdfmi
			// 			JOIN file_managed fm on fm.fid=fdfmi.field_penton_media_image_fid
			// 			WHERE entity_id=$nid";
			// 	$records = $d7->query($sql);

			// 	if (isset($records) && count($records)) {

			// 		foreach($records as $dimage) {
				
			// 			$filename = $dimage->filename;
				
			// 			// make the image:
			// 			$imageId = $files->getMediaEntity();
			// 			// make attachements
			// 			$wp_post->makeAttachment($wpPostId, $imageId);

			// 		}
			// 		//debug(DB::strip($sql));
			// 		//dd($records);
			// 	}
			// }
		}
		//debug($galleries, 0, 1);
	}
}

/*
	process media_entities
*/
foreach ($featuredImages as $nodeId => $mediaSet) {
	foreach($media_set as $media) {
		$wp_post_id = $wp_post->nodeToPost($nodeId);

		//create wp-cli import statements
		if ($wp_post_id) {
			$wordpress->addUrlMediaLibrary($wp_post_id, $media->uri, $options, $featured = true, $source = '');
		}
	}
}


/* process media galleries */

// post changes specific to a publication

// ioti -  use field_data_field_penton_content summary value field data to create excerpts
if ($options->project === 'ioti') {
	$cmds = [];
	$cmds[] = "UPDATE wp_38_posts p JOIN wp_38_postmeta m ON p.ID = m.post_id 
				SET post_excerpt = m.meta_value 
				WHERE m.meta_key = 'penton_content_summary_value' 
				 AND p.ID=m.post_id 
				 AND p.post_excerpt = ''";

	foreach ($cmds as $sql) {
		$wp->query($sql);
	}
}

// run cmds

if (count($unassigned)) {
	debug($unassigned);
}

$wp->close();
$d7->close();

$wp_taxonomy->__destroy();

die("\n\nMigrator programme ends.\n\n");
