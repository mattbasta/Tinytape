<?php

function bless_badge($badge, $username="") {
	global $r, $session, $tt_badges;
	
	if(empty($username))
		$username = $session->username;
	
	$r->sAdd("tinytape_badges_" . $username, $badge);
	$r->zIncrBy("tinytape_hasbadges", 1, $username);
	$r->zIncrBy("tinytape_badgecount", 1, $badge);
	
	$post = array(
		"timestamp"=>$_SERVER["REQUEST_TIME"],
		"username"=>$username,
		"version"=>1,
		"type"=>"badge_earned",
		"payload"=>array(
			"badge"=>$badge
		)
	);
	
	push_to_follower_feeds($post, $username);
	
	push_to_feed("tinytape_feed_$username", $post);
	push_to_feed("tinytape_fullfeed_$username", $post);
	
	post_blessing_fb($badge, $username);
	
}

function post_blessing_fb($badge, $username) {
	global $r, $tt_badges;
	
	$ttbadge = $tt_badges[$badge];
	
	if(ENABLE_FACEBOOK && $uid = $r->hGet("tinytape_facebook", $username)) {
		
		$token = $r->hGet("tinytape_facebook_token", $username);
		
		$fields_raw = array(
			// I could have sworn FB allowed HTML in stream posts :(
			//"message"=>shout_process($post_text, "http://tinytape.com" . URL_PREFIX),
			"message"=>"I just earned the {$ttbadge["title"]} on Tinytape!",
			"attachment"=>json_encode(array(
				"name"=>$ttbadge["title"],
				"href"=>"http://tinytape.com/user/$username",
				"caption"=>$ttbadge["description"],
				"media"=>array(
					array(
						"type"=>"image",
						"src"=>"http://tinytape.com/images/badges/{$ttbadge["image"]}.small.jpg",
						"href"=>"http://tinytape.com" . URL_PREFIX . "user/$username"
					)
				)
			)),
			"uid"=>$uid,
			"target_id"=>$uid
		);
		$fields = "";
		foreach($fields_raw as $key=>$value) { $fields .= $key.'='.urlencode($value).'&'; }
		file_get_contents("https://api.facebook.com/method/stream.publish?" . $fields . $token);
		
		$r->zIncrBy("tinytape_fbposts", 1, $username);
		$r->incr("tinytape_autofbposts_total", 1);
		
	}
}

function post_blessing_fb_graph($badge, $username) {
	global $r, $tt_badges;
	
	$ttbadge = $tt_badges[$badge];
	
	if(ENABLE_FACEBOOK && $uid = $r->hGet("tinytape_facebook", $username)) {
		
		$token = $r->hGet("tinytape_facebook_token", $username);
		
		$fields_raw = array(
			// I could have sworn FB allowed HTML in stream posts :(
			//"message"=>shout_process($post_text, "http://tinytape.com" . URL_PREFIX),
			"message"=>"I just earned the {$ttbadge["title"]} on Tinytape!",
			"picture"=>"http://tinytape.com/images/badges/{$ttbadge["image"]}.small.jpg?" . time(),
			"name"=>$ttbadge["title"],
			"caption"=>$ttbadge["description"],
			"link"=>"http://tinytape.com" . URL_PREFIX . "user/$username",
			"actions"=>json_encode(array(
				"name"=>"Earn your own on Tinytape",
				"link"=>"http://tinytape.com" . URL_PREFIX
			))
		);
		$fields = "";
		foreach($fields_raw as $key=>$value) { $fields .= $key.'='.urlencode($value).'&'; }
		
		$ch = curl_init("https://graph.facebook.com/$uid/feed");
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $fields.$token);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER  ,1);  // RETURN THE CONTENTS OF THE CALL
		echo curl_exec($ch);
		
		$r->zIncrBy("tinytape_fbposts", 1, $username);
		$r->incr("tinytape_autofbposts_total", 1);
		
	}
}

