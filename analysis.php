<?php

require "DB.class.php";
// require "Options.class.php";
// require "Post.class.php";

require "Node.class.php";
// require "Files.class.php";
// require "Taxonomy.class.php";
// require "Events.class.php";
require "FieldSet.class.php";

$d7 = new DB('d7');

$nodes = new Node($d7);
$nodeCount = $nodes->nodeCount();

$fieldSelected = isset($_GET['field']) ? $_GET['field'] : 'labels';
$data = isset($_GET['data']) ? $_GET['data'] : 0;
$item = isset($_GET['item']) ? $_GET['item'] : '';

$fieldSet = new FieldSet($d7);
$types = $fieldSet->getFieldData($fieldSelected);

$inputs = [];


if ($fieldSelected === 'labels') {
	$heading = "List of all types";
	foreach ($types as $type => $itemCount) {
		$input  = '<input type="checkbox" name="' . $type . '">';
		$input .= '<a href="analysis.php?field=' . $type.'">' . $type . '</a> ('.$itemCount.')'; 
		$inputs[] = $input;
	}
} else if ($fieldSelected && !$data) {
	$heading = "List of $fieldSelected type elements";
	foreach ($types as $key => $value) {
		$input  = '<input type="checkbox" name="' . $key . '">';
		$input .= '<a href="analysis.php?field=' .$fieldSelected. '&item=' .$value.'&data=1">' . $value . '</a>'; 
		$inputs[] = $input;	
	}
} else {
	$data = $fieldSet->getAllRecords($fieldSelected, $item);

	if ($data) {

		foreach($data as $objects) {	
			$input = '';			

			foreach ($objects as $key => $value) {
				$input .= $key . '=>' . $value;
			}
			$inputs[] = $input;
		}
	} else {
		$inputs[0] = 'No data in that table';
	}
}
?>
<!DOCTYPE html>
<html>
<head>
	<title>Drupal to Wordpress Migration</title>
	<link href="styles.css" type="text/css" rel="stylesheet"></link>
</head>
<body>
	<div class="menu block"><a href="index.php">Home</a><a href="analysis.php">Analysis</a></div>
	<div class="block">
		<form>
			<h2>Summary</h2>

			<div>
			Number of Drupal Nodes: <?= $nodeCount ?>
			<button onClick="runMigrate();return false">Import</button>
			</div>

			<div>
				<h2><?= $heading ?></h2>
				<?php foreach ($inputs as $key => $input) : ?>
					<div>
						<?= $input ?>
					</div>
				<?php endforeach; ?>
				<button>Import</button>
			</div>

		</form>
	</div>
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
	<script type="text/javascript">
		function runMigrate() {

				$(function() {
					$.ajax({
						url: "migrator.php",
						context: document.body,
						timeout: 2400,
						type: 'GET'
					}).fail(function() {

					}).always(function() {
						alert('completed')
					}).done(function() {
						$(this).addClass('done');
					});
				});
		}
	</script>
</body>
</html>