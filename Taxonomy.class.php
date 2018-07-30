<?php
/**
 * Taxonomy class
 * for translating D7 to Wordpress
 * taxonomies: dual purpose class 
 * i.e. addresses both Drupal (input) and Wordpress (output) functionality
 */
require_once "DB.class.php";
require_once "RemapTaxonomy.class.php";

class Taxonomy {

	public $db;
	public $drupalDB;

	private $termMeta = [
		'category' 	=> 'Category',
		'subject' 	=> 'Subject',
		'type'		=> 'Type',
		'region'	=> 'Region',
		'industry'	=> 'Industry'
	];

	public $terms = [];
	public $options;

	private $counter = 0;

	public function __construct($db, $options) {
		$this->db = $db;
		$this->options = $options;
	}

	public function __destroy() {
		if ($this->options->verbose) {
			print "\nFinished\n";
		}
	}

	//TODO TECH-DEBT: terms are NOT unique by SLUG!!!!
	// they must be unique by SLUG and TERM_TAXONOMY

	// set the term and return its ID, OR get the ID of the $term_name
	public function getSetTerm($term_name, $term_slug, $taxonomy = null) {

		$wp_terms = DB::wptable('terms');
		$wp_term_taxonomy = DB::wptable('term_taxonomy');

		$sql = "SELECT COUNT(*) as c FROM $wp_terms WHERE slug='$term_slug'";

		$record = $this->db->record($sql);

		if ($record && (integer) $record->c === 1) {

			$sql = "SELECT term_id FROM $wp_terms WHERE slug='$term_slug'";

			$record = $this->db->record($sql);
			return (integer) $record->term_id;

		} else if ($record && (integer) $record->c > 1) {

			// TODO: investigate potential for bug where it keeps adding 
			//       because there is more than one term with the same name
			if ($taxonomy) {

				$sql = "SELECT * FROM $wp_terms t INNER JOIN $wp_term_taxonomy tt ON tt.term_id=t.term_id 
						WHERE tt.taxonomy='$taxonomy' AND t.slug='$term_slug'";
				$records = $this->db->records($sql);
				$number_found = count($records);

				if ($number_found > 1) {
					debug(DB::strip($sql));
					debug($records);
					throw new Exception("ERROR: Can not isolate a term for $term_slug with taxonomy $taxonomy");
				} if ($number_found === 1) {
					$term_id = (integer) $records[0]->term_id;
				} else {
					$sql = "INSERT INTO $wp_terms (name, slug, term_group) VALUES ('$term_name', '$term_slug', 0)";
					$this->db->query($sql);
					$term_id = $this->db->lastInsertId();
				}

			} else {
				throw new Exception("\n\nERROR: can not getSet term $term_slug without a taxonomy as multiple instances of the term slug already exist");
			}

		} else {

			$sql = "INSERT INTO $wp_terms (name, slug, term_group) VALUES ('$term_name', '$term_slug', 0)";
			$this->db->query($sql);
			$term_id = $this->db->lastInsertId();
		}

		return (integer) $term_id;
	}

	public function remapIOTTaxonomyName($taxonomy) {

		$remap = new RemapTaxonomy($this->db, $this->options);

		switch ($this->options->project) {
			case 'ioti':
			case 'iotworldtoday':
				$taxonomies = $remap->IOTIRemapNameCategory($taxonomy);
				break;
		}

		if (isset($taxonomies['subject'])) {
			return $taxonomies['subject'];
		}
		return null;
	}

	protected function remapNameCategory($name) {

		// if no taxonomy or not reconised, it may be a post_tag
		$taxonomy = 'post_tag';
		$remap = new RemapTaxonomy($this->db, $this->options);

		switch ($this->options->project) {
			case 'tuauto':
				list($name, $taxonomy) = $remap->TUAutoRemapNameCategory($name);
				return [$taxonomy => $name];
				break;

			case 'ioti':
			case 'iotworldtoday':
				$taxonomies = $remap->IOTIRemapNameCategory($name);
				return $taxonomies;

				break;
			default: 
				throw new Exception('Can not remap for this project ' . $this->options->project);
		}
	}

	private function termsAlreadyExist() {

		$wp_terms = DB::wptable('terms');

		$this->db->query("SELECT COUNT(*) as c from $wp_terms");
		$item = $this->db->getRecord();
		if ((integer) $item->c > 1) {
			return true;
		}
		return false;
	}

	public function checkTermTaxonomyExists() {

		$wp_term_taxonomy = DB::wptable('term_taxonomy');

		$sql = "SELECT COUNT(*) AS c FROM $wp_term_taxonomy";
		$this->db->query($sql); 
		$items = $this->db->getRecord();

		if ($items->c === 0) {
			debug('Term Taxonomy is empty');
		}
		return ($items->c > 0);
	}

