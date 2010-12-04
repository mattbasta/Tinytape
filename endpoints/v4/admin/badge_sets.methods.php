<?php

class methods {
	public function __default() {
		global $session, $r;
		
		if(!$session->admin)
			return false;
		
		view_manager::set_value("title", "Admin");
		
		view_manager::add_view(VIEW_PREFIX . "shell");
		view_manager::add_view(VIEW_PREFIX . "admin/badge_sets");
		
		$sets = $r->sMembers("tinytape_badgesets");
		view_manager::set_value("BADGESETS", $sets);
		
		return view_manager::render_as_httpresponse();
		
	}
	public function create() {
		global $session, $r;
		
		if(!$session->admin)
			return false;
		
		if(empty($_REQUEST["slug"]) || empty($_REQUEST["reqsongs"])) {
			header("Location: " . URL_PREFIX . "admin/badge_sets");
			return false;
		}
		
		$r->sAdd("tinytape_badgesets", $_REQUEST["slug"]);
		$r->hSet("tinytape_badgesets_required", $_REQUEST["slug"], (int)$_REQUEST["reqsongs"]);
		header("Location: " . URL_PREFIX . "admin/badge_sets/view/" . urlencode($_REQUEST['slug']));
		
		return false;
		
	}
	public function delete($id) {
		global $session, $r;
		
		if(!$session->admin)
			return false;
		
		if(empty($id)) {
			header("Location: " . URL_PREFIX . "admin/badge_sets");
			return false;
		}
		
		$r->sRemove("tinytape_badgesets", $id);
		$r->hDel("tinytape_badgesets_required", $id);
		$r->delete("tinytape_badgeset_$id");
		header("Location: " . URL_PREFIX . "admin/badge_sets");
		
		return false;
		
	}
	public function add_song($id) {
		global $session, $r;
		
		if(!$session->admin)
			return false;
		
		if(empty($id) || !$r->sContains("tinytape_badgesets", $id)) {
			header("Location: " . URL_PREFIX . "admin/badge_sets");
			return false;
		} elseif(empty($_REQUEST["song_id"])) {
			header("Location: " . URL_PREFIX . "admin/badge_sets/view/$id");
			return false;
		} elseif($r->sContains("tinytape_badgeset_$id", $_REQUEST["song_id"])) {
			header("Location: " . URL_PREFIX . "admin/badge_sets/view/$id");
			return false;
		}
		
		$r->sAdd("tinytape_badgeset_$id", $_REQUEST["song_id"]);
		header("Location: " . URL_PREFIX . "admin/badge_sets/view/$id");
		
		return false;
		
	}
	public function remove_song($id, $song_id) {
		global $session, $r;
		
		if(!$session->admin)
			return false;
		
		if(empty($id) || !$r->sContains("tinytape_badgesets", $id)) {
			header("Location: " . URL_PREFIX . "admin/badge_sets");
			return false;
		} elseif(empty($song_id)) {
			header("Location: " . URL_PREFIX . "admin/badge_sets/view/$id");
			return false;
		}
		
		$r->sRemove("tinytape_badgeset_$id", $song_id);
		header("Location: " . URL_PREFIX . "admin/badge_sets/view/$id");
		
		return false;
		
	}
	public function view($id) {
		global $session, $r, $tt_badges;
		
		if(!$session->admin)
			return false;
		
		if(!$r->sContains("tinytape_badgesets", $id)) {
			header("Location: " . URL_PREFIX . "admin/badge_sets");
			return false;
		}
		
		$reqsongs = $r->hGet("tinytape_badgesets_required", $id);
		$raw_songs = $r->sMembers("tinytape_badgeset_$id");
		$songs = array();
		foreach($raw_songs as $song)
			$songs[$song] = $r->hGet("tinytape_title", $song) . " - " . $r->hGet("tinytape_artist", $song);
		
		view_manager::add_view(VIEW_PREFIX . "shell");
		view_manager::add_view(VIEW_PREFIX . "admin/badge_sets_view");
		
		view_manager::set_value("TT_BADGES", $tt_badges);
		view_manager::set_value("SET_ID", $id);
		view_manager::set_value("REQSONGS", $reqsongs);
		view_manager::set_value("SONGS", $songs);
		
		return view_manager::render_as_httpresponse();
		
	}
}
