<?php

class methods {
	public function __default() {
		global $session, $r;
		
		if(!$session->admin)
			return false;
		
		view_manager::set_value("title", "Admin");
		
		view_manager::add_view(VIEW_PREFIX . "shell");
		view_manager::add_view(VIEW_PREFIX . "admin/user");
		
		return view_manager::render_as_httpresponse();
		
	}
	public function add_badge($username) {
		global $session, $r;
		
		if(!$session->admin)
			return false;
		
		bless_badge($_REQUEST["badge"], $username);
		header("Location: " . URL_PREFIX . "admin/user/view/$username");
		
		return false;
		
	}
	public function remove_badge($username, $badge) {
		global $session, $r;
		
		if(!$session->admin)
			return false;
		
		$r->sRemove("tinytape_badges_$username", $badge);
		header("Location: " . URL_PREFIX . "admin/user/view/$username");
		
		return false;
		
	}
	public function delete_history($username) {
		global $session, $r;
		
		if(!$session->admin)
			return false;
		
		$r->delete("tinytape_history_$username");
		header("Location: " . URL_PREFIX . "admin/user/view/$username");
		
		return false;
		
	}
	public function delete_feed($username) {
		global $session, $r;
		
		if(!$session->admin)
			return false;
		
		$r->delete("tinytape_fullfeed_$username");
		$r->delete("tinytape_feed_$username");
		$r->delete("tinytape_mentions_$username");
		$r->delete("tinytape_followeefeed_$username");
		$r->delete("tinytape_searchhistory_$username");
		$r->delete("tinytape_history_$username");
		header("Location: " . URL_PREFIX . "admin/user/view/$username");
		
		return false;
		
	}
	public function delete_favorites($username) {
		global $session, $r;
		
		if(!$session->admin)
			return false;
		
		$r->delete("tinytape_favorites_$username");
		header("Location: " . URL_PREFIX . "admin/user/view/$username");
		
		return false;
		
	}
	public function view($username="") {
		global $session, $r, $tt_badges;
		
		if(!$session->admin)
			return false;
		
		if(!empty($_REQUEST['username']))
			$username = $_REQUEST['username'];
		else
			$username = $username;
		$badges = $r->sMembers("tinytape_badges_$username");
		$shuffled = $r->sMembers("tinytape_shuffled_$username");
		
		view_manager::add_view(VIEW_PREFIX . "shell");
		view_manager::add_view(VIEW_PREFIX . "admin/user_view");
		
		view_manager::set_value("USERNAME", $username);
		view_manager::set_value("BADGES", $badges);
		view_manager::set_value("SHUFFLED", $shuffled);
		view_manager::set_value("TTBADGES", $tt_badges);
		
		return view_manager::render_as_httpresponse();
		
	}
}
