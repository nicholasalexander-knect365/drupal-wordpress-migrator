<?php

/***
 * replacecContent.php
 * migrator.php script 
 * by Nicholas Alexander for informa Knect365
 * 
 * purpose: to migrate drupal nodes into wp-posts 
 * with no side effects
 * 
 * use: 
 * php replaceContent.php 
 * --server=[staging,vm,local] --wordpressPath=/path/to/wordpress --project=[tuauto.ioti] --clean (strips out styles from html tags)
 */
require "DB.class.php";
require "WP.class.php";

require "Initialise.class.php";
require "Options.class.php";
require "Post.class.php";
require "WPTermMeta.class.php";
require "User.class.php";
require "Node.class.php";
require "Taxonomy.class.php";
require "WPTerms.class.php";

// common routines including script init
require "common.php";
// databases are now available as $wp and $d7
$wordpress = new WP($wp, $options);

/* nodes */
$d7_node = new Node($d7);
$wp_post = new Post($wp);

$wp_terms = new WPTerms($wp, $options);
$wp_termmeta = new WPTermMeta($wp);
$wp_termmeta_term_id = $wp_termmeta->getSetTerm(DRUPAL_WP, DRUPAL_WP);


$wp_terms->removeBlankSlugs();


print "\nCompleted\n\n";

$wp->close();
$d7->close();
