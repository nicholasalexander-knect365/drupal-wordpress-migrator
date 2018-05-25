<?php
/**
 * Taxonomy class
 * for translating D7 to Wordpress
 * taxonomies: dual purpose class 
 * i.e. addresses both Drupal (input) and Wordpress (output) functionality
 */
require_once "DB.class.php";

class Taxonomy {

	public $db;
	public $drupalDB;

	// private $mapped = [
	// 	'category' 					=> 'Category',
	// 	'channel'					=> 'Category',
	// 	'channels'					=> 'Category',
	// 	'article type' 				=> 'Type',
	// 	'tags' 						=> 'post_tag',
	// 	'itunes category' 			=> 'Podcast',
	// 	'weekly brief'				=> 'Brief',
	// 	'upload type'				=> 'Upload',
	// 	'auto mobility'				=> 'Mobility',
	// 	'autonomous car'			=> 'Autonomous',
	// 	'fleet and asset management' => 'Fleet',
	// 	'user experience & hmi' 	=> 'User Experience',
	// 	'insurance & legal'			=> 'Insurance',
	// 	'insurance telematics' 		=> 'Insurance',
	// 	'safety, adas & autonomous'	=> 'ADAS',
	// 	'telematics for evs'		=> 'Electric Vehicles',
	// 	'navigation and lbs'		=> 'Connected Car'
	// ];

	private $termMeta = [
		'category' 	=> 'Category',
		'subject' 	=> 'Subject',
		'type'		=> 'Type',
		'region'	=> 'Region',
		'industry'	=> 'Industry'
	];

	public $terms = [];
	public $options;

	public function __construct($db, $options) {
		$this->db = $db;
		$this->options = $options;
	}

	public function setDrupalDb($db) {
		$this->drupalDB = $db;
	}

	public function __destroy() {
		if ($this->options->verbose) {
			print "\nFinished\n";
		}
	}

	// set the term and return its ID, OR get the ID of the $term_name
	public function getSetTerm($term_name, $term_slug) {

		$wp_terms = DB::wptable('terms');

		$sql = "SELECT COUNT(*) as c FROM $wp_terms WHERE slug='$term_slug'";
		$record = $this->db->record($sql);

		if ($record && $record->c) {
			$sql = "SELECT term_id FROM $wp_terms WHERE slug='$term_slug'";

			$record = $this->db->record($sql);

			return (integer) $record->term_id;

		} else {

			$sql = "INSERT INTO $wp_terms (name, slug, term_group) VALUES ('$term_name', '$term_slug', 0)";

			$this->db->query($sql);
			$term_id = $this->db->lastInsertId();

			return (integer) $term_id;
		}
	}

	// maps taxonomy slug and name
	// TODO: these should be a little softer!
	private function remapNameCategory($name, $slug) {
		switch(trim(strtolower($name))) {
			case 'mobility':
			case 'auto mobility':
				$name = 'Mobility';
				$slug = 'channels';
				break;
			case 'telematics':
				$name = 'Telematics';
				$slug = 'subject';
				break;
			case 'adas':
				$name = 'ADAS';
				$slug = 'category';
				break;
			case 'electric vehicles':
				$name = 'Electric Vehicles';
				$slug = 'category';
				break;
			case 'autonomous':
			case 'autonomous car':
				$name = 'Autonomous';
				$slug = 'channels';
				break;
			case 'infotainment':
				$name = 'Infotainment';
				$slug = 'category';
				break;
			case 'fleet and asset management':
			case 'fleet':
				$name = 'Fleet';
				$slug = 'category';
				break;
			case 'insurance':
			case 'insurance & legal':
			case 'insurance and legal':
			case 'insurance telematics':
				$name = 'Insurance';
				$slug = 'channels';
				break;
			case 'safety, adas & autonomous':
			case 'safety, adas and autonomous':
				$name = 'ADAS';
				$slug = 'category';
				break;
			case 'telematics for evs':
				$name = 'Electric Vehicles';
				$slug = 'category';
				break;
			case 'navigation & lbs':
			case 'navigation and lbs':
			case 'connected car':
				$name = 'Connected Car';
				$slug = 'channels';
				break;
			case 'security':
				$name = 'Security';
				$slug = 'channels';
				break;
		}
		return [$name, $slug];
	}

	// more generic ... maps taxonmy types
	private function remap($taxonomyType) {

		die('private function remap called??');

		return $this->mapped[strtolower($taxonomyType)];

	}


