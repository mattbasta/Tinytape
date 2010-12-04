<h1>Top Songs</h1>
<ol>
	<?php
	$items = view_manager::get_value("SONGS");
	foreach($items as $item=>$data) {
		?>
		<li>
			<a href="<?php echo URL_PREFIX; ?>song/view/<?php echo urlencode($item); ?>"><?php echo htmlentities($data["title"]); ?></a> by <?php echo htmlentities($data["artist"]); ?>
			<small><?php echo $data["score"]; ?> Plays</small>
		</li>
		<?php
	}
	?>
</ol>