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

LIVE:  For a live install:

	a. run php makeDrupalUsers.php to create dusers table in the Wordpress database, this is then used instead of Drupal to create live users and usermeta in Wordpress.

	b. to import users on live use 

		php migrator.php --dusers -u --server=live --wordpressPath=/srv/www/telecoms.com --project='tu-auto'

	c. backup live database and restore on staging, run php migrator.php -d --server=staging to build the full database on staging

	d. migrate the wp_nn_ tables from staging to live via SQL dump

0. Install the migrator in your HOME directory
	* cd 
	* mkdir migrator
	* cd migrator
	* git clone git@github.com:nicholasalexander-knect365/drupal-wordpress-migrator.git

1. Create the Wordpress Site instance and record its ID number

2. Add yourself as a user to the site

3. Setup Apache/DNS as required so site is accessible

4. install the migrator tar xvfz migrator.tar.gz
	* make an image store directory ./images

6. configure the database: 
	* edit DB.class.php and add a section for the staging/live server with the database credentials for the server
 
7. in the migrator directory, run the unit test:
>	phpunit tests/migrator.tests.php > unitest-prerun.txt
>	run the migrator, configure for the server, turn on verbose
>	php migrator.php --server=staging --wordpressPath=/var/www/public --drupalPath=/var/www/drupal7/tuauto --imageStore=images --clean --images --init --taxonomy --nodes -c -f -p -n
	or
>	php migrator.php --server=staging --wordpressPath=/var/www/public --drupalPath=/var/www/drupal8/tuauto --project=tuauto -d

7a. 01/05/2018 run with these parameters on staging:

>	php migrator.php --wordpressPath=/srv/www/test1.telecoms.com --project=tuauto --clean --drupalPath=/srv/www/test1.telecoms.com/drupal7/tu-auto --server=staging --wordpressURL=http://beta-tu.auto.com -n -u -t -f -c --initialise

8. run the image importation script, from the Wordpress directory
>	chmod 755 importCmds.sh
>	cd $WORDPRESS_ROOT
>	../migrator/importCmds.sh

NB: The image importer script associates images to posts in this import.  If Nodes are imported again (i.e. to --clean the html) you must rerun the image importer after clearing out the media library

9. Content only can be replaced with replaceContent.php.  NB: this rereads the Drupal source and replaces all posts in Wordpress.  ANY CHANGES made in Wordpress since the migration WILL BE OVERWRITTEN.

`$ php replaceContent.php --project=tuauto --wordpressPath=/home/nicholas/Dev/wordpress/tuauto --clean`


DBUG: --init did not seem to work (wordpress has data in it error)
      -d works if you edit the drupalPath in migrator.php and edit DB to select the right server

	* import the images into the media library (you may have to copy /images to a local machine to drag and drop them into the media library)
	* images that occur in a post internally

10. output shows progress
	* when it finishes, rerun the unit test
	* phpunit tests/migrator.tests.php > unittest-postrun.txt

# Versions 
## History
### TAGs

* Apr - May 2018 prerelease1 - 3

* 24 May 2018  liverun-tuauto - version used to create tu-auto on staging, requiring separation of user build from wp_xx_ files build

* branch v110 - technical debt issues including greater clarity on release names, separation of user build from remaining build

## Definitions

* imagestore - a directory (usually it is ./images under the migrator directory) where images from Drupal file system or S3 are imported to, so that they are available during to the importCmds.sh wp-cli script

* wordpresPath - full pathname on the server where the wp-config.php file can be located

* drupalPath - full pathname on the server where the Drupal is installed drupalPath/sites/default/files normally contains the Drupal media files

## Improvements

* Simpler default settings for standard run

`$ php migrator.php -d --server=[local,vm,staging] ...assumes --wordpressPath --drupalPath and default settings`

* Idempotent script to replace content: 

`$ php replaceContent.php --project=tuauto --wordpressPath=/srv/www/test1.telecoms.com/drupal7/tu-auto --clean`

* Export Drupal users into table in the Wordpress database called dusers that contain sufficient data fields to create Wordpress users.

`$ php makeDrupalUsers.php`

* Create Wordpress users - reads dusers table and creates Wordpress users only - for running on live server to create user IDs - after this is run, the dusers table can be dropped

`$ php createWPusers.php --wordpressPath=/path/to/wp-content`

* importCmds.sh created during full run to import the images from imagestore directory into Wordpress media library.  To import, change directory to the wordpressPath and run it in the bash shell:

`$ . ./MIGRATOR_PATH/importCmds.sh`


## MIGRATING TO LIVE 

* Run makeDrupalUsers.php on staging or vm server, this will create a table called dusers in the Wordpress database.  This table can be dropped after createWPusers.php is run on live.

Then, to build the live instance:

* Export database from LIVE to Staging
* run migrator on Staging (in a multisite environment) to build the remaining wp_NN_ tables 
* importCmds.sh to create the media library
* export wp_NN_ tables to LIVE (these will not yet exist)
* rsynch the images

Now you can test the LIVE server.

