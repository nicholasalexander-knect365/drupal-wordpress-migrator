<?php
/**
 * Taxonomy class
 * for translating D7 to Wordpress
 * taxonomies: dual purpose class
 * call with DB constructor
 */
require_once "DB.class.php";

class Taxonomy {
	
	public $db;
	private $initialise_regardless = false;

	private $mapped = [
		'channels' 			=> 'category',
		'article type' 		=> 'type',
		'tags' 				=> 'tag',
		'itunes category' 	=> 'podcast',
		'weekly brief'		=> 'brief'
	];

	private $termMeta = [
		'category' 	=> 'Category',
		'subject' 	=> 'Subject',
		'type'		=> 'Type',
		'region'	=> 'Region',
		'industry'	=> 'Industry'
	];


	public function __construct($db) {
		$this->db = $db;
	}
	
	public function initialise() {
		if ($this->initialise_regardless && $this->termsAlreadyExist()) {
			$this->cleanUp();
		} else {
			return;
		}
	}
	private function cleanUp() {
		$sql = "DELETE FROM wp_terms WHERE term_id>1";
		$this->db->query($sql);
		$sql = "ALTER TABLE wp_terms AUTO_INCREMENT = 2";
		$this->db->query($sql);
		$sql = "DELETE FROM wp_term_taxonomy";
		$this->db->query($sql);
		$sql = "ALTER TABLE wp_term_taxonomy AUTO_INCREMENT = 1";
		$this->db->query($sql);
		$sql = "DELETE FROM wp_term_relationships";
		$this->db->query($sql);
		$sql = "ALTER TABLE wp_term_relationships AUTO_INCREMENT = 1";
		$this->db->query($sql);
	}

	private function termsAlreadyExist() {
		$this->db->query('SELECT COUNT(*) as c from wp_terms');
		$item = $this->db->getRecord();
		if ($item->c > 1) {
			return true;
		}		
	}

	/** 
	 * checkTerms in Wordpress
	 */
	public function checkTerms() {
		$sql = "SELECT COUNT(*) AS c FROM wp_term_taxonomy";
		$this->db->query($sql);
		$items = $this->db->getRecord();
		return ($items->c === 1);
	}

	/** build Wordpress Terms **/
	public function XbuildTerms() {
		$taxonomies = ['subject', 'type', 'region', 'industry'];
		foreach($taxonomies as $taxonomy) {
			$sql = "INSERT into wp_term_taxonomy (term_taxonomy_id, term_id, taxonomy, description, parent, count) VALUES (null, 1, '$taxonomy', '', 0, 0)";
			$this->db->query($sql);
		}	
	}

