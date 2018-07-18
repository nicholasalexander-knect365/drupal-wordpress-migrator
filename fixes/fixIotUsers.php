<?php
/***
 * fixIotiUsers.php
 * one off script to run on UAT server to assign drupal users to wp_38_capabilities 
 * (they were assigned to wp_capabilites in wp_usermeta)
 */
require "../DB.class.php";
require "../WP.class.php";

require "../Initialise.class.php";
require "../Options.class.php";
require "../User.class.php";
require "../Node.class.php";

// common routines include script initialisation
require "../common.php";

$user = new User($wp, $d7, $options);

// databases are now available as $wp and $d7
$wordpress = new WP($wp, $options, $wp);

/* nodes */
$d7_node = new Node($d7);

// the dusers option does not work with the getAllNodes call below
if ($options->dusers) {
	throw new Exception("\n\n--dusers option is not valid in this script, it is only useful in createWPusers\n\n");
}

$drupal_nodes = $d7_node->getAllNodes();

$drupal_uid = 'drupal_38_uid';

$added = 0;
$exists = [];
$morethanone = 0;

foreach($drupal_nodes as $node) {
	$uid = (integer) $node->uid;
	$nid = (integer) $node->nid;
	$duser = $user->getDrupalUser($uid);

	$sql = "SELECT * FROM field_data_field_penton_author WHERE entity_id=$nid";
	$authorRecords = $d7->records($sql);

	if (isset($authorRecords) && count($authorRecords)) {
		if (count($authorRecords) > 1) {
			$morethanone++;
		}
		$author_id = $authorRecords[0]->field_penton_author_target_id;
		$duser = $user->getDrupalUser($author_id);
	} else {
		$author_id = $duser->uid;
	}

	$email = $duser->mail;
	$drupal_uid_exists = false;

	if (strlen($email)) {
		$wpuser = $user->getWordpressUserByEmail($email);

		//check nicename 
//debug($email);
		$wpusermeta = $user->getUserMeta($wpuser->ID);
		foreach($wpusermeta as $usermeta) {
			if ($usermeta->meta_key === $drupal_uid) {
//debug($usermeta);
				$drupal_uid_exists = $usermeta->umeta_id;
				break;
			}
		}
	} else {
		debug('No email for user ');
		debug($duser);
	}

	if (!$drupal_uid_exists) {
		if ($wpuser->ID) {
			$user->insertOrUpdateUserMeta($wpuser->ID, $drupal_uid, $author_id) ;
			$exists[$author_id] = 1;
			$added++;
		} else {
			debug($wpuser);
			throw new Exception('no wpuser ID?');
		}
	} else {
		if (isset($exists[$author_id])) {
			$exists[$author_id]++;
		}

	}

}

$uexists = count($exists);
if ($added !== $uexists) {
	debug("Users added: " . $added . ' discrepancy with exists check ' . $uexists);
}
die("\n\nEnd of script $added authors added with drupal_38_uid record, articles with more than one author: $morethanone (first one added)\n\n");
