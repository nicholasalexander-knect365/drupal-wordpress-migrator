<?php

require "DB.class.php";
require "WP.class.php";

require "Initialise.class.php";
require "Options.class.php";
require "Post.class.php";
require "PostMeta.class.php";
require "WPTermMeta.class.php";
require "User.class.php";

require "Node.class.php";
require "Taxonomy.class.php";

require "Fields.class.php";
require "FieldSet.class.php";
require "Gather.class.php";

// common routines include script initialisation
require "common.php";

$users = new User($wp, $d7, $options);

$wordpressDBConfig = $wp->wpDBConfig();
$wp->setShowQuery(true);
// databases are now available as $wp and $d7

$wordpress = new WP($wp, $options, $wordpressDBConfig);
$postmeta = new PostMeta($wp, DB::wptable('postmeta'));

/* nodes */
$d7_node = new Node($d7);
$wp_post = new Post($wp, $options);

$wp_taxonomy = new Taxonomy($wp, $options);
$d7_taxonomy = new Taxonomy($d7, $options);

/* content types ... */
$d7_fields = new Fields($d7);
$fieldSet = new FieldSet($d7);

$wp_fields = new Fields($wp);

// use termmeta to record nodeIds converted to wordpress IDs
$wp_termmeta = new WPTermMeta($wp);
$wp_termmeta_term_id = $wp_taxonomy->getSetTerm(DRUPAL_WP, DRUPAL_WP);
$wp_posts = DB::wptable('posts');
$wp_termmeta = DB::wptable('termmeta');


function status($wp, $wp_posts, $wp_termmeta) {
	$sql = "SELECT COUNT(*) as c FROM $wp_posts";
	$record = $wp->record($sql);
	debug("\nNumber of posts ". $record->c);

	$sql = "SELECT COUNT(*) as c FROM $wp_termmeta";
	$record = $wp->record($sql);
	$record = $wp->record($sql);
	debug("\nNumber of termmeta ". $record->c);
}  

status($wp, $wp_posts, $wp_termmeta);

$newposts = fopen('sql/ioti_additional_posts.sql', 'r');
$ioti_urls = fopen('./URLs/ioti-urls', 'w');

// initialise for field data fields
$records = $fieldSet->getFieldData();
$fieldTables = [];
foreach ($records as $key => $numberFound) {
	$fields = $fieldSet->getFieldData($key);
	foreach ($fields as $field) {
		$fieldTables[] = $key . '_' . $field;
	}
}

$BEGIN_TRANS = "START TRANSACTION";
$COMMIT = "COMMIT";

$wp->query($BEGIN_TRANS);

