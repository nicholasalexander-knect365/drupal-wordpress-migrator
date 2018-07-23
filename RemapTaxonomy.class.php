<?php

require_once "Taxonomy.class.php";

class RemapTaxonomy extends Taxonomy {

	protected function IOTIRemapNameCategory($name) {
		// TODO: this is not yet complete: some taxonomies activate a parent,
		// i.e. subject taxonomies are all also type=technolgies or type=vertical industries
		switch(trim(strtolower($name))) {
			case 'news':
			case 'news and analysis':
				$taxset = ['type' => 'News'];
				break;

			case 'agriculture':
			case 'personal':
			case 'startups':
			case 'strategy':
				$taxset = ['type' => 'Strategy'];
				break;

			case 'analytics':
				$taxset = ['type' => 'Technologies', 'subject' => 'Analytics'];
				break;

			case 'infrastructure':
				$taxset = ['type' => 'Technologies', 'subject' => 'Architecture'];
				break;

			case 'engineering and development':
				$taxset = ['type' => 'Technologies', 'subject' => 'Engineering / Development'];
				break;

			case 'security':
				$taxset = ['type' => 'Technologies', 'subject' => 'Security'];
				break;

			case 'industrial iot (iiot)':
				$taxset = ['type' => 'Vertical Industries', 'subject' => 'IIoT'];
				break;

			case 'smart cities':
				$taxset = ['type' => 'Vertical Industries', 'subject' => 'Cities'];
				break;

			case 'smart energy and utilities':
				$taxset = ['type' => 'Vertical Industries', 'subject' => 'Energy'];
				break;

			case 'smart buildings':
			case 'smart home':
				$taxset = ['type' => 'Vertical Industries', 'subject' => 'Homes / Buildings'];
				break;

			case 'automotive':
			case 'transport and logistics':
			case 'transportation and logistics':
				$taxset = ['type' => 'Vertical Industries', 'subject' => 'Transportation / Logistics'];
				break;

			case 'healthcare':
				$taxset = ['type' => 'Vertical Industries', 'subject' => 'Healthcare'];
				break;

			case 'retail':
				$taxset = ['type' => 'Vertical Industries', 'subject' => 'Retail'];
				break;

			case 'article':
			case 'gallery':
			case 'link':
				$taxset = ['type' => 'Business Resources'];
				break;

			case 'webinar':
				$taxset = ['type' => 'Business Resources', 'type' => 'Webcasts'];
				break;

			case 'whitepapers':
			case 'whitepaper':
				$taxset = ['type' => 'Business Resources', 'type' => 'White Papers'];


			case 'video':
			case 'audio':
				$taxset = ['type' => 'Other Content', 'type' => 'Video / Podcasts'];
				break;

			case 'iot resources':
				$taxset = ['type' => 'Business Resources'];
				break;

			case 'connect the world of things to live business':
			case "exploring iot's cutting edge":
			case 'exploring iots cutting edge':
			case 'exploring iot\'s cutting edge':
			case 'five2ndwindow':
			case 'hannover messe 2016':
			case 'ideasxchange':
			case 'ideaxchange':
			case 'manufacturing day':
			case 'ovum viewpoints':
				$taxset = ['programs' => $name];
				break;

			default:
				$taxset = ['post_tag' => $name];
				break;
		}
		return $taxset;
	}

	protected function TUAutoRemapNameCategory($name) {
		
		switch(trim(strtolower($name))) {

			case 'adas':
				$name = 'ADAS';
				$taxonomy = 'category';
				break;

			case 'autonomous':
			case 'autonomous car':
				$name = 'Autonomous';
				$taxonomy = 'channels';
				break;

			case 'electric vehicles':
				$name = 'Electric Vehicles';
				$taxonomy = 'category';
				break;

			case 'fleet and asset management':
			case 'fleet':
				$name = 'Fleet';
				$taxonomy = 'category';
				break;

			case 'infotainment':
				$name = 'Infotainment';
				$taxonomy = 'category';
				break;

			case 'insurance':
			case 'insurance & legal':
			case 'insurance and legal':
			case 'insurance telematics':
				$name = 'Insurance';
				$taxonomy = 'channels';
				break;

			case 'mobility':
			case 'auto mobility':
				$name = 'Mobility';
				$taxonomy = 'channels';
				break;

			case 'navigation & lbs':
			case 'navigation and lbs':
			case 'connected car':
				$name = 'Connected Car';
				$taxonomy = 'channels';
				break;

			case 'other':
				$name = 'Other';
				$taxonomy = 'category';

			case 'safety, adas & autonomous':
			case 'safety, adas and autonomous':
				$name = 'ADAS';
				$taxonomy = 'category';
				break;

			case 'security':
				$name = 'Security';
				$taxonomy = 'channels';
				break;

			case 'telematics':
				$name = 'Telematics';
				$taxonomy = 'subject';
				break;

			case 'telematics for evs':
				$name = 'Electric Vehicles';
				$taxonomy = 'category';
				break;

			default: 
				$taxonomy = 'post_tag';
				break;
		}
		if (strlen($taxonomy)) {
			return [$name, $taxonomy];
		} else {
			debug([$name, $slug, $taxonomy]);
			throw new Exception('No taxonomy mapped?');
		}
	}
}
