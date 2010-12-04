<h1>Top Badges</h1>
<ol class="leaderboard_badges">
	<?php
	$items = view_manager::get_value("BADGES");
	$tt_badges = view_manager::get_value("TT_BADGES");
	foreach($items as $item=>$data) {
		?>
		<li>
			<img src="/images/badges/<?php echo $tt_badges[$item]["image"]; ?>.small.jpg" />
			<strong><?php echo $tt_badges[$item]["title"]; ?></strong>
			<p><?php echo $tt_badges[$item]["description"]; ?></p>
			<small><?php echo $data; ?> Blessings</small>
			<div class="clear"></div>
		</li>
		<?php
	}
	?>
</ol>