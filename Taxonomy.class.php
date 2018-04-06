<?php
/**
 * Taxonomy class
 * for translating D7 to Wordpress
 * taxonomies: dual purpose class
 * call with DB constructor
 * v0.6 works with the result of FG plugin PRO
 * 		imports
 */
require_once "DB.class.php";

class Taxonomy {

	public $db;
	public $drupalDB;
	private $verbose;
	private $initialise_regardless;

	private $mapped = [
		'category' 					=> 'Category',
		'channel'					=> 'Category',
		'channels'					=> 'Category',
		'article type' 				=> 'Type',
		'tags' 						=> 'Tag',
		'itunes category' 			=> 'Podcast',
		'weekly brief'				=> 'Brief',
		'upload type'				=> 'Upload',
		'auto mobility'				=> 'Mobility',
		'autonomous car'			=> 'Autonomous',
		'fleet and asset management' => 'Fleet',
		'user experience & hmi' 	=> 'User Experience',
		'insurance & legal'			=> 'Insurance',
		'insurance telematics' 		=> 'Insurance',
		'safety, adas & autonomous'	=> 'ADAS',
		'telematics for evs'		=> 'Electric Vehicles',
		'navigation and lbs'		=> 'Uncategorised'


	];

	private $termMeta = [
		'category' 	=> 'Category',
		'subject' 	=> 'Subject',
		'type'		=> 'Type',
		'region'	=> 'Region',
		'industry'	=> 'Industry'
	];

	public $terms = [];


	public function __construct($db, $verbose = true) {
		$this->db = $db;
		$this->verbose = $verbose;
		$this->initialise_regardless = false;
	}

	public function setDrupalDb($db) {
		$this->drupalDB = $db;
	}

	public function __destroy() {
		if ($this->verbose) {
			print "\nFinished\n";
		}
	}

	public function setVerbose() {
		$this->verbose = true;
	}
	public function isVerbose() {
		return $this->verbose;
	}

	public function initialise($init = false) {

		$this->initialise_regardless = $init;

		if ($this->initialise_regardless && $this->termsAlreadyExist()) {
			$this->cleanUp();
		} else {
			return;
		}
	}

	private function removeTerms() {

		$wp_terms = DB::wptable('terms');

		$sql = "DELETE FROM $wp_terms WHERE term_id>1";
		$this->db->query($sql);
		$sql = "ALTER TABLE $wp_terms AUTO_INCREMENT = 2";
		$this->db->query($sql);
	}

	private function cleanUp() {
		if ($this->verbose) {
			print "\nCleaning up...";
		}
		$this->removeTerms();

		$wp_termmeta = DB::wptable('termmeta');
		$wp_term_taxonomy = DB::wptable('term_taxonomy');
		$wp_term_relationships = DB::wptable('term_relationships');

		$sql = "DELETE FROM $wp_termmeta";
		$this->db->query($sql);
		$sql = "ALTER TABLE $wp_term_taxonomy AUTO_INCREMENT = 1";
		$this->db->query($sql);

		$sql = "DELETE FROM $wp_term_taxonomy";
		$this->db->query($sql);
		$sql = "ALTER TABLE $wp_term_taxonomy AUTO_INCREMENT = 1";
		$this->db->query($sql);

		$sql = "DELETE FROM $wp_term_relationships";
		$this->db->query($sql);
		$sql = "ALTER TABLE $wp_term_relationships AUTO_INCREMENT = 1";
		$this->db->query($sql);
	}

	private function termsAlreadyExist() {

		$wp_terms = DB::wptable('terms');

		$this->db->query("SELECT COUNT(*) as c from $wp_terms");

		$item = $this->db->getRecord();
		if ($item->c > 1) {
			return true;
		}
	}

	/**
	 * checkTerms in Wordpress
	 */
	public function checkTerms() {

		$wp_term_taxonomy = DB::wptable('term_taxonomy');

		$sql = "SELECT COUNT(*) AS c FROM $wp_term_taxonomy";
		$this->db->query($sql);
		$items = $this->db->getRecord();
		return ($items->c === 1);
	}

