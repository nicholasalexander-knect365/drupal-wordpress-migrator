<?php

/* create Wordpress POST elements */

include_once "DB.class.php";

class Post {

	public $db;
	public $wp_post_fields = ['ID', 'post_author', 'post_date', 'post_date_gmt', 'post_content', 'post_title', 'post_excerpt', 'post_status', 'post_password', 'post_name', 'to_ping', 'pinged', 'post_modified', 'post_modified_gmt', 'post_content_filtered', 'post_parent', 'guid', 'menu_order', 'post_type', 'post_mime_type', 'comment_count'];

	public static $mapPostType = [
		'article' 		=> 'post',
		'banners'		=> 'banner',
		'blog'			=> 'post',
		'event'			=> 'event',
		'job'			=> 'job',
		'webinar'		=> 'webinar',
		'report'		=> 'white-paper',
		'slideshow'  	=> 'slideshow',
		'whitepaper' 	=> 'white-paper',
		'podcast'		=> 'podcast',
		'video'	  		=> 'video',
		'article_banner' => 'banner',
		'homepage_tabs'	=> 'homepage_tabs',
		'page'			=> 'page'
	];

	// map drupal fileds to wordpress fields
	public static $mapped = [
		'nid'		=> 'ignore_nid',
		'vid'		=> 'ignore_vid',
		'type' 		=> 'post_type',
		'language' 	=> 'make_language',
		'title' 	=> 'post_title',
		'uid' 		=> 'post_author',
		'status' 	=> 'post_status',
		'created' 	=> 'post_date',
		'changed' 	=> 'post_modified',
		'comment' 	=> 'comment_status',
		'promote' 	=> 'make_meta_featured',
		'sticky'	=> 'make_meta_sticky',
		'tnid'		=> 'ignore_translation_post_id',
		'translate'	=> 'ignore_translate',
		'content'	=> 'post_content',
		'precis'	=> 'post_excerpt'
	];

	/* Wordpress fields that are initialised with empty string */
	public static $null_fields = [
		'to_ping',
		'pinged',
		'post_content_filtered',
		'post_password',
		'guid',
		'post_mime_type',
	];

	public static $static_fields = [
		'ping_status'	=> 'open',
		'comment_count' => 0,
		'menu_order' => 0,
		'post_parent' => 0
	];

	public static $translation_warning;

	public function __construct($db) {
		$this->db = $db;
		$this->timezone_add = 0;
		static::$translation_warning = 0;
	}



	private function findMakes($item) {
		return strpos('make_', $item);
	}

	// private function convert_smart_quotes($string) { 
	//     $search = array(chr(145), 
	//                     chr(146), 
	//                     chr(147), 
	//                     chr(148), 
	//                     chr(151)); 

	//     $replace = array("'", 
	//                      "'", 
	//                      '"', 
	//                      '"', 
	//                      '-'); 

	//     return str_replace($search, $replace, $string); 
	// } 


	/**
	 * Helper function for drupal_html_to_text().
	 *
	 * Calls helper function for HTML 4 entity decoding.
	 * Per: http://www.lazycat.org/software/html_entity_decode_full.phps
	 */
	private function decode_entities_full($string, $quotes = ENT_COMPAT, $charset = 'ISO-8859-1') {
		$that = $this;
	  	return html_entity_decode(preg_replace_callback('/&([a-zA-Z][a-zA-Z0-9]+);/', array($this,'convert_entity'), $string), $quotes, $charset); 
	}

