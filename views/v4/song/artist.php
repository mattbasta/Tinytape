<?php
$artist = view_manager::get_value("NAME");

view_manager::add_view(VIEW_PREFIX . "song/song_breadcrumb");
echo view_manager::render();
?>
<div class="g2">
	<img src="<?php echo URL_PREFIX; ?>api/artistart/redirect?artist=<?php echo urlencode($artist); ?>" />
	<?php
	if(ENABLE_FACEBOOK) {?>
	<iframe src="http://www.facebook.com/plugins/like.php?href=<?php echo "http://tinytape.com", URL_PREFIX, "song/artist/", urlencode($artist); ?>&amp;layout=standard&amp;show_faces=false&amp;width=150&amp;action=like&amp;colorscheme=light&amp;height=45" scrolling="no" frameborder="0" style="border:none; overflow:hidden; width:150px; height:45px;" allowTransparency="true"></iframe>
	<?php } ?>
</div>
<div class="g10">
	<hgroup>
		<h1><?php echo htmlentities($artist); ?></h1>
	</hgroup>
	<?php
	$albums = view_manager::get_value("ALBUMS");
	if(!empty($albums) && count($albums) > 1) {
	?>
	<section class="albumscroller">
		<?php
		foreach($albums as $album_raw) {
			$album = $album_raw["album"];
			if(empty($album))
				continue;
			?>
			<a class="historyitem" href="<?php echo URL_PREFIX; ?>song/artist/<?php echo urlencode($artist), "/", urlencode($album); ?>" title="<?php echo htmlentities($album); ?>">
				<img src="<?php echo URL_PREFIX; ?>api/albumart/redirect?album=<?php echo urlencode($album); ?>&amp;artist=<?php echo urlencode($artist); ?>&amp;size=medium" />
			</a>
			<?php
		}
		?>
	</section>
	<?php
	}
	
	$tempsecret = uniqid();
	$json_data = array();
	$results = view_manager::get_value('SONGS');
	if($results === FALSE) {
		?>
		<div class="no_results">
			<p>Uh-oh. Doesn't look like we have any <?php echo htmlentities($artist); ?> registered in the system. You should go <a href="<?php echo URL_PREFIX; ?>song/new">add one of his/her songs</a>.</p>
		</div>
		<?php
	} else {
		/*
		?>
		<div id="song_controls">
			(<a href="javascript:player.start('<?php
			$do_play = current($results);
			echo sha1($do_play["id"] . $tempsecret);
			?>');">play</a>)
		</div>
		*/?>
		<div class="no_results scrappy">
			<p><?php echo count($results); ?> songs</p>
		</div>
		<ol class="songlist">
		<?php
		$embeddable = true;
		$rid = view_manager::get_value('ID');
		
		foreach($results as $result) {
			
			$uid = sha1($result['id'] . $tempsecret);
			
			$rtitle = $result['title'];
			$rartist = $result['artist'];
			$ralbum = $result['album'];
			
			$rid = $result['id'];
			
			require(PATH_PREFIX . '/result.php');
			
			$json_data[$uid] = array(
				"service"=>"tinytape",
				"resource"=>array("id"=>(int)$result['id']),
				"metadata"=>array(
					"title"=>htmlentities($rtitle),
					"artist"=>htmlentities($rartist),
					"album"=>htmlentities($ralbum)
				)
			);
			
			
		}
		?>
		</ol>
		<?php
	}
	?>
	<div class="no_results scrappy bottom">
		<p>Didn't find the song you were looking for? <a href="<?php echo URL_PREFIX; ?>song/new">Go add it.</a> It's a piece of cake, trust us.</p>
	</div>
	<script type="text/javascript">
	<!--
	player.register_all(<?php echo json_encode($json_data); ?>);
	-->
	</script>
</div>