	static public function slugify($str) {
		$text = $str;
		// replace non letter or digits by -
		$text = preg_replace('~[^\pL\d]+~u', '-', $text);
		$text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
		$text = preg_replace('~[^-\w]+~', '', $text);
		$text = trim($text, '-');
		$text = preg_replace('~-+~', '-', $text);
		$text = strtolower($text);
		if (empty($text)) {
			return '';
		}
		return $text;
	}


	private function remap($taxonomyType) {
		return $this->mapped[strtolower($taxonomyType)];

	}

	// D7 only
	public function getVocabulary() {
		$sql = "SELECT vid, name, machine_name, description, hierarchy, module, weight
				FROM taxonomy_vocabulary
				ORDER BY weight";
		$vocabulary = $this->db->records($sql);
		return $vocabulary;
	}

	private function makeWPTermName($name) {
		$old = $name;
		if (isset($this->mapped[strtolower($name)])) {
			$name = $this->mapped[strtolower($name)];
		}
		//debug($old . ' => ' . $name);
		return $name;
	}

	public function createTerms($taxonomies) {

		$wp_terms = DB::wptable('terms');

		if ($this->termsAlreadyExist()) {
			$this->removeTerms();
		}

		//debug('creating terms');

		if ($this->verbose === true) {
			print "\nCreating " . count($taxonomies) . " taxonomy terms";
		} else if (is_string($this->verbose)) {
			print $this->verbose;
		}

		foreach ($taxonomies as $taxonomy) {

			if (strtolower($taxonomy->type) !== 'tags') {

				$name = $this->makeWPTermName($taxonomy->name);
				$slug = $this->slugify($name);
				$term_group = 0;

				$sql = "INSERT INTO $wp_terms (name, slug, term_group)
						VALUES ('$name', '$slug', $term_group)";

				$this->db->query($sql);
				$this->terms[$slug] = $this->db->lastInsertId();
			}
		}

	}

	/*
	* D7 only ... get the Drupal taxonomyList
	*/
	public function fullTaxonomyList() {

		$taxonomyNames = [];
		$sql = 'SELECT distinct td.tid, td.vid, td.name, v.name AS type
				FROM taxonomy_term_data td
				LEFT JOIN taxonomy_vocabulary v ON td.vid=v.vid';

		$records = $this->db->records($sql);

		return $records;
	}

	/**
	 * D7 only ... full node taxonomy
	 */
	public function nodeTaxonomies($node) {
		$nid = $node->nid;
		$sql = "SELECT 	ti.nid as nid,
						ti.tid as tid,
						td.vid as vid,
						td.name as name,
						td.description as description,
						td.weight as weight,
						tv.name as category,
						td.format as format,
						tv.hierarchy as hierarchy
				FROM taxonomy_index ti
				INNER JOIN taxonomy_term_data td ON td.tid=ti.tid
				INNER JOIN taxonomy_vocabulary tv ON tv.vid=td.vid
				WHERE nid=$nid";

		$taxonomies = $this->db->records($sql);

		return $taxonomies;
	}

	// private function getPostId($nid) {
	// 	$sql = "SELECT pm.post_id
	// 			FROM wp_postmeta pm
	// 			WHERE pm.meta_value=$nid AND meta_key='_fgd2wp_old_node_id'";

	// 	$wp->query($sql);
	// 	$post = $wp->getRecord();
	// 	return $post->post_id;
	// }


	// D7 only ... build the taxonomy term_data from drupal
	private function getTermData($taxonomy) {

		$termData = [];
		$tid = $taxonomy->tid;

		$sql = "SELECT * FROM taxonomy_term_data
				WHERE tid = $tid
				ORDER BY tid";

		if (!$this->drupalDB) {
			$termData = $this->db->records($sql);
		} else {
			$termData = $this->drupalDB->records($sql);
		}

		return $termData;
	}