	/**
	 * Helper function for decode_entities_full().
	 *
	 * This contains the full HTML 4 Recommendation listing of entities, so the default to discard  
	 * entities not in the table is generally good. Pass false to the second argument to return 
	 * the faulty entity unmodified, if you're ill or something.
	 * Per: http://www.lazycat.org/software/html_entity_decode_full.phps
	 */
	private function convert_entity($matches, $destroy = true) {
	  static $table = array('quot' => '&#34;','amp' => '&#38;','lt' => '&#60;','gt' => '&#62;','OElig' => '&#338;','oelig' => '&#339;','Scaron' => '&#352;','scaron' => '&#353;','Yuml' => '&#376;','circ' => '&#710;','tilde' => '&#732;','ensp' => '&#8194;','emsp' => '&#8195;','thinsp' => '&#8201;','zwnj' => '&#8204;','zwj' => '&#8205;','lrm' => '&#8206;','rlm' => '&#8207;','ndash' => '&#8211;','mdash' => '&#8212;','lsquo' => '&#8216;','rsquo' => '&#8217;','sbquo' => '&#8218;','ldquo' => '&#8220;','rdquo' => '&#8221;','bdquo' => '&#8222;','dagger' => '&#8224;','Dagger' => '&#8225;','permil' => '&#8240;','lsaquo' => '&#8249;','rsaquo' => '&#8250;','euro' => '&#8364;','fnof' => '&#402;','Alpha' => '&#913;','Beta' => '&#914;','Gamma' => '&#915;','Delta' => '&#916;','Epsilon' => '&#917;','Zeta' => '&#918;','Eta' => '&#919;','Theta' => '&#920;','Iota' => '&#921;','Kappa' => '&#922;','Lambda' => '&#923;','Mu' => '&#924;','Nu' => '&#925;','Xi' => '&#926;','Omicron' => '&#927;','Pi' => '&#928;','Rho' => '&#929;','Sigma' => '&#931;','Tau' => '&#932;','Upsilon' => '&#933;','Phi' => '&#934;','Chi' => '&#935;','Psi' => '&#936;','Omega' => '&#937;','alpha' => '&#945;','beta' => '&#946;','gamma' => '&#947;','delta' => '&#948;','epsilon' => '&#949;','zeta' => '&#950;','eta' => '&#951;','theta' => '&#952;','iota' => '&#953;','kappa' => '&#954;','lambda' => '&#955;','mu' => '&#956;','nu' => '&#957;','xi' => '&#958;','omicron' => '&#959;','pi' => '&#960;','rho' => '&#961;','sigmaf' => '&#962;','sigma' => '&#963;','tau' => '&#964;','upsilon' => '&#965;','phi' => '&#966;','chi' => '&#967;','psi' => '&#968;','omega' => '&#969;','thetasym' => '&#977;','upsih' => '&#978;','piv' => '&#982;','bull' => '&#8226;','hellip' => '&#8230;','prime' => '&#8242;','Prime' => '&#8243;','oline' => '&#8254;','frasl' => '&#8260;','weierp' => '&#8472;','image' => '&#8465;','real' => '&#8476;','trade' => '&#8482;','alefsym' => '&#8501;','larr' => '&#8592;','uarr' => '&#8593;','rarr' => '&#8594;','darr' => '&#8595;','harr' => '&#8596;','crarr' => '&#8629;','lArr' => '&#8656;','uArr' => '&#8657;','rArr' => '&#8658;','dArr' => '&#8659;','hArr' => '&#8660;','forall' => '&#8704;','part' => '&#8706;','exist' => '&#8707;','empty' => '&#8709;','nabla' => '&#8711;','isin' => '&#8712;','notin' => '&#8713;','ni' => '&#8715;','prod' => '&#8719;','sum' => '&#8721;','minus' => '&#8722;','lowast' => '&#8727;','radic' => '&#8730;','prop' => '&#8733;','infin' => '&#8734;','ang' => '&#8736;','and' => '&#8743;','or' => '&#8744;','cap' => '&#8745;','cup' => '&#8746;','int' => '&#8747;','there4' => '&#8756;','sim' => '&#8764;','cong' => '&#8773;','asymp' => '&#8776;','ne' => '&#8800;','equiv' => '&#8801;','le' => '&#8804;','ge' => '&#8805;','sub' => '&#8834;','sup' => '&#8835;','nsub' => '&#8836;','sube' => '&#8838;','supe' => '&#8839;','oplus' => '&#8853;','otimes' => '&#8855;','perp' => '&#8869;','sdot' => '&#8901;','lceil' => '&#8968;','rceil' => '&#8969;','lfloor' => '&#8970;','rfloor' => '&#8971;','lang' => '&#9001;','rang' => '&#9002;','loz' => '&#9674;','spades' => '&#9824;','clubs' => '&#9827;','hearts' => '&#9829;','diams' => '&#9830;','nbsp' => '&#160;','iexcl' => '&#161;','cent' => '&#162;','pound' => '&#163;','curren' => '&#164;','yen' => '&#165;','brvbar' => '&#166;','sect' => '&#167;','uml' => '&#168;','copy' => '&#169;','ordf' => '&#170;','laquo' => '&#171;','not' => '&#172;','shy' => '&#173;','reg' => '&#174;','macr' => '&#175;','deg' => '&#176;','plusmn' => '&#177;','sup2' => '&#178;','sup3' => '&#179;','acute' => '&#180;','micro' => '&#181;','para' => '&#182;','middot' => '&#183;','cedil' => '&#184;','sup1' => '&#185;','ordm' => '&#186;','raquo' => '&#187;','frac14' => '&#188;','frac12' => '&#189;','frac34' => '&#190;','iquest' => '&#191;','Agrave' => '&#192;','Aacute' => '&#193;','Acirc' => '&#194;','Atilde' => '&#195;','Auml' => '&#196;','Aring' => '&#197;','AElig' => '&#198;','Ccedil' => '&#199;','Egrave' => '&#200;','Eacute' => '&#201;','Ecirc' => '&#202;','Euml' => '&#203;','Igrave' => '&#204;','Iacute' => '&#205;','Icirc' => '&#206;','Iuml' => '&#207;','ETH' => '&#208;','Ntilde' => '&#209;','Ograve' => '&#210;','Oacute' => '&#211;','Ocirc' => '&#212;','Otilde' => '&#213;','Ouml' => '&#214;','times' => '&#215;','Oslash' => '&#216;','Ugrave' => '&#217;','Uacute' => '&#218;','Ucirc' => '&#219;','Uuml' => '&#220;','Yacute' => '&#221;','THORN' => '&#222;','szlig' => '&#223;','agrave' => '&#224;','aacute' => '&#225;','acirc' => '&#226;','atilde' => '&#227;','auml' => '&#228;','aring' => '&#229;','aelig' => '&#230;','ccedil' => '&#231;','egrave' => '&#232;','eacute' => '&#233;','ecirc' => '&#234;','euml' => '&#235;','igrave' => '&#236;','iacute' => '&#237;','icirc' => '&#238;','iuml' => '&#239;','eth' => '&#240;','ntilde' => '&#241;','ograve' => '&#242;','oacute' => '&#243;','ocirc' => '&#244;','otilde' => '&#245;','ouml' => '&#246;','divide' => '&#247;','oslash' => '&#248;','ugrave' => '&#249;','uacute' => '&#250;','ucirc' => '&#251;','uuml' => '&#252;','yacute' => '&#253;','thorn' => '&#254;','yuml' => '&#255;'
	                       );
	  if (isset($table[$matches[1]])) return $table[$matches[1]];
	  // else 
	  return $destroy ? '' : $matches[0];
	}

