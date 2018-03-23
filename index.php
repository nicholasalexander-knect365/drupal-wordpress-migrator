<?php 
// Drupal to Wordpres Migrator
// by Nicholas Alexander
// for Knect365.com
// this is not a web app but it may become one
?>
<html>
<head>
	<style>
		.block {
			display: block;
			background: #cccccc;
			border: 1px inset #dedede;
			padding: 1rem;
			margin: 1rem;
			max-width: 32rem;
		}
		.small {
			font-size: small;
		}
	</style>
</head>
<body>
<h1>Drupal to Wordpress Migrator</h1>
<hr>
<p><strong>Welcome to the Drupal to Wordpress Migrator</strong></p>

<h2>Projects</h2>
<div class="block">

<h3>TU-Auto</h3>
	<ul>
		<li>Launch Migrator on command line <input type="text" value="php migrator.php -p"></li>
		<li>Wordpress <a href="http://tuauto.local" target="local">TUAuto Wordpress (on local)</a></li>
		<li>Wordpress<a href="http://tuwp.local" target="local">TUAuto1 Wordpress (on local)</a></li>
		<li>Wordpress<a href="http://tuwp2.local" target="local">TUAuto2 Wordpress (on local)</a></li>
		<li>Wordpress Multisite <a href="http://telecoms.local" target="vagrant">Multisite (Telecoms)</a></li>
		<li>Wordpress Multisite <a href="http://tuauto.vagrant" target="vagrant">TUAuto Wordpress (on Vagrant)</a></li>
		<li>Drupal <a href="http://tudrupal.local" target="local2">Drupal 7 (TUAuto)</a></li>	
	</ul>	
</div>

<hr>

<h2>Software Development Versions</h2>

<p>Versions add new content elements.  Each version produces a wordpress site that has content.</p>

<div class="block small">

	<h3>Versions</h3>

	<p>Version 0.1</p>
	<ol>
		<li>Use FG plugin to import nodes</li>
	</ol>

	<p>Version 0.2</p>
	<ol>
		<li>Run migrator.php on command line to build taxonomies </li>
		<pre>php migrator.php</pre>
	</ol>

	<p>Version 0.3</p>
	<ol> 
		<li>Set options in control array to test events</li>
		<li>Run migrator.php on command line to build Content Types (Events, Podcasts, etc) as ACF entities</li>

		<i>or, explore...</i>

		<li>Convert wp_postmeta data into ACF from the FG import</li>
	</ol>

	<p>Version 0.4</p>
	<ol>
		<li>Import all public:// images used in nodes and store.</li>
		<li>Import s3:// images and store.</li>
		<li>Add options Class: supporting 
			<ul>
				<li>-q quiet</li>
				<li>-p progress</li>
				<li>-v verbose</li>
				<li>-h help (shows options and quits)</li>

				<li>--drupalPath=path/to/drupal</li>
				<li>--imagePath=path/to/store/images</li>
			</ul>
		</li>
	</ol>

	<p>Next ... Version 0.5</p>
	<ol>
		<li>Import nodes directly, so that image paths can be updated</li>
	</ol>

	<p>Planned ... Version 0.6</p>
	<ol>
		<li>Content types: process field_data for each content types into JSON data</li>
		<li>Populate ACF</li>
	</ol>

</div>
</body>
</html>