while ($line = fgets($newposts)) {
	//print "\n".$line;
	// copy up to the first escaped apos in title
	preg_match("/INSERT (.*?) VALUES \('.+?', '(.*?),/", $line, $matched);

// var_dump($line);
// var_dump($matched);
	//	print "\n" . $matched[2];
	
	$title = substr($matched[2], 0, strlen($matched[2])-1);
	//debug('TITLE='.$title);

	// find the title in drupal
	if (strlen($title) > 2) {

		$sql = "SELECT * FROM ioti_drupal.node WHERE title LIKE '$title%'";
		$d7nodes = $d7->records($sql);

		if (isset($d7nodes) && count($d7nodes) !== 1) {

			debug($sql);
			debug($d7nodes);

		} else if (isset($d7nodes) && count($d7nodes) === 1) {
			$nid = $d7nodes[0]->nid;

			// insert the post
			$wp->query($line);
			$wpPostId = $wp->lastInsertId();

			if (empty($wpPostId)) {
				throw new Exception($line . ' inserted without returning a post_id?');
			}

			$sql = "INSERT INTO $wp_termmeta (term_id, meta_key, meta_value) VALUES ($wp_termmeta_term_id, $nid, $wpPostId)";
			$wp->query($sql);

			// summary for excerpt
			$sql = "SELECT field_penton_content_summary_value AS excerpt 
					FROM field_data_field_penton_content_summary 
					WHERE entity_id = $nid 
					LIMIT 1";
			$record = $d7->record($sql);
			//$record = $wp->record($sql);

			if (isset($record)) {
				$excerpt = addslashes($record->excerpt);
				// update the post
				$sql = "UPDATE $wp_posts SET post_excerpt='$excerpt' WHERE ID=$wpPostId";
				$wp->query($sql);
			} else {
				debug(DB::strip($sql));
				debug('field_penton_content_summary_value did not find a record!');
				debug($record);
			}

			// externally defined author
			$sql = "SELECT * FROM field_data_field_penton_author WHERE entity_id=$nid";
			$authorRecords = $wp->records($sql);

			if (isset($authorRecords) && count($authorRecords) > 0) {
				$author_id = $authorRecords[0]->field_penton_author_target_id;
				// lookup the author
				$sql = "SELECT user_id FROM wp_usermeta WHERE meta_key='drupal_38_uid' AND meta_value='$author_id' LIMIT 1";
				$um = $wp->record($sql);
				if ($um && $um->user_id) {
					$authorId=$um->user_id;

					$sql = "UPDATE $wp_posts SET post_author=$authorId WHERE ID=$wpPostId LIMIT 1";
					$wp->query($sql);
				}
			}

			// featured image
			$postImages = $d7_fields->penton_media_feat_img($nid);

			if (isset($postImages) && count($postImages)) {
				$filename = $postImages[0]->filename;
				$fid = $postImages[0]->fid; 
				$media_settings = $d7_fields->penton_media_settings($fid);
			}

			$wordpress->addMediaLibrary($wpPostId, $filename, $options, $featured = true, $media_settings, $source = '');
dd($wpPostId);
			// primary category
			$sql = "SELECT field_penton_primary_category_tid AS tid FROM field_data_field_penton_primary_category WHERE entity_id=$nid";
			$record = $d7->record($sql);
//dd($record);
			if (isset($record)) {
				$pc_tid = $record->tid;
				$term = $wp_taxonomy->getTermFromSlug('primary');
				// how to work out the primary category on tid?
				$catName = $d7_taxonomy->getTaxonomyDrupal($pc_tid);
				//$remapTaxonomy = new RemapTaxonomy($wp_taxonomy, $options);
				$wpCatName = $wp_taxonomy->remapIOTTaxonomyName($catName);
				// find the category in terms
				$wpCatId = $wp_taxonomy->getTermFromName($wpCatName);
//debug([$pc_tid, $wpCatId, $wpCatName, $catName, $term]);
				if (strlen($wpCatName)) {
					$postmeta->createGetPostMeta($wpPostId, 'primary_category', $wpCatId);
				}
			}

			// for gettting seo data
			$alias = $d7_node->getNodeUrls($d7nodes[0]);
			fputs($ioti_urls, 'ioti.com/' . $alias . "\n");
			// add alias to postmeta: $wpPostId ContentPillarUrl $alias
			if ($wpPostId) {
				$postmeta->createUpdatePostMeta($wpPostId, 'ContentPillarUrl', $alias);
			}


			// field data
			foreach($fieldTables as $fieldDataSource) {

				$gather = new Gather($d7, $fieldDataSource);
				$gather->setNid($nid);

				$tableName = 'field_data_field_' . $fieldDataSource;
				$func = 'get_' . $fieldDataSource;

				//Gather brings in the fields with softdata
				$data = $gather->$func($nid);
				if ($data) {
					// IOTI specific "article types" (they are all being imported as posts)
					$article_types = ['Article', 'Gallery', 'Audio', 'Video', 'Webinar', 'Data Table', 'White Paper', 'Link'];
					if ($data[0] === 'field_penton_article_type') {
						$type_tid = (integer) $data[1]->field_penton_article_type_tid;
						if ($type_tid < 9) {
							$article_type = $article_types[$type_tid - 1];
						}
						$postmeta->createUpdatePostMeta($wpPostId, 'article_type', $article_type);
					}
					if ($data[0] === 'field_penton_native_advertising') {
						$sponsored = (integer) $data[1]->field_penton_native_advertising_value;
						if ($sponsored) {
							$tx = new Taxonomy($wp, $options);
							$term_id = $tx->getSetTerm('Sponsored', 'sponsored', 'Attributes');
							$term_taxonomy_id = (integer) $tx->updateInsertTaxonomy($term_id, 'sponsored');
							$tx->createTermRelationship($term_taxonomy_id, $wpPostId);
						}
					}
				}
			}


			// taxonomies
			$node = $d7_node->getNode($nid);
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

	}
}
status($wp, $wp_posts, $wp_termmeta);

$wp->query($COMMIT);

fclose($newposts);
fclose($ioti_urls);

