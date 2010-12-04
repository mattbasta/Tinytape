<?php
$username = view_manager::get_value("USERNAME");
?>
<div id="user_follow">
	<?php
	if(!view_manager::get_value("FOLLOWING")) {
		?>
		<button class="follow">Follow <?php echo htmlentities($username); ?></button>
		<?php
	} else {
		?>
		<button>Unfollow <?php echo htmlentities($username); ?></button>
		<?php
	}
	?>
</div>
<div style="text-align:center;">
	<div data-role="controlgroup" data-type="horizontal">
		<a data-type="fullfeed" class="active" href="#" data-role="button" data-inline="true">All</a>
		<a data-type="followeefeed" href="#" data-role="button" data-inline="true">Mentions</a>
		<a data-type="history" href="#" data-role="button" data-inline="true">History</a>
		<a data-type="favorites" href="#" data-role="button" data-inline="true">Favorites</a>
	</div><!-- /controlgroup -->
</div>
<script type="text/javascript" src="<?php echo URL_PREFIX; ?>/activity.js"></script>
<ul id="my_feed" data-role="listview" data-theme="c">
	<?php
	foreach(view_manager::get_value("NEWS_FEED") as $feed_item) {
		$fi = json_decode($feed_item, true);
		if($fi["type"] == "songs")
			continue;
		require(PATH_PREFIX . "/feed_item.php");
	}
	?>
</ul>
<div class="g5" id="tapelist">
	<section id="my_badges">
		<?php
		$tt_badges = view_manager::get_value("TTBADGES");
		foreach(view_manager::get_value("BADGES") as $badge) {
			$b = $tt_badges[$badge];
			?>
			<a href="/images/badges/<?php echo $b["image"]; ?>.jpg" class="fancybox">
				<img src="/images/badges/<?php echo $b["image"]; ?>.small.jpg" alt="<?php echo htmlentities($b["description"]); ?>" />
			</a>
			<?php
		}
		?>
	</section>
	<section id="my_tapes">
		<?php
		view_manager::add_view(VIEW_PREFIX . "snippets/tapelist");
		echo view_manager::render();
		?>
	</section>
	<div class="clear">&nbsp;</div>
	<section id="user_following">
		<?php
		$following = view_manager::get_value("FOLLOWING_USERS");
		if($following) {
			?>
			<p><?php echo htmlentities($username); ?> follows:</p><?php
			echo "<ul>";
			$count = 0;
			foreach($following as $followee=>$stats) {
				?>
				<li><a href="<?php echo URL_PREFIX; ?>user/<?php echo urlencode($followee); ?>">
					<b><?php echo htmlentities($followee); ?></b><br />
					<small>Level <?php echo $stats["level"]; ?></small>
				</a></li>
				<?php
				if(++$count == 10) {
					break;
				}
			}
			echo "</ul>";
			if($count == 10) {
				?><a id="my_allfollowing" href="<?php echo URL_PREFIX; ?>api/ajax/following/<?php echo urlencode($username); ?>" class="fancybox">Everybody <?php echo htmlentities($username); ?> Follows</a><?php
			}
		} else {
			?><p><?php echo htmlentities($username); ?> is not following anybody.</p><?php
		}
		?>
	</section>
</div>
<script type="text/javascript">
<!--
$(document).ready(function() {
	var ub = $("#user_follow button");
	ub.click(function() {
		$.getJSON(
			"<?php echo URL_PREFIX; ?>api/ajax/toggle_follow/<?php echo urlencode($username); ?>",
			function(data) {
				ub.html(data.new_text);
				ub.toggleClass("follow");
				if(data.badge) {
					badge(data.badge);
				}
			}
		);
	});
});
-->
</script>