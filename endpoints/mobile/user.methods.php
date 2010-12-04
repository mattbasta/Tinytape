<?php

class methods {
	public function __default($username) {
		global $db, $session, $r, $tt_badges;
		
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
				"limit"=>10
			)
		);
		
		view_manager::set_value("TITLE", $username . "'s Profile on Tinytape");
		view_manager::set_value("TAPES", $tapes);
		
		view_manager::add_view(VIEW_PREFIX . "user");
		
		view_manager::set_value("SCORE", (int)$r->zScore("tinytape_scores", $username));
		view_manager::set_value("BADGES", $r->sMembers("tinytape_badges_" . $username));
		view_manager::set_value("NEWS_FEED", $r->lGetRange("tinytape_feed_$username", 0, 10));
		view_manager::set_value("FOLLOWING_USERS", $r->sMembers("tinytape_followers_$username"));
		//view_manager::set_value("MUTUALLY_FOLLOWING", $r->sInter());
		view_manager::set_value("TTBADGES", $tt_badges);
		view_manager::set_value("USERNAME", $username);
		view_manager::set_value("FOLLOWING", $r->sContains("tinytape_following_$username", $session->username));
		view_manager::set_value("MORE_TAPES", count($tapes) == 10);
		
		return view_manager::render_as_httpresponse();
		
	}
}
