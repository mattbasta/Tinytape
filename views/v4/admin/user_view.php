<div>
	<h2><?php echo htmlentities(view_manager::get_value("USERNAME")); ?></h2>
	<div class="g5">
		<section>
			<h3>Badges</h3>
			<?php
			$badges = view_manager::get_value("BADGES");
			$username = view_manager::get_value("USERNAME");
			$tt_badges = view_manager::get_value("TTBADGES");
			if(!empty($badges)) {
				?><ul><?php
				foreach($badges as $badge) {
					?>
					<li><a href="/images/badges/<?php echo $tt_badges[$badge]["image"]; ?>.jpg" class="fancybox"><?php echo $badge; ?></a> (<a href="<?php echo URL_PREFIX . "admin/user/remove_badge/" . urlencode($username) . "/" . urlencode($badge); ?>">delete</a>)</li>
					<?php
				}
				?></ul><?php
			} else {
				?><p>This user has no badges.</p><?php
			}
			?>
			<form action="<?php echo URL_PREFIX; ?>admin/user/add_badge/<?php echo urlencode($username); ?>" method="post" class="notice_box">
				<strong>Add Badge to User</strong>
				
				<p class="inliner">
					<label>Badge ID</label>
					<select name="badge">
						<?php
						foreach($tt_badges as $ttbadge=>$b) {
							?><option><?php echo $ttbadge; ?></option><?php
						}
						?>
					</select>
					<input type="submit" value="Add to User" style="width:auto;" />
				</p>
				
			</form>
		</section>
		<section>
			<h3>Shuffled Tapes</h3>
			<?php
			$shuffled = view_manager::get_value("SHUFFLED");
			if(!empty($shuffled)) {
				?><ul><?php
				foreach($shuffled as $shuff) {
					?>
					<li style="float:left;width:50%;"><?php echo $shuff; ?></li>
					<?php
				}
				?></ul><?php
			} else {
				?><p>This user has not shuffled any tapes.</p><?php
			}
			?>
		</section>
		<section>
			<h3>Controls</h3>
			<ul>
				<li><a href="<?php echo URL_PREFIX; ?>/admin/user/delete_history/<?php echo urlencode(view_manager::get_value("USERNAME")); ?>">Delete User History</a></li>
				<li><a href="<?php echo URL_PREFIX; ?>/admin/user/delete_feed/<?php echo urlencode(view_manager::get_value("USERNAME")); ?>">Delete User Feeds</a></li>
				<li><a href="<?php echo URL_PREFIX; ?>/admin/user/delete_favorites/<?php echo urlencode(view_manager::get_value("USERNAME")); ?>">Delete User Favorites</a></li>
			</ul>
		</section>
	</div>
	<div class="g7">
		<menu id="feedchooser">
			<li><a id="filter_fullfeed" data-type="fullfeed" href="#">Full Feed</a></li>
			<li><a id="filter_feed" data-type="feed" href="#">Feed</a></li>
			<li><a id="filter_followeefeed" data-type="followeefeed" href="#">Followee</a></li>
			<li><a id="filter_history" data-type="history" href="#">History</a></li>
			<li><a id="filter_searchhistory" data-type="searchhistory" href="#">Searches</a></li>
			<li><a id="filter_favorites" data-type="favorites" href="#">Favorites</a></li>
			<li><a id="filter_mentions" data-type="mentions" href="#">Mentions</a></li>
		</menu>
		<script type="text/javascript"> 
		<?php
		echo "username = '", htmlentities($username), "';\n";
		?>
		-->
		</script> 
		<script type="text/javascript" src="<?php echo URL_PREFIX; ?>activity.js"></script>
		<section id="my_feed">
			<div id="feed_empty">
				Select a feed to load for this user.
			</div>
		</section>
	</div>
</div>