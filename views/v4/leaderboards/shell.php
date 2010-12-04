<div class="g3 lb_sidebar">
	<h1>Leaderboards</h1>
	<ul>
		<?php
		function n($name) {
			if($name == view_manager::get_value("PAGE")) {
				echo ' class="current"';
			}
		}
		?>
		<li<?php echo n("user_score"); ?>><a href="<?php echo URL_PREFIX; ?>leaderboards/">Top Users</a></li>
		<li<?php echo n("top_songs"); ?>><a href="<?php echo URL_PREFIX; ?>leaderboards/top_songs">Top Songs</a></li>
		<li<?php echo n("top_badges"); ?>><a href="<?php echo URL_PREFIX; ?>leaderboards/top_badges">Top Badges</a></li>
		<li<?php echo n("most_songs"); ?>><a href="<?php echo URL_PREFIX; ?>leaderboards/most_songs">Most Songs Added</a></li>
		<li<?php echo n("most_favorited"); ?>><a href="<?php echo URL_PREFIX; ?>leaderboards/most_favorited">Most Favorited Songs</a></li>
		<li<?php echo n("most_opinionated"); ?>><a href="<?php echo URL_PREFIX; ?>leaderboards/most_opinionated">Most Opinionated Users</a></li>
	</ul>
</div>
<div class="g9 lb_main<?php if(view_manager::get_value("PAGE") != "top_badges") {echo " lb_scale";} ?>">
<?php
echo view_manager::render();
?>
</div>