	static private function slugify($str) {
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


	// private function categories($taxonomies) {
	// 	$catgories = [];
	// 	foreach($taxonomies as $taxonomy) {
	// 		if ((integer)$taxonomy->vid === $catId) {
	// 			$categories[$taxonomy->tid] = $taxonomy->name;
	// 		}
	// 	}
	// 	return $categories;
	// }

	private function remap($taxonomyType) {
		return $this->mapped[strtolower($taxonomyType)];

	}
	public function createTerms($taxonomies) {

		if ($this->termsAlreadyExist()) {
			//$this->cleanUp();
			return;
		}

		foreach ($taxonomies as $taxonomy) {

			$category = addslashes(ucfirst($taxonomy->name));
			$slug = self::slugify($category);
			$taxonomyType = $this->remap($taxonomy->type);
			$tid = $taxonomy->tid;
			// if (!$tid) {
			// 	var_dump($taxonomy);
			// 	die('no tid?');
			// }

			$sql = "INSERT INTO wp_terms (name, slug, term_group) 
					VALUES ('$category', '$slug', $tid)";
			$this->db->query($sql);
			$term_id = $this->db->lastInsertId();

			$sql = "INSERT INTO wp_term_taxonomy 
				(term_id, taxonomy, description, parent, count) 
				VALUES 
				($term_id, '$taxonomyType', 'Migrated from Drupal', 0, 0)";

			$this->db->query($sql);
		}
	}


	/** 
	 * full node taxonomy
	 */
	public function nodeTaxonomies($node) {
		$nid = $node->nid;
		$sql = "SELECT 	ti.nid as nid, 
						ti.tid as tid, 
						td.vid as vid, 
						td.name as name, 
						td.description as description, 
						td.weight as weight, 
						tv.name as category 
				FROM taxonomy_index ti 
				INNER JOIN taxonomy_term_data td ON td.tid=ti.tid 
				INNER JOIN taxonomy_vocabulary tv ON tv.vid=td.vid 
				WHERE nid=$nid";
		$this->db->query($sql);
		$taxonomies = $this->db->getRecords();
		return $taxonomies;
	}


	private function getPostId($nid) {
		$sql = "SELECT pm.post_id
				FROM wp_postmeta pm
				WHERE pm.meta_value=$nid AND meta_key='_fgd2wp_old_node_id'";

		$wp->query($sql);
		$post = $wp->getRecord();
		return $post->post_id;		
	}

	public function makeTermTaxonomy($taxonomyRecord) {
		$post_id = $this->getPostId($taxonomyRecord->nid);
		$term_id = $taxonomyRecord->term_id;
		$taxonomy = $taxonomyRecord->category;
		$description = $taxonomyRecord->name;
		$parent = 0;

		$sql = "INSERT INTO wp_term_taxonomy (term_id, taxonomy, description, parent, count) VALUES ( $term_id, $taxonomy, $description, $parent, 0)";

		$this->db->query($sql);

	}

	// build the taxonomy term_data from drupal
	private function getTermData($taxonomy) {

		$termData = [];
		$tid = $taxonomy->tid;	

		$sql = "SELECT * FROM taxonomy_term_data
				WHERE tid = $tid
				ORDER BY tid";

		$this->db->query($sql);
		$termData = $this->db->getRecords();
		return $termData;
	}


	// make the wp_termmeta for the tags!!
	private function makeTermMeta($term_id, $name, $description) {

			$meta_key = $name;
			$meta_value = $description;

			$sql = "INSERT INTO wp_termmeta (term_id, meta_key, meta_value) 
					VALUES ($term_id, '$meta_key', '$meta_value')";

			$this->db->query($sql);
	}

	private function makeTermTaxonomy($taxonomy) {
		$name = $taxonmy->name;
		$term_id = $taxonomy->tid;
		$taxonomy = $taxonomy->category;
		if (strlen($taxonomy->description)) {
			$description = $taxonomy->name . ' ' . $taxonomy->description;
		} else {
			$description = $taxonomy->name;
		}
		$format = $taxonomy->format;
		$weight = $taxonomy->weight;

		// does the taxonmy exist, if so increase count
		$sql = "SELECT COUNT(*) as c from wp_term_taxonomy WHERE term_id = $term_id";
		$this->db->query($sql);
		$record = $this->db->getRecord();
		$cnt = $record->c;
		if ($cnt === 0) {
			$sql = "INSERT INTO wp_term_taxonomy (term_id, taxonomy, description, parent, count) VALUES ($term_id, '$taxonomy', '$description', $parent, 0)";
		} else {
			$sql = "UPDATE wp_term_taxonomy SET count=count+1 WHERE term_id=$term_id";
		}
		$this->db->query($sql);
	}

	private function makeTermRelationship($taxonomy) {
		// find the post
		$posts = $this->findPosts($taxonomy->nid);
		foreach ($posts as $post) {
			$postId = $post->post_id;
			if (!$taxonmy->term_taxonomy_id) {
				die('makeTermRelationship out of order!');
			}
			$term_taxonomy_id = $taxonomy->term_taxonomy_id;
			$term_order = $taxonomy->weight;
			// create a termRelation
			$sql = "INSERT INTO wp_term_relationships (object_id, term_taxonomy_id, term_order) 
					VALUES ($postId, $term_taxonomy_id, $term_order)";
			$this->db->query($sql);			
		}
	}

	public function makeWPTermData($taxonomies) {

		foreach ($taxonomies as $taxonomy) {

			$termData = $this->getTermData($taxonomy);
			$term_id = $taxonomy->tid;
				
			if (strtolower($taxonomy->category) === 'tags') {

				$name = 'tag';
				$description = $taxonomy->name;
				$this->makeTermMeta($term_id, $name, $description);

			} else {

				$this->makeTermTaxonomy($taxonomy);

			}
			$this->makeTermRelationship($taxonomy);
		}
	}

	private function findPosts($nid) {

		$sql = "SELECT pm.post_id, pm.meta_value
				FROM wp_postmeta pm
				WHERE pm.meta_value=$nid AND meta_key='_fgd2wp_old_node_id'";

		$wp->query($sql);
		$posts = $wp->getRecords();
		return $posts;
	}


	}



	 /*
	 * get the Drupal taxonomyList
	 */
	public function fullTaxonomyList() {
		$taxonomyNames = [];
		$sql = 'SELECT distinct td.tid, td.vid, td.name, v.name AS type FROM taxonomy_term_data td
                LEFT JOIN taxonomy_vocabulary v ON td.vid=v.vid';
		$this->db->query($sql);

		$records = $this->db->getRecords();

		return $records;
	}

	public function nodeVocabulary($node) {
		$taxonomies = [];
		$nid = $node->nid;
		$sql = "SELECT DISTINCT tv.vid, tv.machine_name FROM taxonomy_vocabulary tv 
				LEFT JOIN taxonomy_term_data td ON tv.vid=td.vid
				INNER JOIN taxonomy_index ti ON ti.tid=td.tid
				WHERE ti.nid=$nid";

		$this->db->query($sql);
		$tids = $this->db->getRecords();

		return $tids;
	}


	public function taxonomyListForNode($node) {
		// find the taxonomies for this node
		$nid = $node->nid;
		$this->db->query("SELECT ti.nid, ti.tid, td.name
			FROM taxonomy_index ti 
			LEFT JOIN taxonomy_term_data td ON td.tid=ti.tid 
			WHERE nid=$nid");
		$tids = $this->db->getRecords();	

		return $tids;
	}

	/** 
	 * taxonomyListWithHierarchy
	 * purpose: to return taxonomy_term_data with parent node
	 * NOT TESTTED
	 */
	public function taxonomyListWithHierarchy($node) {
		$nid = $node->nid;
		$this->db->query("SELECT td.tid, th.parent FROM taxonomy_term_hierarchy th
							LEFT JOIN taxonomy_term_data td on td.tid=th.tid
							LEFT JOIN taxonomy_vocabulary v on v.vid=td.vid
							where td.nid=$nid");
		$tids = $this->db->getRecords();
		return $tids;
	}

}