	static public function slugify($str) {

		$text = $str;

		// replace non letter or digits by -
		$text = preg_replace('/[^\pL\d]+/', '-', $text);

		// convert text to utf-8 ?? appears to not be necessary
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

	private function makeWPTermName($name) {
		return addslashes($name);

		$old = $name;
		if (isset($this->mapped[strtolower($name)])) {
			$name = $this->mapped[strtolower($name)];
		}
		return $name;
	}

	private function createTerm($name, $slug) {
		
		$wp_terms = DB::wptable('terms');
		$slug = $this->slugify($name);

		$term_group = 0;

		$sql = "INSERT INTO $wp_terms (name, slug, term_group)
				VALUES ('$name', '$slug', $term_group)";
		$this->db->query($sql);
		$term_id = $this->db->lastInsertId();
		return $term_id;
	}

	private function getTaxonomyRecord($term_id, $taxonomy) {
		
		$wp_term_taxonomy = DB::wptable('term_taxonomy');

		$sql = "SELECT term_taxonomy_id 
				FROM $wp_term_taxonomy 
				WHERE taxonomy='$taxonomy' AND term_id=$term_id";
		$record = $this->db->record($sql);
		return $record;
	}

	public function resetTermCounts() {
		$wp_term_taxonomy = DB::wptable('term_taxonomy');
		$sql = "UPDATE $wp_term_taxonomy SET count=0";
		$this->db->query($sql);
		//$rowAffected = $this->db->rowsAffected();
		return true;
	}

	public function loadTerms($taxonomies) {
		$wp_terms = DB::wptable('terms');
		$wp_term_taxonomy = DB::wptable('term_taxonomy');
		$sql = "SELECT * FROM $wp_terms";
		$records = $this->db->records($sql);
		$this->terms = [];
		foreach($records as $n => $record) {
			$taxname = $record->name;
			$slug = $this->slugify($name);
			$this->terms[$taxname][$slug] = $term_id;
		}
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

			$taxonomy_name = $this->makeWPTermName($taxonomy->name);
//debug($name);
			if (strlen($taxonomy_name)) {

				$term_group = 0;
				$taxonomySet = $this->remapNameCategory($taxonomy_name);

				foreach ($taxonomySet as $taxname => $name) {

					$slug = $this->slugify($name);
					$term_id = $this->getSetTerm($name, $slug, $taxname);

					$this->terms[$taxname][$slug] = $term_id;

					$record = $this->getTaxonomyRecord($term_id, $taxname);
//debug($record);
					if ($record === NULL) {
						$sql = "INSERT INTO $wp_term_taxonomy (term_id, taxonomy, description, parent, count) VALUES ($term_id, '$taxname', '$name', 0, 1)";
					// DO NOT INCREMENT COUNTS FOR MULTIPLE INSTANCES - THESE ARE INSTANCES OF TAXONOMY not NODES!
					// } else {
					// 	$term_taxonomy_id = $record->term_taxonomy_id;
					// 	$sql = "UPDATE $wp_term_taxonomy SET count = count+1 WHERE term_taxonomy_id = $term_taxonomy_id";
					// }
//debug($sql);
					$this->db->query($sql);
					}
				}
			} else {
				debug($taxonomy);
				throw new Exception("Taxonomy does not have a name?");
			}
		}
	}

	public function getTermFromSlug($slug) {

		$wp_terms = DB::wptable('terms');

		$sql = "SELECT * FROM $wp_terms WHERE slug = '$slug' LIMIT 1";
		$this->db->query($sql);
		$term = $this->db->getRecord();
		if ($term) {
			return $term;
		} 
		return null;
	}

	public function getTermFromName($name) {
		$wp_terms = DB::wptable('terms');

		$sql = "SELECT term_id FROM $wp_terms WHERE name like '$name' LIMIT 1";
		$this->db->query($sql);
		$term = $this->db->getRecord();
		if (isset($term) && $term->term_id) {
			return $term->term_id;
		}
		return null;
	}

	private function makeTermMeta($term_id, $name, $description, $postId) {

			$wp_termmeta = DB::wptable('termmeta');

			$meta_key = addslashes($name);
			$meta_value = addslashes($description);

			$sql = "INSERT INTO $wp_termmeta (term_id, meta_key, meta_value)
					VALUES ($term_id, '$meta_key', '$meta_value')";

			$this->db->query($sql);
	}

	public function createTermRelationship($term_taxonomy_id, $postId) {

		$wp_term_relationships = DB::wptable('term_relationships');
		$term_order = 0;

		$sql = "INSERT INTO $wp_term_relationships (object_id, term_taxonomy_id, term_order)
				VALUES ($postId, $term_taxonomy_id, $term_order)";

		$this->db->query($sql);
	}

