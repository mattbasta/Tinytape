<div>
	<h2>"Add to Tape" badge sets</h2>
	<?php
	$sets = view_manager::get_value("BADGESETS");
	if(!empty($sets)) {
		?><ul><?php
		foreach($sets as $badgeset) {
			?>
			<li><a href="<?php echo URL_PREFIX . "admin/badge_sets/view/" . urlencode($badgeset); ?>"><?php echo $badgeset; ?></a></li>
			<?php
		}
		?></ul><?php
	} else {
		?><p>There aren't any badge sets yet. Sorry to be a bummer.</p><?php
	}
	?>
	
</div>
<form action="<?php echo URL_PREFIX; ?>admin/badge_sets/create" method="post" class="notice_box">
	<strong>Create new badge set</strong>
	
	<label>Badge Slug</label>
	<input type="text" name="slug" />
	
	<label>Required Songs</label>
	<input type="text" name="reqsongs" />
	
	<p class="buttons">
		<input type="submit" value="Create Badge Set" />
	</p>
	
</form>