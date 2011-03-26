<?php

class methods {
	public function __default() {
		global $r;
		
		view_manager::set_value("TITLE", "Tinytape: Mixtapes for all");
		
		view_manager::add_view(VIEW_PREFIX . "shell");
		view_manager::add_view(VIEW_PREFIX . "home");
		
		$history = $r->lGetRange("tinytape_history", 0, 64);
		$recent_songs = array();
		foreach($history as $hist) {
			$recent_songs[] = array(
				"service"=>"tinytape",
				"resource"=>array(
					"id"=>$hist,
					"instance"=>0
				),
				"metadata"=>array(
					"title"=>$r->hGet("tinytape_title", $hist),
					"artist"=>$r->hGet("tinytape_artist", $hist),
					"album"=>$r->hGet("tinytape_album", $hist)
				)
			);
		}
		view_manager::set_value("SONGS", $recent_songs);
		
		return view_manager::render_as_httpresponse();
		
	}
	
	public function privacy() {
		view_manager::add_view(VIEW_PREFIX . "shell");
		view_manager::add_view(VIEW_PREFIX . "privacy");
		return view_manager::render_as_httpresponse();
	}
	
	public function search() {
		global $session, $r, $db, $sphinx;
		
		$term = strtolower(trim($_GET["q"]));
		
		if(empty($term)) {
			header("Location: " . URL_PREFIX);
			return false;
		}
		
		
		view_manager::add_view(VIEW_PREFIX . "shell");
		view_manager::add_view(VIEW_PREFIX . "search");
		view_manager::set_value("QUERY", $term);
		view_manager::set_value('REDIRECT', URL_PREFIX . 'search/?q=' . urlencode($term));
		
		if(SEARCH_PROVIDER == "sphinx") {
			
			$query = $sphinx->query($term);
			$result = array();
			
			if(isset($query["matches"])) {
				foreach($query["matches"] as $match=>$value) {
					$result[] = array(
						"id"=>$match,
						"title"=>$r->hGet("tinytape_title", $match),
						"artist"=>$r->hGet("tinytape_artist", $match),
						"album"=>$r->hGet("tinytape_album", $match)
					);
				}
			} else $result = false;
			
		} else {
			$escaped_query = $db->escape($term);
			$query = new cloud_unescaped(
				"MATCH (title,artist,album) AGAINST ('$escaped_query')"
			);
			
			$songs = $db->get_table('songs');
			$result = $songs->fetch(
				array($query),
				FETCH_ARRAY,
				array(
					'limit'=>25
				)
			);
		}
		
		view_manager::set_value('RESULTS', $result);
		
		if($session->logged_in) {
			$r->lPush("tinytape_searchhistory_" . $session->username, $term);
			$r->zIncrBy(REDIS_PREFIX . NOW_YEAR . "_searches_" . $session->username, 1, $term);
			$r->zIncrBy(REDIS_PREFIX . NOW_MONTH . "_searches_" . $session->username, 1, $term);
		}
		
		
		$r->lPush("tinytape_searchhistory", $term);
		$r->zIncrBy("tinytape_searchtally", 1, $term);
		$r->zIncrBy(REDIS_PREFIX . NOW_YEAR . "_searches", 1, $term);
		$r->zIncrBy(REDIS_PREFIX . NOW_MONTH . "_searches", 1, $term);
		$r->zIncrBy(REDIS_PREFIX . NOW_WEEK . "_searches", 1, $term);
		
		view_manager::set_value("TITLE", $term . " - Tinytape");
		
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
