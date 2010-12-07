<?php

define("SEARCH_PROVIDER", "sphinx");
define("ENABLE_FACEBOOK", true);
define("FB_ID", "192417860416");
define("FB_SECRET", "364349eb3403b57d60520fa0b76a0e14");
define("COLOR_REGEX", "/^[0-9A-Za-z]*$/");
define("TAPE_REGEX", "/^[0-9A-Za-z\\-_]*$/");
define("EDIT_SONG_MIN_POINTS", 750);

define("THROTTLE_PERHOUR", 50);
define("THROTTLE_PERHOUR_ENABLE", true);
define("THROTTLE_DUPLICATE_ENABLE", true);

function user_exists($username) {
	global $r;
	return $r->sContains("tinytape_users", $username);
}

// TODO: Push this to AWS SQS and do it with a daemon
function push_to_feed($feed, $post) {
	global $r;
	
	$collapsible_types = array(
		"songs"=>true,
		"following"=>true
	);
	
	if(isset($collapsible_types[$post["type"]])) {
		
		$last = last_feed_item($feed);
		if($last["type"] == $post["type"] &&
		   $last["username"] == $post["username"] &&
		   $last["version"] == $post["version"]) {
			$r->lPop($feed);
			
			switch($post["type"]) {
				case "songs":
					$last["payload"]["songs"] = array_merge($post["payload"]["songs"], $last["payload"]["songs"]);
					$last["payload"]["song_count"] += $post["payload"]["song_count"];
					break;
				case "following":
					$last_target = $last["payload"]["target"];
					if(!is_array($last_target))
						$last_target = array($last_target);
					$last_target = array_merge($last_target, $post["payload"]["target"]);
					$last_target = array_unique($last_target); // Eliminates "Now following you and you" syndrome
					$last["payload"]["target"] = $last_target;
					break;
			}
			
			//push_to_feed($feed, $last);
			$r->lPush($feed, json_encode($last));
			return;
			
		}
		
	}
	
	$r->lPush($feed, json_encode($post));
}
function last_feed_item($feed) {
	global $r;
	return json_decode($r->lGet($feed, 0), true);
}
function push_to_follower_feeds($post, $username="", $exclude="") {
	global $session, $r;
	
	if(empty($username))
		$username = $session->username;
	if(empty($exclude))
		$exclude = array();
	
	$following = $r->sMembers("tinytape_following_$username");
	if($following) {
		foreach($following as $followee) {
			push_to_feed("tinytape_followeefeed_$followee", $post);
			foreach($exclude as $exclusion)
				if($exclusion == $followee)
					continue 2;
			push_to_feed("tinytape_fullfeed_$followee", $post);
		}
	}
	
}

function shout_process($text, $url_prefix="") {
	if(empty($url_prefix))
		$url_prefix = URL_PREFIX;
	$text = htmlspecialchars($text);
	$text = ereg_replace("[[:alpha:]]+://[^<>[:space:]]+[[:alnum:]/]","<a target=\"_blank\" href=\"\\0\">\\0</a>", $text);
	$text = preg_replace('!(^|\W)@([a-z0-9_]+)!', ' @<a href="' . $url_prefix . 'user/$2">$2</a>', $text);
	return $text;
}

$levels = array(
	2=>50,
	3=>125,
	4=>250,
	5=>400,
	6=>700,
	7=>1100,
	8=>1500
);
function getLevel($user_score) {
	global $levels;
	
	if($user_score < 2500) {
		$user_level = 1;
		$clev = count($levels);
		while($levels[$user_level + 1] < $user_score && $user_level < $clev + 1)
			$user_level++;
	} else {
		$user_level = floor(($user_score - 1500) / 1000) + 8;
	}
	return $user_level;
}
if($session->logged_in) {
	$user_score = (int)$r->zScore("tinytape_scores", $session->username);
	if(!$user_score)
		$user_score = 0;
	
	$user_level = getLevel($user_score);
	
	function bumpsLevel($increment=1) {
		global $levels, $user_level, $user_score;
		$after = $user_score + $increment;
		
		return getLevel($after) > $user_level;
	}
	
	view_manager::set_value("USER_SCORE", $user_score);
	view_manager::set_value("USER_LEVEL", $user_level);
	
}
