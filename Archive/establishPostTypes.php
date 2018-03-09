<?php

require "DB.class.php";

$wp = new DB('wp');
//$d7 = new DB('d7');

$wp->query("SELECT COUNT(*) as c FROM wp_terms");
$items = $wp->getRecord();

if ((integer)$items->c > 1) {
	die("\n" . $items->c . " post types have been established already\n\n");
}

$post_types = [	'article' 		=> 'Article',
		'article_banner' 	=> 'Article Banner',
		'banners'		=> 'Banners',
		'blog'			=> 'Blog',
		'event'			=> 'Event',
		'homepage_tabs'		=> 'Home Page Tabs',
		'job'			=> 'Job',
		'page'			=> 'Page',
		'podcast'		=> 'Podcast',
		'post'			=> 'Post',
		'report'		=> 'Report',
		'slideshow'		=> 'Slideshow',
		'wp-types-group'	=> 'WP Types',
		'wp-types-user-group' 	=> 'WP Types User Group'
];

foreach($post_types as $slug => $name) {
	$sql = "INSERT INTO wp_terms (name, slug) VALUES ('$name', '$slug')";
	$wp->query($sql);
}

$wp->close();

print "Post types established";
