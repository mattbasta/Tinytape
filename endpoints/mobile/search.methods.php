<?php

class methods {
	public function __default() {
		
		view_manager::set_value("TITLE", "Search");
		view_manager::set_value("THEME", "b");
		
		view_manager::add_view(VIEW_PREFIX . "shell");
		view_manager::add_view(VIEW_PREFIX . "search");
		
		return view_manager::render_as_httpresponse();
		
	}
	
	public function _do() {
		global $session, $r, $db;
		
		$term = strtolower(trim($_GET["q"]));
		
		if(empty($term)) {
			header("Location: " . URL_PREFIX . "search");
			return false;
		}
		
		$escaped_query = $db->escape($term);
		$query = new cloud_unescaped(
			"MATCH (title,artist,album) AGAINST ('$escaped_query')"
		);
		
		$songs = $db->get_table('songs');
		$result = $songs->fetch(
			array($query),
			FETCH_TOKENS,
			array(
				'limit'=>25
			)
		);
		
		view_manager::add_view(VIEW_PREFIX . "shell");
		view_manager::add_view(VIEW_PREFIX . "search/results");
		
		
		if($session->logged_in)
			$r->lPush("tinytape_searchhistory_" . $session->username, $term);
		$r->lPush("tinytape_mobile_searchhistory", $term);
		$r->lPush("tinytape_searchhistory", $term);
		$r->zIncrBy("tinytape_mobile_searchtally", 1, $term);
		$r->zIncrBy("tinytape_searchtally", 1, $term);
		
		view_manager::set_value("TITLE", $term . " - Tinytape");
		view_manager::set_value("THEME", "b");
		view_manager::set_value("SHOWBACK", true);
		view_manager::set_value('RESULTS', $result);
		
		return view_manager::render_as_httpresponse();
		
	}
	
}
