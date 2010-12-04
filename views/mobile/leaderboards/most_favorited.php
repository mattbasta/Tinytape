<ol data-role="listview">
	<?php
	$items = view_manager::get_value("SONGS");
	foreach($items as $item=>$data) {
		?>
		<li>
			<a href="<?php echo URL_PREFIX; ?>song/view/<?php echo urlencode($item); ?>"><?php echo htmlentities($data["title"]); ?></a><br />
			by <?php echo htmlentities($data["artist"]); ?>
			<small class="ui-li-count"><?php echo $data["score"]; ?> Favorites</small>
		</li>
		<?php
	}
	?>
</ol>