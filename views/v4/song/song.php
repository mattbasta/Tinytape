<div class="g2">&nbsp;</div>
<div class="g8">
	<hgroup id="song_title">
		<h1 id="title"><?php echo htmlentities(view_manager::get_value('TITLE')); ?></h1>
		<h2 id="artist"><?php echo htmlentities(view_manager::get_value('ARTIST')); ?></h2>
		<h2 id="album"><?php echo htmlentities(view_manager::get_value('ALBUM')); ?></h2>
	</hgroup>
	<?php
	$results = view_manager::get_value('INSTANCES');
	$tempsecret = uniqid();
	$json_data = array();
	if($results === FALSE) {
		?>
		<div id="song_controls">
			(<a href="javascript:player.start('<?php
			$guess_uid = sha1("bestguess" . $tempsecret);
			echo $guess_uid;
			$json_data[$guess_uid] = array(
				"service"=>"tinytape",
				"resource"=>array(
					"id"=>view_manager::get_value('ID')
				)
			);
			?>');">play best guess</a>)
		</div>
		<div id="song_guess_hardware">
			<div id="hardware_global"></div>
			<div id="playbar_global"></div>
		</div>
		<div class="no_results">
			<p>Uh-oh. Looks like there aren't any copies of <?php echo htmlentities(view_manager::get_value('TITLE')); ?> registered in the system. No love for <?php echo htmlentities(view_manager::get_value('ARTIST')); ?>.</p>
		</div>
		<a href="<?php echo URL_PREFIX; ?>song/search/<?php echo view_manager::get_value('ID'); ?>" id="song_addworkingcopy">Find a working copy of this song</a>
		<?php
	} else {
		?>
		<div id="song_controls">
			(<a href="javascript:player.start('<?php
			$do_play = current($results);
			echo sha1($do_play["service_resource"] . $tempsecret);
			?>');">play</a>)
		</div>
		<div class="no_results scrappy">
			<p>Sometimes we don't have a great copy of a song. <a href="<?php echo URL_PREFIX; ?>song/search/<?php echo view_manager::get_value('ID'); ?>">Help yourself to a new copy</a> if you're not satisfied.</p>
		</div>
		<ol class="songlist">
		<?php
		$embeddable = true;
		$rid = view_manager::get_value('ID');
		
		foreach($results as $result) {
			$res = $result["service_resource"];
			$uid = sha1($res . $tempsecret);
			
			$rtitle = view_manager::get_value('TITLE') . ($result["version_name"] ? ' - ' . $result["version_name"] : '');
			
			if($result["remix_name"])
				$rtitle .= " [{$result["remix_name"]}]";
			
			$rlive = $result['live'];
			$racoustic = $result['acoustic'];
			$rremix = $result['remix'];
			$rclean = $result['clean'];
			
			$instance_id = $result["id"];
			
			require(PATH_PREFIX . '/result.php');
			
			$json_data[$uid] = array(
				"title"=>htmlentities($rtitle),
				"service"=>"tinytape",
				"resource"=>array(
					"id"=>view_manager::get_value('ID'),
					"instance"=>(int)$result["id"]
				)
			);
			
		}
	?>
	</ol>
	<div class="no_results scrappy bottom">
		<p>Didn't find the version you were looking for? <a href="<?php echo URL_PREFIX; ?>song/search/<?php echo view_manager::get_value('ID'); ?>?search">Find a new copy</a> of the song.</p>
	</div>
		<?php
		if(ENABLE_FACEBOOK) {
		?>
		<div id="facebook_song">
			<iframe src="http://www.facebook.com/plugins/like.php?href=<?php echo "http://tinytape.com", URL_PREFIX, "song/view/", view_manager::get_value('ID'); ?>&amp;layout=box_count&amp;show_faces=true&amp;width=450&amp;action=like&amp;font=arial&amp;colorscheme=light&amp;height=65" scrolling="no" frameborder="0" style="border:none; overflow:hidden; width:450px; height:65px;" allowTransparency="true"></iframe>
		</div>
		<?php
		}
	}
	?>
	<script type="text/javascript">
	<!--
	player.register_all(<?php echo json_encode($json_data); ?>);
	-->
	</script>
</div>