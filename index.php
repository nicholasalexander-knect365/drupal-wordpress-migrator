<?php 
// Drupal to Wordpres Migrator
// by Nicholas Alexander
// for Knect365.com
// this is not a web app but it may become one
?>
<html>
<h1>Drupal to Wordpress Migrator</h1>
<hr>
<p><strong>Welcome to the Drupal to Wordpress Migrator</strong></p>

<p>Versions add new content elements.  Each version produces a wordpress site that has content.</p>

<ul>
	<li><a href="http://tudrupal.local" target="local2">Drupal 7 (TUAuto)</a></li>
	<li><a href="http://tuauto.local" target="local">TUAuto Wordpress (on local)</a></li>
	<li><a href="http://tuwp.local" target="local">TUAuto1 Wordpress (on local)</a></li>
	<li><a href="http://tuwp2.local" target="local">TUAuto2 Wordpress (on local)</a></li>
	<li><a href="http://telecoms.local" target="vagrant">Multisite (Telecoms)</a></li>
	<li><a href="http://tuauto.vagrant" target="vagrant">TUAuto Wordpress (on Vagrant)</a></li>
</ul>	


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
<p>Version 0.5</p>
<p>Version 0.6</p>