$tt_badges = array(
	"fast_signup"=>array(
		"image"=>"stopwatch",
		"title"=>"Fast Signup Badge",
		"description"=>"Sign up in under one minute"
	),
	"slow_signup"=>array(
		"image"=>"slowpoke",
		"title"=>"Slowpoke Badge",
		"description"=>"Take longer than 60 seconds to sign up"
	),
	"cluso"=>array(
		"image"=>"cluso",
		"title"=>"Inspector Cluso Badge",
		"description"=>"Try to find something that doesn't exist"
	),
	"shuffler"=>array(
		"image"=>"slevin",
		"title"=>"Kansas City Shuffle Badge",
		"description"=>"You look left, they fall right; Shuffle 15 tapes"
	),
	"zombie"=>array(
		"image"=>"zombie",
		"title"=>"Zombie Badge",
		"description"=>"BRAAAAIIIIINNNNSSSSSS.........listen to the same song 100 times....."
	),
	"enthusiast"=>array(
		"image"=>"enthusiast",
		"title"=>"Enthusiast Badge",
		"description"=>"I like to listen to music. Listen to 1000 songs.",
		"earned_string"=>"Some people like to listen to songs. I only listen to magic songs. Or neutral songs. But not when the watchers are around. I hear you've listened to 1000 of them. That's very neutral."
	),
	"aficionado"=>array(
		"image"=>"aficionado",
		"title"=>"Aficionado Badge",
		"description"=>"Wow, you've listened to 2500 songs. Like Beethoven, except less deaf."
	),
	"drofmusic"=>array(
		"image"=>"drofmusic",
		"title"=>"Doctor of Music Badge",
		"description"=>"\"That's DOCTOR PROFESSOR PATRICK to you!\" 10,000 songs strong!"
	),
	"opinionated"=>array(
		"image"=>"opinionated",
		"title"=>"Everybody's a Critic Badge",
		"description"=>"I KNOW WHAT I LIKE AND I'VE FAVORITED 20 SONGS.",
		"earned_string"=>"You've got such strong feelings, favoriting 20 songs and everything. Way to hold to your principles."
	),
	"creeper"=>array(
		"image"=>"creeper",
		"title"=>"Kilroy Was Here Badge",
		"description"=>"Creep like Kilroy and follow 15 people.",
		"earned_string"=>"Kilroy was the king of creep. But you've followed 15 people. That's pretty damn creepy."
	),
	"choochoo"=>array(
		"image"=>"choochoo",
		"title"=>"Choo Choo Badge",
		"description"=>"Add three Train songs to a tape.",
		"earned_string"=>"You're on a train. You're on a train. Everybody look at you cause you're ridin' on a train!"
	),
	"house"=>array(
		"image"=>"house",
		"title"=>"House Badge",
		"description"=>"Add three songs from House to a tape.",
		"earned_string"=>'"I need some help with Alice Tanner. She wants a vagina."<br />"I\'m pretty attached to mine."'
	),
	"gaga"=>array(
		"image"=>"gaga",
		"title"=>"GaGa Badge",
		"description"=>"Add five GaGa songs to a tape.",
		"earned_string"=>"Baby, you'll be famous. Chase you down until you love me."
	),
	"bastacarrie"=>array(
		"image"=>"bastacarrie",
		"title"=>"Basta and Carrie Badge",
		"description"=>"You've just got to be super cool, I guess.",
		"earned_string"=>"Ehhhhh....Basta and Carrie, Carrie and Basta....potato potato."
	),
	"bspears"=>array(
		"image"=>"bspears",
		"title"=>"Britney Badge",
		"description"=>"Make a tape showing Britney some love.",
		"earned_string"=>"WHY CAN'T YOU JUST <b>LEAVE BRITNEY ALLOOOONNNEEEEEE?????</b>"
	),
	"douchebag"=>array(
		"image"=>"douchebag",
		"title"=>"Douchebag Badge",
		"description"=>"Make a tape that's DTF.",
		"earned_string"=>"Wipe the spray tan out of your eyes and check it out, brah. Let's go drop some Jagerbombs over this shiz."
	),
	"christmas"=>array(
		"image"=>"christmas",
		"title"=>"Holiday Badge",
		"description"=>"Create a tape full of holiday cheer.",
		"earned_string"=>"It must be time to break out the age-old carols and equally-old fruit cakes. In the spirit of giving, here's a holiday-esque badge."
	)
);
