<?php
// Universal result format
?>
<li id="<?php echo $uid; ?>">
	<?php
	if((!isset($instance_id) || $instance_id == 0) && !isset($hide_view_edit)){
		?>
		<a href="<?php echo URL_PREFIX; ?>song/view/<?php echo $rid; ?>#<?php echo urlencode($rartist); ?>_<?php echo urlencode($rtitle); ?>" class="instances">
			View/Edit
		</a>
		<?php
	}
	?>
	<a href="javascript:player.start('<?php echo $uid; ?>');" class="playblock">
		<strong><?php echo htmlentities($rtitle); ?></strong>
		<?php
		if(!empty($rartist)) {
			?><br />
			by <span class="artist"><?php echo htmlentities($rartist); ?></span><?php
			if(!empty($ralbum)) {
				?> in <span class="album"><?php echo htmlentities($ralbum); ?></span><?php
			}
		}
		?>
	</a>
	<?php
	if($embeddable && $session->logged_in) {
		?>
		<div class="embedcontrols">
			<?php
			if(!isset($no_favorite) || !$no_favorite) {?>
			<a href="#" onclick="jQuery.get('<?php echo URL_PREFIX; ?>song/favorite/<?php echo $rid . ((isset($instance_id) && $instance_id != 0) ? "/$instance_id":""); ?>?uid=<?php echo $uid; ?>',function(d){eval(d);});return false;" class="favorite">Favorite</a>
			<?php } ?>
			<a href="<?php echo URL_PREFIX; ?>api/tapes/add_to_tape/<?php echo $rid, isset($instance_id)?"/$instance_id":""; ?>" class="addtotape">Add to Tape</a>
		</div>
		<?php
	}
	if($rlive || $racoustic || $rremix || $rclean) {
	?>
	<ul class="types">
		<?php
		if($rlive)
			echo '<li class="live">Live</li>';
		if($racoustic)
			echo '<li class="acoustic">Acoustic</li>';
		if($rremix)
			echo '<li class="remix">Remix</li>';
		if($rclean)
			echo '<li class="clean">Clean</li>';
		?>
	</ul>
	<?php
	}
	if(view_manager::get_value("CAN_DELETE_SONGS")) {
		?>
		<a href="#" class="song-delete" onclick="jQuery.get('<?php echo URL_PREFIX; ?>tape/delete_song/<?php echo urlencode(view_manager::get_value("ID")), "/", $rid, "/", $instance_id; ?>?uid=<?php echo $uid; ?>',function(d){eval(d);});return false;"><img src="/images/delete.png" alt="Delete Song" /></a>
		<?php
	}
	?>
	<div class="clear"></div>
	<div id="hardware_<?php echo $uid; ?>" class="hardware"></div>
	<div id="playbar_<?php echo $uid; ?>" class="playbar"></div>
</li>