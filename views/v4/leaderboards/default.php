<h1>Top Users</h1>
<ol>
	<?php
	$items = view_manager::get_value("USERS");
	foreach($items as $item=>$score) {
		?>
		<li>
			<a href="<?php echo URL_PREFIX; ?>user/<?php echo urlencode($item); ?>"><?php echo htmlentities($item); ?></a>
			<small><?php echo $score; ?> Points</small>
		</li>
		<?php
	}
	?>
</ol>