<?php
$results = view_manager::get_value('RESULTS');
if($results === FALSE) {
	?>
	<div id="search_noresults">
		<p>Hey, yeah, so we didn't find anything related to "<?php echo htmlentities(view_manager::get_value('QUERY')); ?>". When you get back to your computer, feel free to add the song (and rack up some points for it!).</p>
	</div>
	<?php
} else {
	?>
	<ul data-role="listview" data-inset="true">
	<?php
	$tempsecret = uniqid();
	$embeddable = true;
	$instance_id = 0;
	foreach($results as $result) {
		$uid = sha1($result->id . $tempsecret);
		
		$rtitle = $result->title;
		$rartist = $result->artist;
		$ralbum = $result->album;
		
		$rid = $result->id;
		
		require(PATH_PREFIX . '/result.php');
		
	}
	?>
	</ul>
	<?php
	
}
?>