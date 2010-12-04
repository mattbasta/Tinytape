<?php

if(!isset($fi))
	$fi = json_decode($feed_item, true);
?>
<li class="feed_item <?php echo $fi["type"]; ?>">
<?php
$username = $fi["username"];
switch($fi["version"]) {
	case 1:
		switch($fi["type"]) {
			case "shout":
				
				$text = $fi["payload"]["text"];
				$text = htmlspecialchars($text);
				
				if($username != $session->username) {
					?><a href="<?php echo URL_PREFIX; ?>user/<?php echo urlencode($username); ?>"><?php
					echo htmlentities($username), "</a>: ", $text;
				} else
					echo htmlentities($username), ": ", $text;
				break;
			case "songs":
				?><div class="feedsongs"><?php
				$count = $fi["payload"]["song_count"] - $fi["payload"]["song_count"] % 5;
				foreach($fi["payload"]["songs"] as $song) {
				?>
				<a href="<?php echo URL_PREFIX; ?>song/view/<?php echo $song["id"]; ?>">
					<img src="<?php echo NM_URL_PREFIX; ?>api/albumart/redirect?title=<?php echo urlencode($song["title"]); ?>&amp;artist=<?php echo urlencode($song["artist"]); ?>&amp;size=large" alt="<?php echo htmlentities($song["title"]); ?>" height="90" width="90" />
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
				$ttbadges = view_manager::get_value("TTBADGES");
				$ttbadge = $ttbadges[$badge];
				?>
				<img src="/images/badges/<?php echo $ttbadge["image"]; ?>.small.jpg" alt="<?php echo htmlentities($ttbadge["title"]); ?>" class="badgephoto" />
				<h3><a href="<?php echo URL_PREFIX, "badge/$badge" ?>"><?php echo htmlentities($fi["username"]); ?> earned <?php echo htmlentities($ttbadge["title"]); ?></a></h3>
				<p><strong><?php echo htmlentities($ttbadge["title"]); ?></strong><br />
				<?php echo htmlentities($ttbadge["description"]); ?></p>
				<?php
				break;
			case "following":
				$pt = $fi["payload"]["target"];
				if(!is_array($pt))
					$pt = array($pt);
				?>
				<a href="<?php echo URL_PREFIX; ?>user/<?php echo urlencode($username); ?>"><?php
				
				echo htmlentities($username), " is now following ";
				
				$at = 1;
				$ptc = count($pt);
				foreach($pt as $p) {
					$name = ($p == $session->username) ? "you" : $p;
					if($at == $ptc && $at > 1)
						echo " and ";
					echo htmlentities($name);
					if($at < $ptc && $ptc > 2)
						echo ",";
					$at++;
				}
				echo "</a>";
				break;
		}
		break;
}
?>
</li>