	// TERMS 
	private function termsAlreadyExist() {

		$wp_terms = DB::wptable('terms');

		$this->db->query("SELECT COUNT(*) as c from $wp_terms");
		$item = $this->db->getRecord();
		if ((integer) $item->c > 1) {
			return true;
		}
		return false;
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
		$text = preg_replace('/[^\pL\d]+/', '-', $text);

		// convert text to utf-8 ?? probably not necessary
		// $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);

		// replace anything not a hyphen or word char with nothing
		$text = preg_replace('/[^-\w]+/', '', $text);
		$text = trim($text, '-');

		// replace any multiple dashes with a single hyphen
		$text = preg_replace('/-+/', '-', $text);

		$text = strtolower($text);

		if (empty($text)) {
			throw new Exception("\n\nERROR: slugify with no string?  string passed = $str, slug = $text\n");
		}

		return $text;
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
		return $name;

		$old = $name;
		if (isset($this->mapped[strtolower($name)])) {
			$name = $this->mapped[strtolower($name)];
		}
		return $name;
	}

	public function createTerms($taxonomies) {

		$wp_terms = DB::wptable('terms');
		$wp_term_taxonomy = DB::wptable('term_taxonomy');

		if ($this->options->verbose === true) {
			print "\nCreating " . count($taxonomies) . " taxonomy terms";
		} else if (is_string($this->options->verbose)) {
			print $this->options->verbose;
		}

		foreach ($taxonomies as $taxonomy) {

			$name = $this->makeWPTermName($taxonomy->name);
			$slug = $this->slugify($name);

			$term_group = 0;

			list($lcname, $slug) = $this->remapNameCategory($name, $slug);

			// is there a term for this name?
			$sql = "SELECT term_id FROM $wp_terms WHERE name='$name' AND slug='$slug'";
			$record = $this->db->record($sql);
			if (!$record || !$record->term_id) {

				$sql = "INSERT INTO $wp_terms (name, slug, term_group)
					VALUES ('$name', '$slug', $term_group)";

				$this->db->query($sql);
				$term_id = $this->db->lastInsertId();
			} else {
				$term_id = $record->term_id;
			}

			$this->terms[$taxonomy->type][$this->slugify($lcname)] = $term_id;

			if (strtolower($taxonomy->type) !== 'tags') {
				$sql = "SELECT term_taxonomy_id 
						FROM $wp_term_taxonomy 
						WHERE taxonomy='$slug' AND term_id=$term_id";
				$record = $this->db->record($sql);

				if (!$record) {
					$sql = "INSERT INTO $wp_term_taxonomy (term_id, taxonomy, description, parent, count) VALUES ($term_id, '$slug', '$name', 0, 0)";
				} else {
					$term_taxonomy_id = $record->term_taxonomy_id;
					$sql = "UPDATE $wp_term_taxonomy SET count = count+1 WHERE term_taxonomy_id = $term_taxonomy_id";
				}

//debug($sql);
				$this->db->query($sql);
			}
		}

	}