	private function makeTermRelationship($taxonomy, $term_taxonomy_id, $postId, $clearOlds = false) {

		$wp_term_relationships = DB::wptable('term_relationships');

		$term_order = $taxonomy->weight;
		// create a termRelation

		if ($clearOlds) {
			$sql = "DELETE FROM $wp_term_relationships WHERE term_taxonomy_id=$term_taxonomy_id AND object_id=$postId";
			$this->db->query($sql);
		}
		$sql = "INSERT INTO $wp_term_relationships (object_id, term_taxonomy_id, term_order)
				VALUES ($postId, $term_taxonomy_id, $term_order)";
		$this->db->query($sql);
	}

	// NB: taxonomy is a DRUPAL record nid/tid/vid/name/description/type (category)
	private function makeTermTaxonomy($taxonomy, $postId) {

		$wp_term_taxonomy = DB::wptable('term_taxonomy');
		$wp_terms = DB::wptable('terms');

		$name = $this->makeWPTermName($taxonomy->name);

		$taxonomies = $this->remapNameCategory($name);

		foreach($taxonomies as $taxname => $name) {

			$slug = $this->slugify($name);

			if (strlen($taxonomy->description)) {
				$description = $taxonomy->name . ' ' . $taxonomy->description;
			} else {
				$description = $taxonomy->name;
			}

			if ($this->options->verbose) {
				debug([$taxname, $slug]);
			}

			if ($term = $this->getTermFromSlug($slug)) {
				$term_id = $term->term_id;
			} else {
				if ($this->options->verbose) {
					debug('makeTermTaxonomy term created with:');
					debug([$name, $slug]);
				}
				$term_id = $this->createTerm($name, $slug);
			}

			$format = $taxonomy->format;
			$weight = $taxonomy->weight;
			$parent = $taxonomy->hierarchy;

			$term_taxonomy_id = $this->updateInsertTaxonomy($term_id, $taxname, $parent, $description);

			if ($this->options->verbose) {
				debug($this->counter++  . ") $taxname term_id:$term_id for " . $slug . ' term_taxonomy_id:'. $term_taxonomy_id . ' postId:'.$postId);
			}

			$this->makeTermRelationship($taxonomy, $term_taxonomy_id, $postId, TRUE);

		}
	}

	// wordpress
	public function makeWPTermData($taxonomy, $postId) {
		$this->makeTermTaxonomy($taxonomy, $postId);
		
	}

	public function updateInsertTaxonomy($term_id, $taxname, $parent = 0, $description = '') {

		$wp_term_taxonomy = DB::wptable('term_taxonomy');
		//$wp_terms = DB::wptable('terms');

		$record = $this->getTaxonomyRecord($term_id, $taxname);
// debug([$term_id, $taxname]);

//debug($record);
		if ($record) {
			$term_taxonomy_id = $record->term_taxonomy_id;
			$sql = "UPDATE $wp_term_taxonomy 
					SET count=count+1 
					WHERE term_taxonomy_id=$term_taxonomy_id";
			$this->db->query($sql);
//debug($sql);
			return $term_taxonomy_id;
		} else {
			$sql = "INSERT INTO $wp_term_taxonomy (term_id, taxonomy, description, parent, count) VALUES ($term_id, '$taxname', '$description', $parent, 1)";
			$this->db->query($sql);
			$term_taxonomy_id = $this->db->lastInsertId();
		}
//debug($sql);
		return $term_taxonomy_id;
	}

	// wordpress
	public function getTags() {
		
		$wp_terms = DB::wptable('terms');
		$wp_term_taxonomy = DB::wptable('term_taxonomy');

		$sql = "SELECT t.*, wtt.* FROM $wp_terms t
				LEFT JOIN $wp_term_taxonomy wtt ON t.term_id=wtt.term_id
				WHERE wtt.taxonomy = 'post_tag'";
		$records = $this->db->records($sql);
		return $records;
	}

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

	public function setDrupalDb($db) {
		$this->drupalDB = $db;
	}



	/******************* Drupal 7 taxonomy functions ********************/

	public function getVocabulary() {
		$sql = "SELECT vid, name, machine_name, description, hierarchy, module, weight
				FROM taxonomy_vocabulary
				ORDER BY weight";
		$vocabulary = $this->db->records($sql);
		return $vocabulary;
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

	public function getTaxonomyDrupal($tid) {
		$sql = "SELECT name FROM taxonomy_term_data WHERE tid=$tid";
		$record = $this->db->record($sql);
		if ($record && $record->name) {
			return $record->name;
		} else {
			return NULL;
		}
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
}
