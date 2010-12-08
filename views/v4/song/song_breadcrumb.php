<nav id="song_breadcrumb">
	<strong>Everything</strong> &gt;
	<a href="<?php echo URL_PREFIX; ?>song/artist/<?php echo urlencode(view_manager::get_value('ARTIST')); ?>"><?php echo htmlentities(view_manager::get_value('ARTIST')); ?></a><?php
	if(view_manager::get_value('ALBUM')) {?> &gt;
	<a href="<?php echo URL_PREFIX; ?>song/artist/<?php echo urlencode(view_manager::get_value('ARTIST')), "/", urlencode(view_manager::get_value('ALBUM')); ?>"><?php echo htmlentities(view_manager::get_value('ALBUM')); ?></a>
	<?php } ?>
</nav>