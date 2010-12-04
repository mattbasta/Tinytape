<?php

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
				"limit"=>10
			)
		);
		
		view_manager::set_value("TITLE", "My Account");
		view_manager::set_value("THEME", "b");
		view_manager::set_value("TAPES", $tapes);
		
		view_manager::add_view(VIEW_PREFIX . "shell");
		view_manager::add_view(VIEW_PREFIX . "account");
		
		view_manager::set_value("SCORE", (int)$r->zScore("tinytape_scores", $username));
		view_manager::set_value("BADGES", $r->sMembers("tinytape_badges_$username"));
		view_manager::set_value("NEWS_FEED", $r->lGetRange("tinytape_fullfeed_$username", 0, 30));
		view_manager::set_value("FOLLOWING_USERS", $r->sMembers("tinytape_followers_" . $username));
		view_manager::set_value("FOLLOWEE_USERS", $r->sMembers("tinytape_following_" . $username));
		view_manager::set_value("TTBADGES", $tt_badges);
		view_manager::set_value("USERNAME", $username);
		
		return view_manager::render_as_httpresponse();
		
	}
	
	public function history() {
		global $r, $session;
		
		if(!$session->logged_in) {
			header("Location: " . URL_PREFIX . "login?redirect=" . URL_PREFIX . "account");
			return false;
		}
		$username = $session->username;
		
		view_manager::set_value("TITLE", "History");
		view_manager::set_value("THEME", "b");
		view_manager::set_value("SONGS", $this->get_songs($r->lGetRange("tinytape_history_" . $username, 0, 30)));
		
		view_manager::add_view(VIEW_PREFIX . "shell");
		view_manager::add_view(VIEW_PREFIX . "songs");
		
		return view_manager::render_as_httpresponse();
		
	}
	
	public function favorites() {
		global $r, $session;
		
		if(!$session->logged_in) {
			header("Location: " . URL_PREFIX . "login?redirect=" . URL_PREFIX . "account");
			return false;
		}
		$username = $session->username;
		
		view_manager::set_value("TITLE", "Favorites");
		view_manager::set_value("THEME", "b");
		view_manager::set_value("SONGS", $this->get_songs($r->sMembers("tinytape_favorites_" . $username)));
		
		view_manager::add_view(VIEW_PREFIX . "shell");
		view_manager::add_view(VIEW_PREFIX . "songs");
		
		return view_manager::render_as_httpresponse();
		
	}
	
	private function get_songs($items) {
		global $r, $session, $db;
		
		$output = array();
		
		$song_table = $db->get_table("songs");
		foreach($items as $item) {
			$instance = 0;
			if(($uscorepos = strpos($item, "_")) !== false) {
				$instance = (int)substr($item, $uscorepos + 1);
				$item = (int)substr($item, 0, $uscorepos);
			}
			$song = $song_table->fetch(
				array( "id"=>$item ),
				FETCH_SINGLE_ARRAY
			);
			
			$output[] = array(
				"service"=>"tinytape",
				"nofav"=>$type == "favorites",
				"resource"=>array(
					"id"=>(int)$item,
					"instance"=>(int)$instance,
				),
				"metadata"=>array(
					"title"=>htmlentities($song["title"]),
					"artist"=>htmlentities($song["artist"]),
					"album"=>htmlentities($song["album"])
				)
			);
		}
		
		return $output;
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
		$post = $_POST["post"];
		preg_match_all("!(^|\W)@([a-z0-9_]+)!", $post, $mentions);
		$post = array(
			"timestamp"=>(int)$_SERVER["REQUEST_TIME"],
			"username"=>$username,
			"type"=>"shout",
			"payload"=>array(
				"text"=>$post, 
				"mentions"=>$mentions[2]
			),
			"version"=>1
		);
		
		
		foreach($mentions[2] as $mention) {
			$mention = strtolower($mention);
			if(user_exists($mention)) {
				// TODO : Add controls to disable users from pushing to fullfeed
				push_to_feed("tinytape_fullfeed_$mention", $post);
				push_to_feed("tinytape_mentions_$mention", $post);
			}
		}
		
		push_to_follower_feeds($post);
		
		push_to_feed("tinytape_fullfeed_$username", $post);
		push_to_feed("tinytape_feed_$username", $post);
		
		$r->zIncrBy("tinytape_scores", 1, $username);
		
		header("Location: " . URL_PREFIX . "account");
		return false;
		
	}
	
}
