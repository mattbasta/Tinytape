<?php
$badge = view_manager::get_value("SET_ID");
?>
<img src="/images/badges/<?php echo urlencode($badge); ?>.jpg" style="position:absolute;right:0;" />
<div>
	<h2><?php echo htmlentities(view_manager::get_value("SET_ID")); ?></h2>
	<p>In order for this badge set to activate, <strong><?php echo view_manager::get_value("REQSONGS"); ?></strong> songs must be added to a tape.</p>
	<div id="my_feed">
		<div class="feed_item badge_earned">
			<div class="feedbadge">
				<img src="/images/badges/<?php echo urlencode(view_manager::get_value("SET_ID")); ?>.small.jpg" style="display:block;float:left;" />
				<?php
				$tt_badges = view_manager::get_value("TT_BADGES");
				?>
				<strong><?php echo htmlentities($tt_badges[$badge]["title"]); ?></strong>
				<p><?php echo $tt_badges[$badge]["description"]; ?></p>
			</div>
		</div>
	</div>
	<p style="border:3px solid #666;border-radius:5px;-moz-border-radius:5px;padding:1em;"><?php echo $tt_badges[$badge]["earned_string"]; ?></p>
	<?php
	$songs = view_manager::get_value("SONGS");
	$set_id = view_manager::get_value("SET_ID");
	if(!empty($songs)) {
		?><ul><?php
		foreach($songs as $song_id=>$label) {
			?>
			<li>
				<?php echo htmlentities($label), " (", $song_id, ")"; ?> 
				<small><a onclick="return confirm('You\'re about to delete this song from the badge set.');" href="<?php echo URL_PREFIX . "admin/badge_sets/remove_song/$set_id/" . urlencode($song_id); ?>">delete</a></small>
			</li>
			<?php
		}
		?></ul><?php
	} else {
		?><p>There aren't any related songs yet. Sorry to be a bummer.</p><?php
	}
	?>
</div>
<form action="<?php echo URL_PREFIX; ?>admin/badge_sets/add_song/<?php echo $set_id; ?>" method="post" class="notice_box">
	<strong>Add Song To Set</strong>
	
	<label>Song ID</label>
	<input type="text" name="song_id" />
	
	<p class="buttons">
		<input type="submit" value="Add to Set" />
	</p>
	
</form>
<div class="warning_box">
	<strong>Delete Badge Set</strong>
	<p>
		If you're sure that you want to delete the badge set, <a href="<?php echo URL_PREFIX; ?>admin/badge_sets/delete/<?php echo urlencode($set_id); ?>">click here</a>.
	</p>
</div>