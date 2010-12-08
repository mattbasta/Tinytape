<?php
if(view_manager::get_value("BONUS")) {
	?><section id="bonus">
	<h1>Hooray!</h1>
	<p>You just earned yourself <b>10</b> bonus points! See that? Good karma, right there.</p>
	</section><?php
}
?>
<hgroup id="song_title">
	<h1 id="title"><?php echo htmlspecialchars(view_manager::get_value('TITLE')); ?></h1><br />
	<h2 id="artist"><?php echo htmlspecialchars(view_manager::get_value('ARTIST')); ?></h2><br />
	<h2 id="album"><?php echo htmlspecialchars(view_manager::get_value('ALBUM')); ?></h2>
</hgroup>
<div class="g2">&nbsp;</div>
<div class="g8">
	<p class="scrappy">Below are all of the copies of what we think "<?php echo htmlentities(view_manager::get_value('TITLE')); ?>" might be. Have a listen to some of them. If you think it's legit, save it using the controls on the right.</p>
	<?php
	$results = view_manager::get_value('RESULTS');
	if(empty($results)) {
		?>
		<div class="no_results scrappy bottom" style="background:#c55;color:#fff;">
			<p>We've got nothing. Sorry. It probably isn't a great song, anyway.</p>
		</div>
		<?php
	} else {
		?>
		<ol class="songlist">
		<?php
		$tempsecret = uniqid();
		$embeddable = false;
		$hide_view_edit = true;
		$json_data = array();
		foreach($results as $result) {
			$uid = sha1($result['service_resource'] . $tempsecret);
			
			$rtitle = $result['title'];
			
			// Do a check to see if we have some crazy capital live floating around
			// I know it can return false positives, but I'd rather that than false negatives
			$rlive = strpos($rtitle, 'LIVE') !== false;
			
			$ltitle = strtolower($rtitle);
			$ltitle = str_replace('(','',$ltitle);
			$ltitle = str_replace('[','',$ltitle);
			$ltitle = str_replace(')','',$ltitle);
			$ltitle = str_replace(']','',$ltitle);
			
			if(	!$rlive && (
				strpos($ltitle, '(live') !== false ||
				strpos($ltitle, ' live in ') !== false ||
				strpos($ltitle, ' live/') !== false ||
				strpos($ltitle, ' live on ') !== false ||
				strpos($ltitle, ' live @ ') !== false ||
				strpos($ltitle, ' live at ') !== false ||
				strpos($ltitle, ' - live') !== false ||
				strpos($ltitle, ' live from ') !== false ||
				strpos($ltitle, ' performed ') !== false)
					)
				$rlive = true;
			$racoustic = strpos($ltitle, 'acoustic') !== false;
			$rremix = strpos($ltitle, 'remix') !== false;
			$rclean = strpos($ltitle, 'clean') !== false;
			
			require(PATH_PREFIX . '/result.php');
			
			$json_data[$uid] = array(
				"service"=>"youtube",
				"resource"=>$result['service_resource'],
				"title"=>htmlentities(utf8_decode($rtitle)),
				"properties"=>array(
					"live"=>$rlive,
					"acoustic"=>$racoustic,
					"remix"=>$rremix,
					"clean"=>$rclean
				)
			);
			
		}
		?>
		</ol>
		<script type="text/javascript" defer>
		<!--
		player.register_all(<?php echo json_encode($json_data); ?>);
		-->
		</script>
		<?php
	}
	?>
</div>
<div id="ssearch_sidecar" style="display:none;">
	<div id="sidecar">
		<p><strong id="sc_title"></strong><br />Is this a decent version of <?php echo htmlentities(view_manager::get_value('TITLE')); ?>? Tell us about it to help make Tinytape a little smarter.</p>
		<form action="#" method="post" id="sc_form">
		<p class="buttons">
			<input type="submit" value="This is a good version" />
		</p>
		<?php
		$form = getLib('form');
		echo $form->hidden('sc_service','youtube');
		echo $form->hidden('sc_service_resource','');
		?>
		<label for="version_name" id="version_name_context">Version Name</label>
		<input id="version_name" name="version_name" type="text" value="" />
		
		<label class="singular" id="acoustic_context"><input id="acoustic" name="acoustic" type="checkbox" class="checkbox" value="true" /> <span>This version is acoustic</span></label>
		<label class="singular" id="clean_context"><input name="clean" type="checkbox" class="checkbox" value="true" /> <span>This is the clean version (radio edit)</span></label>
		
		
		<label class="singular" id="remix_context"><input id="remix" name="remix" type="checkbox" class="checkbox" value="true" onchange="g('remix_values').style.display=this.checked?'block':'none';" /> <span>It's a remix</span></label>
		
		<div id="remix_values" style="display:none;">
			<label for="remix_name" id="remix_name_context">Remix Name</label>
			<input id="remix_name" name="remix_name" type="text" value="" />
			
			<label for="remix_artist" id="remix_artist_context">Remix Artist</label>
			<input id="remix_artist" name="remix_artist" type="text" value="" />
		</div>
		
		<label class="singular" id="live_context"><input id="live" name="live" type="checkbox" class="checkbox" value="true" onchange="g('event_name').style.display=this.checked?'block':'none';" /> <span>It's a live recording</span></label>
		
		<div id="event_name" style="display:none;">
			<label for="event_name">Live Event Name</label>
			<input id="event_name" name="event_name" type="text" value="" />
		</div>
		
		<p class="buttons">
			<input type="submit" value="Save that shiz" />
		</p>
		</form>
		
	</div>
	<div id="sc_thanks" style="display:none;">
		Thanks for that!<br />
		<a id="sc_add" href="<?php echo URL_PREFIX; ?>api/tapes/add_to_tape/<?php echo view_manager::get_value('ID'); ?>/" class="addtotape">Add to Tape</a>
	</div>
</div>
<script type="text/javascript">
<!--
function g(x){return document.getElementById(x);}
function resizecar(id) {
	var ssearch = g("ssearch_sidecar");
	var item = g(id);
	ssearch.style.top = (item.offsetTop - ssearch.offsetHeight / 2 + item.offsetHeight / 2) + "px";
}
function loadsidecar(id) {
	var reg = player._register[id];
	
	var ssearch = g("ssearch_sidecar");
	ssearch.style.display = "block";
	
	g('sc_thanks').style.display = 'none';
	g("sidecar").style.display = "block";
	
	resizecar(id);
	
	
	g('sc_title').innerHTML = reg.title;
	g('sc_service_resource').value = reg.resource;
	
	g('version_name').value = '';
	g('remix_name').value = '';
	g('event_name').value = '';
	g('remix_artist').value = '';
	g('acoustic').checked = reg.properties.acoustic;
	g('clean').checked = reg.properties.clean;
	g('remix').checked = reg.properties.remix;
	g('live').checked = reg.properties.live;
	
}
$(document).ready(function() {
	player.onstart = function() {loadsidecar(player.playing);};
	$('#sc_form').submit(function() {
		$.post(
			"<?php echo URL_PREFIX; ?>song/addinstance/<?php echo view_manager::get_value('ID'); ?>",
			$(this).serialize(),
			function(data) {
				data = JSON.parse(data);
				g("sc_add").href = "<?php echo URL_PREFIX; ?>api/tapes/add_to_tape/<?php echo view_manager::get_value('ID'); ?>/" + data.instance;
				if(data.badge)
					badge(data.badge);
			}
		);
		g("sidecar").style.display = "none";
		g("sc_thanks").style.display = "block";
		resizecar(player.playing);
		return false;
	});
});
-->
</script>