	/*
	* D7 only ... get the Drupal taxonomyList
	*/
	public function fullTaxonomyList() {

		$taxonomyNames = [];
		$sql = 'SELECT DISTINCT td.tid, td.vid, td.name, v.name AS type
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
	private function makeTermMeta($term_id, $name, $description, $postId) {

			$wp_termmeta = DB::wptable('termmeta');

			$meta_key = addslashes($name);
			$meta_value = addslashes($description);

			$sql = "INSERT INTO $wp_termmeta (term_id, meta_key, meta_value)
					VALUES ($term_id, '$meta_key', '$meta_value')";

			$this->db->query($sql);
	}

	// wp only
	private function makeTermRelationship($taxonomy, $term_taxonomy_id, $postId) {

		$wp_term_relationships = DB::wptable('term_relationships');

		$term_order = $taxonomy->weight;
		// create a termRelation
		$sql = "INSERT INTO $wp_term_relationships (object_id, term_taxonomy_id, term_order)
				VALUES ($postId, $term_taxonomy_id, $term_order)";
		$this->db->query($sql);
	}

	// NB: taxonomy is a DRUPAL record nid/tid/vid/name/description/type (category)
	private function makeTermTaxonomy($taxonomy) {

		$wp_term_taxonomy = DB::wptable('term_taxonomy');
		$wp_terms = DB::wptable('terms');

		$name = $this->makeWPTermName($taxonomy->name);
		$slug = $this->slugify($this->makeWPTermName($taxonomy->category));
		if ($slug === 'post-tag') {
			$slug = 'post_tag';
		}
//debug([$name, $slug]);

		//list($name, $slug) = $this->remapNameCategory($name, $slug);

//debug([$name, $slug]);

		if (strlen($taxonomy->description)) {
			$description = $taxonomy->name . ' ' . $taxonomy->description;
		} else {
			$description = $taxonomy->name;
		}

		if (isset($this->terms[$taxonomy->category][$this->slugify($name)])) {
			$term_id = $this->terms[$taxonomy->category][$this->slugify($name)];
		} else {
			$term_id = null;
		}

// if (!$term_id) {
// 	debug($taxonomy);
// 	debug($name);
// 	debug('term ID not found in array:');
// 	debug($this->terms);
// 	dd($this->terms[$taxonomy->category][$this->slugify($name)]);
// }

		$format = $taxonomy->format;
		$weight = $taxonomy->weight;
		$parent = $taxonomy->hierarchy;

		$record = null;
		// does the taxonomy exist, if so increase count
		$sql = "SELECT term_taxonomy_id 
				FROM   $wp_term_taxonomy 
				WHERE  term_id = $term_id 
				  AND  taxonomy = '$slug'";
		$record = $this->db->record($sql);

		if (empty($record)) {
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
//debug($sql);
		return $term_taxonomy_id;
	}

	// wp only
	public function makeWPTermData($taxonomy, $postId) {
//debug('makeWPTermData');debug($taxonomy);
		$termData = $this->getTermData($taxonomy);
		$term_id = $taxonomy->tid;

		if ($this->options->verbose > 1) {
			print "\nMaking Wordpress Term Data for $term_id";
		}

		// if (strtolower($taxonomy->category) === 'tags') {
		// 	$taxonomy->slug = 'tag';
		// }

		$term_taxonomy_id = $this->makeTermTaxonomy($taxonomy);
		$this->makeTermRelationship($taxonomy, $term_taxonomy_id, $postId);

	}

	// testing
	public function getTaxonomyList($type) {
		switch ($type) {
			case 'channels':
				$taxonomies = ['connected-car',  'insurance', 'autoonmous', 'mobility', 'security',];
				break;
			case 'category';
				$taxonomies = [ 'fleet', 'infotainment', 'adas', 'telematics', 'electric-vehicles'];
				break;
			case 'subject':
				$taxonomies = ['user-experience-hmi', 'connected-car', 'security', 'data-analytics', 'artificial-intelligence', 'smart-cities', 'digital-transformation', 'investment-ma', 'regulation', 'commercial-vehicle'];
				break;
			case 'type':
				$taxonomies = ['industry-insight', 'executive-viewpoint' ,'press-release', 'intelligence-type'];
				break;
			case 'brief':
				$taxonomies = ['yes', 'no'];
				break;
			default:
				die("\n" . $type . ' in testTaxonomyTypeExists has no implementation');
		}
		return $taxonomies;
	}

	public function getTags() {
		
		$wp_terms = DB::wptable('terms');
		$wp_term_taxonomy = DB::wptable('term_taxonomy');

		$sql = "SELECT t.*, wtt.* FROM $wp_terms t
				LEFT JOIN $wp_term_taxonomy wtt ON t.term_id=wtt.term_id
				WHERE wtt.taxonomy = 'post_tag'";
		$records = $this->db->records($sql);
		return $records;
	}

	// establishment of a taxonomy term
	public function tagExists($search) {

		$wp_term_taxonomy = DB::wptable('term_taxonomy');

		$sql = "SELECT COUNT(*) AS c 
				FROM wp_terms t
				LEFT JOIN $wp_term_taxonomy wtt ON t.term_id=wtt.term_id
				WHERE t.slug LIKE '$search' AND wtt.taxonomy = 'post_tag'";

		$record = $this->db->record($sql);
		if (is_null($record)) {
			return 0;
		}
		return (integer) $record->c;
	}

	// establishment of a taxonomy term
	public function termExists($search, $taxonomy) {

		$wp_terms = DB::wptable('terms');
		$wp_term_taxonomy = DB::wptable('term_taxonomy');

		$sql = "SELECT COUNT(*) AS c 
				FROM $wp_terms t
				LEFT JOIN $wp_term_taxonomy wtt ON t.term_id=wtt.term_id
				WHERE t.slug LIKE '$taxonomy' AND wtt.taxonomy <> 'post_tag'";

		$record = $this->db->record($sql);
		if (is_null($record)) {
			return 0;
		}
		return (integer) $record->c;
	}

	// 2. uses of a specific taxonomy without tags
	public function countTaxonomyUse($taxonomy) {

		$wp_term_relationships = DB::wptable('term_relationships');
		$wp_term_taxonomy = DB::wptable('term_taxonomy');
		$wp_terms = DB::wptable('terms');
		$wp_posts = DB::wptable('posts');

		$sql = "SELECT COUNT(*) AS c FROM $wp_posts post
				INNER JOIN $wp_term_relationships wr ON wr.object_id=post.ID
				LEFT JOIN $wp_term_taxonomy wtt ON wr.term_taxonomy_id=wtt.term_id
				LEFT JOIN $wp_terms t ON t.term_id=wtt.term_id
				WHERE t.slug LIKE '$taxonomy' and wtt.taxonomy <> 'post_tag'";

		$record = $this->db->record($sql);
		if (is_null($record)) {
			return 0;
		}
		return (integer) $record->c;
	}
}
