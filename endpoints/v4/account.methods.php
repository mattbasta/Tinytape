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
		if(ENABLE_TWITTER) {
			// Is Twitter connected for the user?
			if($sn = $r->hGet("tinytape_twitter", $username)) {
				
				view_manager::set_value("TWITTER", true);
				view_manager::set_value("TW_SN", $sn);
				view_manager::set_value("TW_NAME", $r->hGet("tinytape_twitter_name", $username));
				view_manager::set_value("TW_AVATAR", $r->hGet("tinytape_twitter_avatar", $username));
				
			}
		}
		
		view_manager::set_value("TITLE", "Oh, it's you again. I thought you were someone else.");
		view_manager::set_value("TAPES", $tapes);
		
		view_manager::add_view(VIEW_PREFIX . "shell");
		view_manager::add_view(VIEW_PREFIX . "account");
		
		view_manager::set_value("BADGES", $r->sMembers("tinytape_badges_$username"));
		//view_manager::set_value("NEWS_FEED", $r->lGetRange("tinytape_fullfeed_$username", 0, 25));
		view_manager::set_value("FOLLOWING_USERS", $following_users);
		view_manager::set_value("TTBADGES", $tt_badges);
		view_manager::set_value("USERNAME", $username);
		view_manager::set_value("FEED_USERNAME", $username);
		view_manager::set_value("FEED_TYPE", "fullfeed");
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
		preg_match_all(MENTION_REGEX, $post_text, $mentions);
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
				if($r->sContains("tinytape_followers_" . $session->username, $mention))
					push_to_feed("tinytape_fullfeed_$mention", $post);
				push_to_feed("tinytape_mentions_$mention", $post);
			}
		}
		
		push_to_follower_feeds($post, "", $mentioned);
		
		push_to_feed("tinytape_fullfeed_$username", $post);
		push_to_feed("tinytape_feed_$username", $post);
		
		$r->zIncrBy("tinytape_scores", 1, $username);
		
		if(ENABLE_FACEBOOK && !empty($_REQUEST["dofb"]) && $uid = $r->hGet("tinytape_facebook", $username)) {
			@$this->writetofb($post_text, $uid, $username);
			foreach($mentioned as $mention) {
				if($r->sContains("tinytape_following_" . $session->username, $mention)
				   && $men_uid = $r->hGet("tinytape_facebook", $mention)) {
					@$this->writetofb($post_text, $men_uid, $mention, $uid);
				}
			}
		}
		
		if(ENABLE_TWITTER && !empty($_REQUEST["dotw"]) && $r->hGet("tinytape_twitter", $username))
			@$this->writetotw($post_text, $username);
		
		header("Location: " . URL_PREFIX . "account");
		return false;
		
	}
	
	private function writetofb($post_text, $uid, $username, $from="") {
		global $r;
		
		if(!$from)
			$from = $uid;
		
		$token = $r->hGet("tinytape_facebook_token", $username);
		
		$fields_raw = array(
			// I could have sworn FB allowed HTML in stream posts :(
			//"message"=>shout_process($post_text, "http://tinytape.com" . URL_PREFIX),
			"message"=>$post_text,
			"uid"=>$from,
			"target_id"=>$uid
		);
		$fields = "";
		foreach($fields_raw as $key=>$value) { $fields .= $key.'='.urlencode($value).'&'; }
		file_get_contents("https://api.facebook.com/method/stream.publish?" . $fields . $token);
		
		$r->zIncrBy("tinytape_fbposts", 1, $username);
		$r->incr("tinytape_fbposts_total", 1);
		
	}
	
	private function writetotw($post_text, $username) {
		global $r;
		
		$token = $r->hGet("tinytape_twitter_token", $username);
		$secret = $r->hGet("tinytape_twitter_secret", $username);
		
		$oauth = $this->getTwitterOAuth();
		$oauth->setToken($token, $secret);
		
		function replace_mentions($matches) {
			global $r;
			$user = $matches[0];
			if(user_exists($user) && $sn = $r->hGet("tinytape_twitter", $user)) {
				$user = "@$sn";
			} else {
				$user = "(t)$user";
			}
			return $user;
		}
		
		$post_text = preg_replace_callback(
			MENTION_REGEX,
			"replace_mentions",
			$post_text
		);
		$args = array('status'=>$post_text);
		$oauth->fetch('http://api.twitter.com/1/statuses/update.json', $args, OAUTH_HTTP_METHOD_POST);
		
		
		$r->zIncrBy("tinytape_twposts", 1, $username);
		$r->incr("tinytape_twposts_total", 1);
		
	}
	
	public function fbconnect() { // Facebook authorization
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
	
	private function getTwitterOAuth() {
		return new OAuth(TWITTER_CONSUMER_KEY, TWITTER_CONSUMER_SECRET, OAUTH_SIG_METHOD_HMACSHA1, OAUTH_AUTH_TYPE_URI);
	}
	
	public function twredir() { // Twitter redirect
		global $r, $session;
		
		if(!ENABLE_TWITTER)
			return false;
		
		if(!$session->logged_in) {
			header("Location: " . URL_PREFIX . "login?redirect=" . URL_PREFIX . "account");
			return false;
		}
		
		$oauth = $this->getTwitterOAuth();
		$request_token_info = $oauth->getRequestToken('https://twitter.com/oauth/request_token');
		$r->hSet("tinytape_twitter_oauth_state", $session->username, "1");
		$r->hSet("tinytape_twitter_oauth_token", $session->username, $request_token_info["oauth_token"]);
		$r->hSet("tinytape_twitter_oauth_secret", $session->username, $request_token_info["oauth_token_secret"]);
		
		header("Location: https://twitter.com/oauth/authorize?oauth_token=" . $request_token_info["oauth_token"]);
		return false;
		
	}
	
	public function twauth() { // Twitter authorization
		global $r, $session;
		
		if(!ENABLE_TWITTER)
			return false;
		
		if(!$session->logged_in ||
		   $r->hGet("tinytape_twitter_oauth_state", $session->username) != "1") {
			header("Location: " . URL_PREFIX . "login?redirect=" . URL_PREFIX . "account");
			return false;
		}
		
		$username = $session->username;
		if(empty($_GET["oauth_token"])) {
			header("Location: " . URL_PREFIX . "account");
			return false;
		}
		
		$oauth = $this->getTwitterOAuth();
		$oauth->setToken($_GET["oauth_token"], $r->hGet("tinytape_twitter_oauth_secret", $username));
		$access_token_info = $oauth->getAccessToken("https://api.twitter.com/oauth/access_token");
		
		$r->hSet("tinytape_twitter_oauth_state", $username, "2");
		$r->hSet("tinytape_twitter_token", $username, $access_token_info["oauth_token"]);
		$r->hSet("tinytape_twitter_secret", $username, $access_token_info["oauth_token_secret"]);
		
		$creds_raw = download_oauth(
			"https://twitter.com/account/verify_credentials.json",
			"twitter",
			$access_token_info["oauth_token"],
			$access_token_info["oauth_token_secret"]
		);
		$creds = json_decode($creds_raw, true);
		
		$r->hSet("tinytape_twitter", $username, $creds["screen_name"]);
		$r->hSet("tinytape_twitter_name", $username, $creds["name"]);
		$r->hSet("tinytape_twitter_metadata", $username, $creds_raw);
		$r->hSet("tinytape_twitter_avatar", $username, $creds["profile_image_url"]);
		
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
	
	public function twdeauth() {
		global $r, $session;
		
		if(!$session->logged_in) {
			header("Location: " . URL_PREFIX . "login?redirect=" . URL_PREFIX . "account");
			return false;
		}
		
		$username = $session->username;
		$r->hDel("tinytape_twitter", $username);
		$r->hDel("tinytape_twitter_oauth_token", $username);
		$r->hDel("tinytape_twitter_oauth_secret", $username);
		$r->hDel("tinytape_twitter_oauth_state", $username);
		$r->hDel("tinytape_twitter_token", $username);
		$r->hDel("tinytape_twitter_secret", $username);
		$r->hDel("tinytape_twitter_name", $username);
		$r->hDel("tinytape_twitter_metadata", $username);
		$r->hDel("tinytape_twitter_avatar", $username);
		
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
