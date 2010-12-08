<div>
	<h2><?php echo htmlentities(view_manager::get_value("TAPE")); ?></h2>
	<div class="g5">
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
		
	</div>
</div>