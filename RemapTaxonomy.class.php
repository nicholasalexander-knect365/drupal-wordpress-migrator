<?php

require_once "Taxonomy.class.php";

class RemapTaxonomy extends Taxonomy {

	protected function IOTIRemapNameCategory($name) {
		// TODO: this is not yet complete: some taxonomies activate a parent,
		// i.e. subject taxonomies are all also type=technolgies or type=vertical industries
		switch(trim(strtolower($name))) {
			case 'news and analysis':
				$name = 'News';
				$taxonomy = 'type';
				break;
			case 'strategy':
				$name = 'Strategy';
				$taxonomy = 'type';
				break;
			case 'analytics':
				$name = 'Analytics';
				$taxonomy = 'subject';
				break;
			case 'infrastructure':
				$name = 'Architecture';
				$taxonomy = 'subject';
				break;
			case 'engineering and development':
				$name = 'Engineering/Development';
				$taxonomy = 'subject';
				break;
			case 'security':
				$name = 'Security';
				$taxonomy = 'subject';
				break;
			case 'startups':
				$name = 'Startups';
				$taxonomy = 'strategy';
				break;
			case 'agriculture':
				$name = 'Agriculture';
				$taxonomy = 'strategy';
				break;
			case 'automotive':
			case 'transport and logistics':
				$name = 'Transportation/Logistics';
				$taxonomy = 'subject';
				break;
			case 'healthcare':
				$name = 'Healthcare';
				$taxonomy = 'subject';
				break;
			case 'industrial iot (iiot)':
				$name = 'IIoT';
				$taxonomy = 'subject';
				break;
			case 'personal':
				$name = 'Personal';
				$taxonomy = 'strategy';
				break;
			case 'retail':
				$name = 'Retail';
				$taxonomy = 'subject';
				break;
			case 'smart buildings':
			case 'smart home':
				$name = 'Homes/Buildings';
				$taxonomy = 'subject';
				break;
			case 'smart cities':
				$name = 'Cities';
				$taxonomy = 'subject';
				break;
			case 'smart energy and utilties':
				$name = 'Energy';
				$taxonomy = 'subject';
				break;
			case 'article':
			case 'link':
				
			case 'video':
				$name = 'Video';
				$taxonomy = 'type';
				break;
			case 'gallery':
				$name = 'Galleries';
				$taxonomy = 'type';
				break;
			case 'audio':
				$name = 'Podcasts';
				$taxonomy = 'type';
				break;
			case 'webinar':
				$name = 'Webinars';
				$taxonomy = 'type';
				break;
			case 'white paper':
				$name = 'Whitepapers';
				$taxonomy = 'type';
				break;
			case 'standard page':
				$name = 'page';
				$taxonomy = 'type';
				break;
			default:
				$taxonomy = 'post_tag';
		}
		if (strlen($taxonomy)) {
			return [$name, $taxonomy];
		} else {
			debug([$name, $slug, $taxonomy]);
			throw new Exception('No taxonomy mapped?');
		}
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