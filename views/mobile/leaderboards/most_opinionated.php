<ol data-role="listview">
	<?php
	$items = view_manager::get_value("USERS");
	foreach($items as $item=>$score) {
		?>
		<li>
			<a href="<?php echo URL_PREFIX; ?>user/<?php echo urlencode($item); ?>"><?php echo htmlentities($item); ?></a>
			<small class="ui-li-count"><?php echo $score; ?> Favorites</small>
		</li>
		<?php
	}
	?>
</ol>