	private function prepare($str) {

        $str = str_replace(array("\r\n", "\r", "\n"), '', $str);
        $str = preg_replace('/&rdquo;/', '&quot;', $str);
        $str = preg_replace('/&rsquo;/', '&apos;', $str);
        $str = preg_replace('/&ldquo;/', '&quot;', $str);
        $str = preg_replace('/&lsquo;/', '&apos;', $str);
        $str = preg_replace('/&ndash;/', '-', $str);
        $str = preg_replace('/&mdash;/', '--', $str);
        $str = preg_replace('/\'/', '&apos;', $str);
        $str = preg_replace('/\"/', '&quot;', $str);
        
		// $str = $this->convert_smart_quotes($str);
        $str = $this->decode_entities_full($str);
        // $str = html_entity_decode($str);

        return $str;
	}

	public function replacePostContent($wpPostId, $drupalNode) {
		$wp_posts = DB::wptable('posts');
		$wp_postmeta = DB::wptable('postmeta');

		$sql = "SELECT * FROM $wp_posts WHERE ID=$wpPostId";
		$record = $this->db->record($sql);

		$post_content = $this->prepare($drupalNode->content);
		$sql = "UPDATE $wp_posts set post_content = '$post_content' WHERE ID=$wpPostId LIMIT 1";
//print "\n\n$sql\n";
		try {
			$this->db->query($sql);
		} catch (Exception $e) {
			throw new Exception($sql . "\nError in SQL statement " . $e->getMessage());
		}
	}

