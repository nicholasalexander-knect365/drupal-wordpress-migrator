<?php

require_once "Taxonomy.class.php";

class RemapTaxonomy extends Taxonomy {

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