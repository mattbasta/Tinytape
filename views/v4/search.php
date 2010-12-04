<div class="g8">
<!-- google_ad_section_start -->
<hgroup id="search_title">
	<h1>&quot;<?php echo htmlentities(view_manager::get_value('QUERY')); ?>&quot;</h1>
</hgroup>
	<?php
	$results = view_manager::get_value('RESULTS');
	if($results === FALSE) {
		?>
		<div id="search_noresults">
			<p>Hey, yeah, so we didn't find any songs in the system named (or by someone named) "<?php echo htmlentities(view_manager::get_value('QUERY')); ?>". On the up-side, it only takes thirty seconds to get the song into the system (no Mp3 required). Go ahead and <a href="<?php echo URL_PREFIX; ?>song/new">add the song information</a> to our database.</p>
		</div>
		<a href="<?php echo URL_PREFIX; ?>song/new" id="search_addthesong">Add the Song Info</a>
		<?php
	} else {
		?>
		<p class="scrappy"><?php
		$cres = count($results);
		echo $cres . (($cres == 1) ? " result" : " results");
		?></p>
		<ul class="songlist">
		<?php
		$tempsecret = uniqid();
		$embeddable = true;
		$instance_id = 0;
		$json_data = array();
		foreach($results as $result) {
			$uid = sha1($result->id . $tempsecret);
			
			$rtitle = $result->title;
			$rartist = $result->artist;
			$ralbum = $result->album;
			
			$rid = $result->id;
			
			require(PATH_PREFIX . '/result.php');
			
			$json_data[$uid] = array(
				"service"=>"tinytape",
				"resource"=>array("id"=>(int)$result->id),
				"metadata"=>array(
					"title"=>htmlentities($rtitle),
					"artist"=>htmlentities($rartist),
					"album"=>htmlentities($ralbum)
				)
			);
			
		}
		?>
		</ul>
		<script type="text/javascript">
		<!--
		player.register_all(<?php echo json_encode($json_data); ?>);
		-->
		</script>
		<?php
		
	?>
	<a href="<?php echo URL_PREFIX; ?>song/new" id="search_addthesong">I can't find it :(</a>
	<?php
	}
	?>
<!-- google_ad_section_end -->
</div>
<div class="g4 search_ads">
	<?php
	view_manager::add_view(VIEW_PREFIX . "advertisements");
	echo view_manager::render();
	?>
</div>