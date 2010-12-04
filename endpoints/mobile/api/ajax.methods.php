<?php

class methods {
	public function toggle_follow($username) {
		global $session, $r, $tt_badges;
		
		if(!$session->logged_in || !user_exists($username)) {
			return false;
		}
		
		if($r->sContains("tinytape_following_$username", $session->username)) {
			
			$r->sRemove("tinytape_following_$username", $session->username);
			$r->sRemove("tinytape_followers_" . $session->username, $username);
			
			return new JSONResponse(array(
				"new_text"=>"Follow " . htmlentities($username)
			));
			
		} else {
			
			$r->sAdd("tinytape_following_$username", $session->username);
			$r->sAdd("tinytape_followers_" . $session->username, $username);
			
			if(true || !$r->sContains("tinytape_oncefollowed_" . $session->username, $username)) {
				
				$post = array(
					"timestamp"=>(int)$_SERVER["REQUEST_TIME"],
					"username"=>$session->username,
					"type"=>"following",
					"payload"=>array(
						"target"=>array($username)
					),
					"version"=>1
				);
				
				push_to_follower_feeds($post);
				
				push_to_feed("tinytape_fullfeed_" . $session->username, $post);
				push_to_feed("tinytape_feed_" . $session->username, $post);
				
				if(!$r->sContains("tinytape_followers_$username", $session->username))
					push_to_feed("tinytape_fullfeed_" . $username, $post);
				
			}
			
			$r->sAdd("tinytape_oncefollowed_" . $session->username, $username);
			
			$badge = false;
			if(!$r->sContains("tinytape_badges_$username", "creeper") &&
			   $r->sSize("tinytape_followers_$username") > 20) {
				
				$r->sAdd("tinytape_badges_$username", "creeper");
				$bage = $tt_badges["creeper"];
				
			}
			
			return new JSONResponse(array(
				"new_text"=>"Unfollow " . htmlentities($username),
				"badge"=>$badge
			));
			
		}
		
	}
}
