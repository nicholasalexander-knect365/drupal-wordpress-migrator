# drupal-wordpress-migrator

Tool for migrating content from Drupal to Wordpress

Following an import using FG import (which imports nodes and builds wp_termmeta elements to support taxonomies and CCK data) Wordpress does not know about taxonomies or CCK data.  

## Version plan
* v101 initial tests
* v102 working with imports, DB module
* v103 importing taxonomies
* v104 importing nodes directly
* TAG prerelease01
* v105 field_data into ACF
* multisite - changes required for multisite MERGED to V105
* v105 additional phpunit, better options
* MASTER & TAG prerelease02
* v106 ACF import from ACF data