	// wp only
	private function getTermFromSlug($slug) {

		$wp_terms = DB::wptable('terms');

		$sql = "SELECT term_id FROM $wp_terms WHERE slug = '$slug' LIMIT 1";
		$this->db->query($sql);
		$term = $this->db->getRecord();
		return $term;
	}

	// wp only
	private function makeTermMeta($term_id, $name, $description) {
			$wp_termmeta = DB::wptable('termmeta');
			$meta_key = addslashes($name);
			$meta_value = addslashes($description);

			$sql = "INSERT INTO $wp_termmeta (term_id, meta_key, meta_value)
					VALUES ($term_id, '$meta_key', '$meta_value')";

			$this->db->query($sql);
	}

	// wp only
	private function makeTermRelationship($taxonomy, $term_taxonomy_id) {

		$wp_term_relationships = DB::wptable('term_relationships');

		$posts = $this->findPosts($taxonomy->nid);

		foreach ($posts as $post) {
			$postId = $post->post_id;
			$term_order = $taxonomy->weight;
			// create a termRelation
			$sql = "INSERT INTO $wp_term_relationships (object_id, term_taxonomy_id, term_order)
					VALUES ($postId, $term_taxonomy_id, $term_order)";
			$this->db->query($sql);
		}
	}

	private function makeTermTaxonomy($taxonomy) {

		$wp_term_taxonomy = DB::wptable('term_taxonomy');

		$name = $this->makeWPTermName($taxonomy->name);
		$slug = $this->slugify($this->makeWPTermName($taxonomy->category));
		$term_id = $this->terms[$this->slugify($name)];

		if (strlen($taxonomy->description)) {
			$description = $taxonomy->name . ' ' . $taxonomy->description;
		} else {
			$description = $taxonomy->name;
		}
		$format = $taxonomy->format;
		$weight = $taxonomy->weight;
		$parent = $taxonomy->hierarchy;

		// does the taxonomy exist, if so increase count
		$sql = "SELECT term_taxonomy_id 
				FROM   $wp_term_taxonomy 
				WHERE  term_id = $term_id 
				  AND taxonomy = '$slug'";
		$this->db->query($sql);

		$record = $this->db->getRecord();
// debug($taxonomy);
// debug($slug);
		if (!$record) {
			$sql = "INSERT INTO $wp_term_taxonomy (term_id, taxonomy, description, parent, count) VALUES ($term_id, '$slug', '$description', $parent, 0)";
			$this->db->query($sql);
			$term_taxonomy_id = $this->db->lastInsertId();

		} else {

			$term_taxonomy_id = $record->term_taxonomy_id;
			$sql = "UPDATE $wp_term_taxonomy 
					SET count=count+1 
					WHERE term_taxonomy_id=$term_taxonomy_id";
			$this->db->query($sql);
		}
		return $term_taxonomy_id;
	}

	// wp only
	public function makeWPTermData($taxonomy) {

		$termData = $this->getTermData($taxonomy);
		$term_id = $taxonomy->tid;

		if ($this->verbose > 1) {
			print "\nMaking Wordpress Term Data for $term_id";
		}

		if (strtolower($taxonomy->category) === 'tags') {

			$name = 'post_tag';
			$description = $taxonomy->name;
			$this->makeTermMeta($term_id, $name, $description);

		} else {

			$term_taxonomy_id = $this->makeTermTaxonomy($taxonomy);
			$this->makeTermRelationship($taxonomy, $term_taxonomy_id);

		}

	}

	private function findPosts($nid) {

		$wp_postmeta = DB::wptable('postmeta');

		$sql = "SELECT pm.post_id, pm.meta_value
				FROM $wp_postmeta pm
				WHERE pm.meta_value=$nid AND meta_key='_fgd2wp_old_node_id'";

		$this->db->query($sql);
		$posts = $this->db->getRecords();
		return $posts;
	}

}
