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

configuration($options);


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

// $sql = "SELECT n.nid, db.body_value as body FROM node n WHERE type = 'article' JOIN field_data_body db ON db.entity_id=n.nid";
// $nodes = $d7->records($sql);

// dd($sql);
// dd($nodes);

$posts = $wp_post->getPosts();
$count = 0;
$tests = false;
$debug = false;
$matched = [];
$wordpressDirectory = sprintf('blogs.dir/%d/files', (integer) $options->siteId);
$addMedia = fopen('addMedia.sh', 'w');

$BEGIN_TRANS = "START TRANSACTION";
$COMMIT = "COMMIT";

$wp->query($BEGIN_TRANS);

foreach($posts as $post) {

	$body = $post->post_content;
	$post_id = $post->ID;

	if ($tests) {
		if ( preg_match_all('/<img src="(.*?)"/', $body, $matchSet) ) {
			//dd($matchSet);
			foreach($matchSet[1] as $matched) {
				debug($matched . '  '.$post->post_title);
			}
		}
		if ( preg_match('/<img src="http:\/\/www.ioti.com/', $body) ) {
			//dd($body);
		}
	} else {

		// wget the image
		$m2 = preg_match_all('/<img src\=\"(http\:\/\/iot\-institute.com\/)(.*?)"/', $body, $matched2);

		// make all paths www.ioti.com
		foreach($matched2 as $n => $img) {
			$body = preg_replace('/<img(.*?)src\=\"http\:\/\/iot\-institute.com\/(.*?)\"(.*?)>/', '<img ${1} src="http://www.ioti.com/${2}"${3}">', $body);
		}

		$m1 = preg_match_all('/<img.*?src\=\"(http\:\/\/www.ioti.com\/(.*?))".*?>/', $body, $matched);

		if (0 && $m1 > 0) {
			debug($matched);
		}

		if (0 && $m2 > 0) {
			debug($matched2);
		}

		$elements = [];
		$urls = [];
		$filenames = [];
		$imgs = [];

		if (count($matched)) {
			foreach($matched as $n => $imgGroups) {
				if (count($imgGroups)) {
					foreach($imgGroups as  $img) {
						switch($n) {
							case 0:
								// full paths
								$elements[] = $img;
								break;
							case 1:
								// url
								$urls[] = preg_replace('/http:/', 'https:', $img);
								break;
							case 2:
								// full tail
								$filenames[] = $img;
								break;
						}
					}
				}
			}

			// get the file
			foreach($urls as $n => $ext_url) {

				try {
					$url = basename($ext_url);

					// real location where image should end up running wp media import
					$blogsdirPath = $options->wppath . '/blogs.dir/files/2018/08/';
					$imagePath = '/files/2018/08/' . $url;
					$blogsdir = 'imageset';
//debug($ext_url);

					$ch = curl_init(); 
					curl_setopt($ch, CURLOPT_URL, $url); 
					curl_setopt($ch, CURLOPT_HEADER, false); 
					curl_setopt($ch, CURLOPT_NOBODY, TRUE); // remove body 
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE); 

					$filecontent = curl_exec($ch);
					$fd = fopen( $blogsdir . '/' . basename($url), 'w+');

					if ($fd) {
						fputs($fd, $filecontent);
						fclose($fd); 
						//debug('FILE obtained '.$url);
					} else {
						throw new Exception('can curl file' . $url );
					}
				} catch (Exception $e) {
					debug('ERROR: could not copy '.$url);
				}

				// build the URL and replace it in the text
				$filename = $filenames;
				$post_id = $post->ID;
				$wppath = $options->wppath;
				$wpurl = $options->wpurl;

				$cmd = "wp media import '$blogsdir/$url' --post_id=$post_id --path=$wppath --url=$wpurl --title=\"$url\"";

				fputs($addMedia, $cmd);

				// create the media library entries
			}
		}

		//$newbody = preg_replace(['/(<img(.*?)src=")(http:\/\/www.ioti.com)(.*?)">/', '/(<img(.*?)src=")(http:\/\/iot-institute.com)(.*?)">/'],'${1}http://www.iotworldtoday.com${4}', $body);

		$newbody = preg_replace(	'/(<img.*?src=")(http:\/\/www.ioti.com\/)(.*?)"(.*?)>/',
									'${1}http://www.iotworldtoday.com'.$imagePath.'"${4}>', $body);

		if ($debug) {
			if ($body !== $newbody) {
				$count++;
				debug('====================================');
				debug($body);
				debug('------------------------------------');
				debug($newbody);
				debug('====================================');
			}
		}
	}
	// rewrite the post content
	if (strlen($newbody)) {
		$wp_post->updatePost($post_id, 'body', $newbody);
	}
}

$wp->query($COMMIT);

fclose($addMedia);

print "\n\nInstances: $count";


function configuration($options) {

	switch ($options->server) {
		case 'multisite' : 
			$options->wppath = '/home/nicholas/Dev/wordpress/multisite';
			$options->wpurl = 'http://ioti.multisite.local';
			break;
		case 'staging' :
			$options->wppath = '/srv/www/test2.telecoms.com';
			$options->wpurl = 'http://ioti.test2.telecoms.com';
			break;
		case 'live': 
			$options->wppath = '/srv/www/telecoms.com';
			$options->wpurl = 'https://www.iotworldtoday.com';
			break;
		default: 
			die('Set a server option with --server=multisite/staging/live');
	}

}