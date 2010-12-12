<?php
$username = view_manager::get_value("USERNAME");
$score = view_manager::get_value("SCORE");
$level = getLevel($score);
?>
<div class="g7">
	<div id="user_points">
		<span class="score"><b><?php echo $score; ?></b> points</span>
		<span class="level">level <b><?php echo $level; ?></b></span>
	</div>
	<div class="clear"></div>
	<menu id="feedchooser">
		<li><a id="filter_feed" data-type="feed" class="active" href="#">Just <?php echo htmlentities($username); ?></a></li>
		<li><a id="filter_mentions" data-type="mentions" href="#">Everyone Else</a></li>
		<li><a id="filter_history" data-type="history" href="#">History</a></li>
		<li><a id="filter_favorites" data-type="favorites" href="#">Favorites</a></li>
	</menu>
	<script type="text/javascript">
	<!--
	username = "<?php echo addslashes($username); ?>";
	-->
	</script>
	<script type="text/javascript" src="<?php echo URL_PREFIX; ?>activity.js"></script>
	<section id="my_feed">
		<?php
		/*foreach(view_manager::get_value("NEWS_FEED") as $feed_item) {
			require(PATH_PREFIX . "/feed_item.php");
		}*/
		?>
	</section>
</div>
<div class="g5" id="tapelist">
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
	<?php if(ENABLE_FACEBOOK || ENABLE_TWITTER) { ?>
	<section id="my_social">
		<?php if(ENABLE_TWITTER && view_manager::get_value("TWITTER")) { ?>
		<div class="twitter">
			<span id="follow-twitterapi"></span>
			<script type="text/javascript">
			<!--
			twttr.anywhere(function (T) {
				T('#follow-twitterapi').followButton("<?php echo addslashes(htmlentities(view_manager::get_value("TWITTER"))); ?>");
			});
			-->
			</script>
		</div>
		<?php } ?>
	</section>
	<div class="clear">&nbsp;</div>
	<?php } ?>
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