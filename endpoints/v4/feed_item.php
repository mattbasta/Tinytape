<?php

$feed_hash = md5($feed_item);
$fi = json_decode($feed_item, true);
?>
<div id="fi_<?php echo $feed_hash; ?>" class="feed_item <?php echo $fi["type"]; ?>">
	<?php
	$feed_username = view_manager::get_value('FEED_USERNAME');
	if($feed_username == $session->username || $session->admin) {
	?>
	<a class="fi_delete" href="#" onclick="return delete_post('<?php echo addslashes(htmlentities($feed_username)); ?>', '<?php echo view_manager::get_value('FEED_TYPE'); ?>', '<?php echo $feed_hash; ?>');">Delete</a>
	<?php
	}
	?>
	<a href="<?php echo URL_PREFIX; ?>user/<?php echo urlencode($fi["username"]); ?>" class="username"><?php echo htmlentities($fi["username"]); ?></a>
<?php
switch($fi["version"]) {
	case 1:
		switch($fi["type"]) {
			case "shout":
				
				$text = $fi["payload"]["text"];
				$text = shout_process($text);
				
				?><p class="payload"><?php
				echo $text;
				?></p><?php
				break;
			case "songs":
				?><div class="feedsongs"><?php
				$count = $fi["payload"]["song_count"] - $fi["payload"]["song_count"] % 5;
				foreach($fi["payload"]["songs"] as $song) {
				?>
				<a href="<?php echo URL_PREFIX; ?>song/view/<?php echo $song["id"]; ?>">
					<img src="<?php echo URL_PREFIX; ?>api/albumart/redirect?title=<?php echo urlencode($song["title"]); ?>&amp;artist=<?php echo urlencode($song["artist"]); ?>&amp;size=large" alt="<?php echo htmlentities($song["title"]); ?>" height="90" width="90" />
				</a> 
				<?php
					$count--;
					if($count == 0)
						break;
				}
				?></div><?php
				break;
			case "badge_earned":
				
				$badge = $fi["payload"]["badge"];
				if(!isset($tt_badges))
					$tt_badges = view_manager::get_value("TTBADGES");
				$ttbadge = $tt_badges[$badge];
				?>
				<div class="feedbadge">
					<a href="/images/badges/<?php echo $ttbadge["image"]; ?>.jpg" title="<?php echo htmlentities($ttbadge["title"]); ?>" class="fancybox">
					<img src="/images/badges/<?php echo $ttbadge["image"]; ?>.small.jpg" alt="<?php echo htmlentities($ttbadge["title"]); ?>" class="badgephoto" />
					</a>
					<strong><?php echo htmlentities($ttbadge["title"]); ?></strong>
					<p><?php echo htmlentities($ttbadge["description"]); ?></p>
				</div>
				<div class="clear"></div>
				<?php
				break;
			case "new_tape":
				
				$tape = $fi["payload"]["name"];
				if(!isset($r))
					$r = view_manager::get_value("redis");
				if(!$r->sContains("tinytape_tapes", $tape))
					break;
				?>
				<p> just created a tape called </p>
				<strong style="background:#<?php echo $fi["payload"]["color"]; ?>"><a href="<?php echo URL_PREFIX; ?>tape/<?php echo urlencode($tape); ?>"><?php echo htmlentities($fi["payload"]["title"]); ?></a></strong>
				<?php
				break;
			case "following":
				$pt = $fi["payload"]["target"];
				if(!is_array($pt))
					$pt = array($pt);
				?><p> is now following<?php
				$at = 1;
				$ptc = count($pt);
				foreach($pt as $p) {
					$name = ($p == $session->username) ? "you" : $p;
					if($at == $ptc && $at > 1)
						echo " and ";
					?> <a href="<?php echo URL_PREFIX; ?>user/<?php echo urlencode($p); ?>"><?php echo $name; ?></a><?php
					if($at < $ptc && $ptc > 2)
						echo ",";
					$at++;
				}
				echo "</p>";
				break;
		}
		break;
}
?>
</div>