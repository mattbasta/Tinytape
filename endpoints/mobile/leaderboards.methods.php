<?php

view_manager::set_value("TITLE", "Leaderboards");
view_manager::set_value("THEME", "b");

class methods {
	
	public function __default() {
		
		view_manager::add_view(VIEW_PREFIX . "shell");
		view_manager::add_view(VIEW_PREFIX . "leaderboards/default");
		
		return view_manager::render_as_httpresponse();
		
	}
	
	public function top_users() {
		global $r;
		
		view_manager::set_value("TITLE", "Top Users");
		view_manager::set_value("USERS", $r->zReverseRange("tinytape_scores", 0, 9, true));
		
		view_manager::add_view(VIEW_PREFIX . "shell");
		view_manager::add_view(VIEW_PREFIX . "leaderboards/top_users");
		
		return view_manager::render_as_httpresponse();
		
	}
	
	public function most_opinionated() {
		global $r;
		
		view_manager::set_value("TITLE", "Most Opinionated Users");
		view_manager::set_value("USERS", $r->zReverseRange("tinytape_favorites", 0, 9, true));
		
		view_manager::add_view(VIEW_PREFIX . "shell");
		view_manager::add_view(VIEW_PREFIX . "leaderboards/most_opinionated");
		
		return view_manager::render_as_httpresponse();
		
	}
	
	public function most_songs() {
		global $r;
		
		view_manager::set_value("TITLE", "Most Songs Added");
		view_manager::set_value("USERS", $r->zReverseRange("tinytape_createcount", 0, 9, true));
		
		view_manager::add_view(VIEW_PREFIX . "shell");
		view_manager::add_view(VIEW_PREFIX . "leaderboards/most_songs");
		
		return view_manager::render_as_httpresponse();
		
	}
	
	public function top_songs() {
		global $r;
		
		view_manager::set_value("TITLE", "Top Songs");
		$raw_songs = $r->zReverseRange("tinytape_songplays", 0, 19, true);
		$songs = array();
		foreach($raw_songs as $song=>$score) {
			$songs[$song] = array(
				"title"=>$r->hget("tinytape_title", $song),
				"artist"=>$r->hget("tinytape_artist", $song),
				"score"=>$score
			);
		}
		view_manager::set_value("SONGS", $songs);
		
		view_manager::add_view(VIEW_PREFIX . "shell");
		view_manager::add_view(VIEW_PREFIX . "leaderboards/top_songs");
		
		return view_manager::render_as_httpresponse();
		
	}
	
	public function most_favorited() {
		global $r;
		
		view_manager::set_value("TITLE", "Most Favorited Songs");
		$raw_songs = $r->zReverseRange("tinytape_favorited", 0, 9, true);
		$songs = array();
		foreach($raw_songs as $song=>$score) {
			$songs[$song] = array(
				"title"=>$r->hget("tinytape_title", $song),
				"artist"=>$r->hget("tinytape_artist", $song),
				"score"=>$score
			);
		}
		view_manager::set_value("SONGS", $songs);
		
		view_manager::add_view(VIEW_PREFIX . "shell");
		view_manager::add_view(VIEW_PREFIX . "leaderboards/most_favorited");
		
		return view_manager::render_as_httpresponse();
		
	}
	
}
