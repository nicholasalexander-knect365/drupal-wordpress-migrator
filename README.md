# drupal-wordpress-migrator

Tool for migrating content from Drupal to Wordpress

Following an import using FG import (which imports nodes and builds wp_termmeta elements to support taxonomies and CCK data) Wordpress does not know about taxonomies or CCK data.  

## Installation

You need: 
* Path to Drupal directory 
* Path to store images
* Drupal mysqldump instance loaded into a database
* Database credentials for Drupal instance 
* Database access for Wordpress instance
* An assigned wp_XX instance created in Network Admin -> Sites : when created record the ID number 

## Do the following

1. Create the Wordpress Site instance and record its ID number
2. Add yourself as a user to the site

3. Setup Apache/DNS as required so site is accessible

4. install the migrator tar xvfz migrator.tar.gz
	* make an image store directory ./images

6. configure the database: 
	* edit DB.class.php and add a section for the staging/live server with the database credentials for the server
 
7. in the migrator directory, run the unit test:
	*  phpunit tests/migrator.tests.php > unitest-prerun.txt
	* run the migrator, configure for the server, turn on verbose
	* php migrator.php --server=staging --drupalPath=/var/www/drupal7/tuauto --imageStore=/images --clean --images --init -v
	* import the images into the media library 
	* (you may have to copy /images to a local machine to drag and drop them into the media library)
	* images that occur in a post internally

8. output shows progress
	* when it finishes, rerun the unit test
	* phpunit tests/migrator.tests.php > unittest-postrun.txt



# Versions 
##History
* v101 initial tests
* v102 working with imports, DB module
* v103 importing taxonomies
* v104 importing nodes directly
* TAG prerelease01
* v105 field_data into ACF
* multisite - changes required for multisite MERGED to V105
* v105 additional phpunit, better options
* MASTER & TAG prerelease02
* v106 ACF import from ACF data, unit tests with taxonomy usage reports

## Release versions
* STAGING RELEASE 17 April 2018
* TAG tag prerelease03

