<?php

define("FB_REDIR_URI", urlencode("http://tinytape.com/account/fbconnect"));

class methods {
	public function __default() {
		global $db, $session, $r, $tt_badges, $keyval;
		
		if(!$session->logged_in) {
			header("Location: " . URL_PREFIX . "login?redirect=" . URL_PREFIX . "account");
			return false;
		}
		
		$username = $session->username;
		
		$tape_table = $db->get_table("tapes");
		$tapes = $tape_table->fetch(
			array(
				"user"=>$username
			),
			FETCH_ARRAY,
			array(
				"limit"=>10,
				'order'=>new listOrder('id','DESC')
			)
		);
		
		// Note: this code is duplicated in account.methods.php
		if(!($following_users = $keyval->get("tinytape_followers_stats_" . $session->username))) {
			$following_users_raw = $r->sMembers("tinytape_followers_" . $session->username);
			$following_users = array();
			foreach($following_users_raw as $fur) {
				$score = (int)$r->zScore("tinytape_scores", $fur);
				$following_users[$fur] = array(
					"score"=>$score,
					"level"=>getLevel($score)
				);
			}
			$keyval->set("tinytape_followers_stats_$username", json_encode($following_users), 3600);
		} else 
			$following_users = json_decode($following_users, true);
		
		if(ENABLE_FACEBOOK) {
			// Is Facebook connected for the user?
			if($uid = $r->hGet("tinytape_facebook", $username)) {
				
				view_manager::set_value("FACEBOOK", true);
				view_manager::set_value("FB_UID", $uid);
				view_manager::set_value("FB_NAME", $r->hGet("tinytape_facebook_name", $username));
				
			} else {
				
				view_manager::set_value("FB_CONNECT_AUTH", FB_REDIR_URI);
				
			}
		}
		
		view_manager::set_value("TITLE", "Oh, it's you again. I thought you were someone else.");
		view_manager::set_value("TAPES", $tapes);
		
		view_manager::add_view(VIEW_PREFIX . "shell");
		view_manager::add_view(VIEW_PREFIX . "account");
		
		view_manager::set_value("BADGES", $r->sMembers("tinytape_badges_$username"));
		view_manager::set_value("NEWS_FEED", $r->lGetRange("tinytape_fullfeed_$username", 0, 25));
		view_manager::set_value("FOLLOWING_USERS", $following_users);
		view_manager::set_value("TTBADGES", $tt_badges);
		view_manager::set_value("USERNAME", $username);
		view_manager::set_value("MORE_TAPES", count($tapes) == 10);
		
		return view_manager::render_as_httpresponse();
		
	}
	
	public function post() {
		global $session, $r;
		
		if(!$session->logged_in) {
			header("Location: " . URL_PREFIX . "login?redirect=" . URL_PREFIX . "account");
			return false;
		}
		
		if(empty($_POST["post"]) || $_POST["post"] == "What's up, pudding cup?") {
			header("Location: " . URL_PREFIX . "account");
			return false;
		}
		
		$username = $session->username;
		$post_text = $_POST["post"];
		preg_match_all("!(^|\W)@([a-z0-9_]+)!", $post_text, $mentions);
		$post = array(
			"timestamp"=>(int)$_SERVER["REQUEST_TIME"],
			"username"=>$username,
			"type"=>"shout",
			"payload"=>array(
				"text"=>$post_text, 
				"mentions"=>$mentions[2]
			),
			"version"=>1
		);
		
		$mentioned = array();
		foreach($mentions[2] as $mention) {
			$mention = strtolower($mention);
			if(user_exists($mention)) {
				$mentioned[] = $mention;
				// TODO : Add controls to disable users from pushing to fullfeed
				push_to_feed("tinytape_fullfeed_$mention", $post);
				push_to_feed("tinytape_mentions_$mention", $post);
			}
		}
		
		push_to_follower_feeds($post, "", $mentioned);
		
		push_to_feed("tinytape_fullfeed_$username", $post);
		push_to_feed("tinytape_feed_$username", $post);
		
		$r->zIncrBy("tinytape_scores", 1, $username);
		
		if(ENABLE_FACEBOOK && !empty($_REQUEST["dofb"]) && $uid = $r->hGet("tinytape_facebook", $username)) {
			
			$token = $r->hGet("tinytape_facebook_token", $username);
			
			$fields_raw = array(
				// I could have sworn FB allowed HTML in stream posts :(
				//"message"=>shout_process($post_text, "http://tinytape.com" . URL_PREFIX),
				"message"=>$post_text,
				"actions"=>json_encode(array(
					"name"=>"Look up $username on Tinytape",
					"link"=>"http://tinytape.com" . URL_PREFIX . "user/$username"
				))
			);
			$fields = "";
			foreach($fields_raw as $key=>$value) { $fields .= $key.'='.urlencode($value).'&'; }
			
			$ch = curl_init("https://graph.facebook.com/$uid/feed");
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $fields.$token);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER  ,1);  // RETURN THE CONTENTS OF THE CALL
			curl_exec($ch);
			
			$r->zIncrBy("tinytape_fbposts", 1, $username);
			$r->incr("tinytape_fbposts_total", 1);
			
			
		}
		
		header("Location: " . URL_PREFIX . "account");
		return false;
		
	}
	
	public function fbconnect() {
		global $r, $session;
		
		if(!ENABLE_FACEBOOK)
			return false;
		
		if(!$session->logged_in) {
			header("Location: " . URL_PREFIX . "login?redirect=" . URL_PREFIX . "account");
			return false;
		}
		
		if(empty($_GET["code"])) {
			header("Location: " . URL_PREFIX . "account");
			return false;
		}
		
		$token = file_get_contents("https://graph.facebook.com/oauth/access_token?client_id=" . FB_ID . "&redirect_uri=" . FB_REDIR_URI . "&client_secret=" . FB_SECRET . "&code=" . urlencode($_GET["code"]));
		
		$mej = file_get_contents("https://graph.facebook.com/me?" . $token);
		$me = json_decode($mej, true);
		
		$username = $session->username;
		$r->hSet("tinytape_facebook", $username, $me["id"]);
		$r->hSet("tinytape_facebook_token", $username, $token);
		$r->hSet("tinytape_facebook_name", $username, $me["first_name"] . " " . $me["last_name"]);
		$r->hSet("tinytape_facebook_metadata", $username, $mej);
		
		header("Location: " . URL_PREFIX . "account");
		return false;
		
	}
	
	public function fbdeauth() {
		global $r, $session;
		
		if(!$session->logged_in) {
			header("Location: " . URL_PREFIX . "login?redirect=" . URL_PREFIX . "account");
			return false;
		}
		
		$username = $session->username;
		$r->hDel("tinytape_facebook", $username);
		$r->hDel("tinytape_facebook_token", $username);
		$r->hDel("tinytape_facebook_name", $username);
		$r->hDel("tinytape_facebook_metadata", $username);
		
		header("Location: " . URL_PREFIX . "account");
		return false;
		
	}
	
	public function testfb() {
		global $r, $session;
		
		if(!$session->logged_in || !$session->admin) {
			header("Location: " . URL_PREFIX . "login?redirect=" . URL_PREFIX . "account");
			return false;
		}
		
		post_blessing_fb("christmas", $session->username);
		
		echo "Tested!";
		
		return false;
		
	}
	
}
