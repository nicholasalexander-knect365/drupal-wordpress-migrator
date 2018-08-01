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
require "Files.class.php";
require "Taxonomy.class.php";

// common routines include script initialisation
require "common.php";

$users = new User($wp, $d7, $options);

$wordpressDBConfig = $wp->wpDBConfig();
$wp->setShowQuery(true);
// databases are now available as $wp and $d7

$wordpress = new WP($wp, $options, $wordpressDBConfig);

/* nodes */
$d7_node = new Node($d7);
$wp_post = new Post($wp, $options);

$wp_taxonomy = new Taxonomy($wp, $options);
$d7_taxonomy = new Taxonomy($d7, $options);

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

while ($line = fgets($newposts)) {
	//print "\n".$line;
	// copy up to the first escaped apos in title
	preg_match("/INSERT (.*?) VALUES \('.+?', '(.*?),/", $line, $matched);
	//var_dump($matched);
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

			$wp->query($line);
			$post_id = $wp->lastInsertId();

			if (empty($post_id)) {
				throw new Exception($line . ' inserted without returning a post_id?');
			}

			$nid = $d7nodes[0]->nid;
			$sql = "INSERT INTO $wp_termmeta (term_id, meta_key, meta_value) VALUES ($wp_termmeta_term_id, $nid, $post_id)";
			$wp->query($sql);

			// summary for excerpt
			$sql = "SELECT field_penton_content_summary_value AS excerpt 
					FROM field_data_field_penton_content_summary 
					WHERE entity_id = $nid";
			//$records = $d7->records($sql);
			$records = $wp->records($sql);

			if (isset($records) && count($records) > 0) {
				$excerpt = $records[0]->excerpt;
				// update the post
				$sql = "UPDATE $wp_posts SET post_excerpt='$excerpt' WHERE ID=$post_id";
				$wp->query($sql);
			} else {
				debug($sql);
				debug('field_penton_content_summary_value did not find a single record!');
				debug($records);
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

					$sql = "UPDATE $wp_posts SET post_author=$authorId WHERE ID=$post_id LIMIT 1";
					$wp->query($sql);
				}
			}

			if (true) {
//debug('>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>taxonomies<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<');
				// taxonomies
				$node = $d7_node->getNode($nid);
				$taxonomies = $d7_taxonomy->nodeTaxonomies($node);
//debug($taxonomies);
				if ($taxonomies && count($taxonomies)) {
					foreach ($taxonomies as $taxonomy) {
//debug($taxonomy);
						$wp_taxonomy->makeWPTermData($taxonomy, $post_id);
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
}
status($wp, $wp_posts, $wp_termmeta);

fclose($newposts);