<?php

class Initialise {

	private static function removeTerms($db) {

		$wp_terms = DB::wptable('terms');

		$sql = "DELETE FROM $wp_terms WHERE term_id>1";
		$db->query($sql);
		$sql = "ALTER TABLE $wp_terms AUTO_INCREMENT = 2";
		$db->query($sql);
	}

	public static function cleanUp($db, $verbose = true) {
		if ($verbose) {
			print "\nCleaning up...";
		}
		static::removeTerms($db);

		$wp_posts = DB::wptable('posts');
		$wp_termmeta = DB::wptable('termmeta');
		$wp_term_taxonomy = DB::wptable('term_taxonomy');
		$wp_term_relationships = DB::wptable('term_relationships');

		$sql = "DELETE FROM $wp_posts";
		$db->query($sql);


		$sql = "DELETE FROM $wp_termmeta";
		$db->query($sql);
		$sql = "ALTER TABLE $wp_term_taxonomy AUTO_INCREMENT = 1";
		$db->query($sql);

		$sql = "DELETE FROM $wp_term_taxonomy";
		$db->query($sql);
		$sql = "ALTER TABLE $wp_term_taxonomy AUTO_INCREMENT = 1";
		$db->query($sql);

		$sql = "DELETE FROM $wp_term_relationships";
		$db->query($sql);
		$sql = "ALTER TABLE $wp_term_relationships AUTO_INCREMENT = 1";
		$db->query($sql);
	}

	public static function purge($db) {

		$wp_posts = DB::wptable('posts');

		$sql = "DELETE FROM $wp_posts";
		$db->query($sql);

		$sql = "ALTER TABLE $wp_posts AUTO_INCREMENT = 1";
		$db->query($sql);
	}
}