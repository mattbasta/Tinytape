<?php

class methods {
	public function __default($username="") {
		global $db, $session, $r, $tt_badges, $keyval;
		
		if(!$session->logged_in) {
			header("Location: " . URL_PREFIX . "login?redirect=" . URL_PREFIX . "user/" . urlencode($username));
			return false;
		}
		
		if(empty($username) || !user_exists($username)) {
			return $this->not_found();
		}
		
		if($session->username == $username) {
			header("Location: " . URL_PREFIX . "account");
			return false;
		}
		
		view_manager::add_view(VIEW_PREFIX . "shell");
		
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
		
		view_manager::set_value("TITLE", $username . "'s Profile on Tinytape");
		view_manager::set_value("TAPES", $tapes);
		
		view_manager::add_view(VIEW_PREFIX . "user");
		
		
		// Note: this code is duplicated in account.methods.php
		if(!($following_users = $keyval->get("tinytape_followers_stats_$username"))) {
			$following_users_raw = $r->sMembers("tinytape_followers_$username");
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
		
		view_manager::set_value("SCORE", (int)$r->zScore("tinytape_scores", $username));
		view_manager::set_value("BADGES", $r->sMembers("tinytape_badges_" . $username));
		//view_manager::set_value("NEWS_FEED", $r->lGetRange("tinytape_feed_$username", 0, 25));
		view_manager::set_value("FOLLOWING_USERS", $following_users);
		//view_manager::set_value("MUTUALLY_FOLLOWING", $r->sInter());
		view_manager::set_value("TTBADGES", $tt_badges);
		view_manager::set_value("USERNAME", $username);
		view_manager::set_value("FEED_USERNAME", $username);
		view_manager::set_value("FEED_TYPE", "feed");
		view_manager::set_value("USE_TWITTER_@A", true);
		view_manager::set_value("TWITTER", $r->hGet("tinytape_twitter", $username));
		view_manager::set_value("FOLLOWING", $r->sContains("tinytape_following_$username", $session->username));
		view_manager::set_value("MORE_TAPES", count($tapes) == 10);
		
		return view_manager::render_as_httpresponse();
		
	}
	
	private function not_found() {
		global $session, $r;
		view_manager::add_view(VIEW_PREFIX . "shell");
		view_manager::add_view(VIEW_PREFIX . "not_found");
		
		if($session->logged_in && !$r->sContains("tinytape_badges_" . $session->username, "cluso")) {
			bless_badge("cluso");
			view_manager::set_value("BADGE", true);
		}
		
		return view_manager::render_as_httpresponse();
	}
	
}