	public function makePost($drupal_data, $options = NULL, $files, $wordpressPath, $users) { //, $fileSet = NULL, $wordpressPath) {

		$wp_posts = DB::wptable('posts');

		$values = [];
		$metas = [];

		static $running = 0;

		$nid = $drupal_data->nid;
		$fileSet = $files->fileList($nid);

		foreach($drupal_data as $key => $value) {

			$wpKey = static::$mapped[$key];

			// if drupal fields are prefixed make_ 
			// they are post_meta and are created AFTER post is created
			if (preg_match('/^make_/', $wpKey)) {
				$metas[$key] = $value;

			} else if (preg_match('/^ignore_/', $wpKey) && isset($value)) {

				if (preg_match('/^ignore_trans/', static::$translation_warning === 0)) {
					print "\nWarning: translation data exists in drupal!";
					static::$translation_warning = 1;
				}

			} else {

				$value = $this->prepare($value);

				if ($key === 'created' || $key === 'changed') {
					$value = date('Y-m-d h:i:s', $value);
					$values[$wpKey] = $value;
					// TODO: assume the blog is GMT based: if it isn't 
					// - the TZ difference would is needed 
					//   to calculate GMT for this post
					$values[$wpKey . '_gmt'] = $value;
				}
				switch ($key) {

					case 'uid':

						$drupalUser = $users->getDrupalUserByUid($value);
						if ($drupalUser && strlen($drupalUser->mail) > 4) {
							$wordpressUser = $users->getWordpressUserByEmail($drupalUser->mail);
						} else {
							debug("$value user with this uid can not be found in the Drupal Database, post assgined to default user in Wordpress");
							$wordpressUser = $users->getWordpressUserById(1);
						}

					case 'title':
						$values[$wpKey] = $value;
						$values['post_name'] = substr(Taxonomy::slugify($values[$wpKey]), 0, 200);
						if (strlen($values['post_name']) === 0) {
							$values['post_name'] = 'tu-auto-' . $running++;
						}
						break;

					case 'content':
						if ($options && $options->clean) {
							$value = preg_replace('/ style\=[\'"].*?[\'"]/', '', $value);
						}

						if ($fileSet) {

							// replace the filename in content: 
							// drupal uses image.preview.png for thumbnails
							// but we only want the actual filename

							foreach ($fileSet as $file) {

								$filename = basename($file->filename);
								$replaceFilename = $filename;
								$preview  = preg_replace(['/.jpg$/', '/.gif$/', '/.png$/'], ['.preview.jpg', '.preview.gif', '.preview.png'], basename($file->filename));

								// if (preg_match('/src=["]([\w:\/\-\.\_]+)?["]/i', $value, $matched)) {
								// 	if (count($matched)>0 && strpos($matched[1], $filename)) {

								// 	}
								// }
								if (preg_match('/src=["]([\w:\/\-\.\_]+)?["]/i', $value, $matched)) {
									if (count($matched)>0 && strpos($matched[1], $preview)) {
										$replaceFilename = $preview;
									}
								}

								$value = preg_replace("/src=\".*?$replaceFilename\"/", "src=\"$wordpressPath/$filename\"" , $value);

							}
						}
						$values[$wpKey] = $value;
						break;

					case 'status':
						if ($value === '1' || $value === 1) {
							$values[$wpKey] = 'publish';
						} else {
							$values[$wpKey] = 'draft';
						}
						break;

					case 'uid': 
						$values[$wpKey] = 1;
						break;

					case 'comment':
						if ($value === 1) {
							$values[$wpKey] = 'open';
						} else {
							$values[$wpKey] = 'closed';
						}
						break;

					case 'type' : 
						$values['post_type'] = static::$mapPostType[$value];
						break;

					default: 
						$values[$wpKey] = $value;
				}
			}
		}
		foreach(static::$null_fields as $field) {
			$values[$field] = '';
		}
		foreach(static::$static_fields as $field => $value) {
			$values[$field] = $value;
		}

		$sql = "INSERT into $wp_posts (" . implode(', ', array_keys($values)) . ") VALUES ('" . implode("', '", $values) ."')";
		$this->db->query($sql); 
		$post_id = $this->db->lastInsertId();

if (!$post_id) {
	debug($sql);
}

		return $post_id;

		// if (isset($filename)) {
		// 	$featured = !$nomatch;
		// 	$wp->addMediaLibrary($post_id, $filename, $featured);
		// }

		// // // set featured image
		// // if (isset($featuredImage)) {
		// // 	$wp->featuredImage($post_id, $featuredImage);
		// // }

		// // meta processing: 
		// // create values in postmeta 
		// foreach ($metas as $key => $value) {
		// 	// $action = static::$mapped[$drupalKey];
		// 	if (preg_match('/^make_/', $value)) {
		// 		// rules to derive this field
		// 		switch ($value) {
		// 			case 'make_post_author':
		// 				var_dump($key, $value);
		// 		}
		// 	}
		// }
		// if (!$post_id) {
		// 	debug($sql);
		// }
		// return $post_id;
	}
}