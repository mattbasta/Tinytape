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
				)
			);
		}
		view_manager::set_value("SONGS", $recent_songs);
		
		return view_manager::render_as_httpresponse();
		
	}
	
	public function tape($name="") {
		global $db, $keyval, $r, $session;
		
		if(empty($name)) {
			header("Location: " . URL_PREFIX . "tapes");
			return false;
		}
		
		$tapes = $db->get_table('tapes');
		$songs = $db->get_table('songs');
		$song_refereneces = $db->get_table('song_reference');
		
		$tape = $tapes->fetch(
			array(
				'name'=>$name
			),
			FETCH_SINGLE_TOKEN
		);
		
		if($tape == false) {
			// The tape could not be found.
			
			return $this->not_found();
			
		}
		
		view_manager::add_view(VIEW_PREFIX . 'tape');
		view_manager::set_value('TITLE', $tape->title);
		view_manager::set_value('NAME', $tape->name);
		view_manager::set_value('OWNER', $tape->user);
		view_manager::set_value('COLOR', $tape->color);
		
		$results = $song_refereneces->fetch(
			array(
				'tape_name'=>$name
			),
			FETCH_TOKENS,
			array(
				'order'=>new listOrder('index','DESC')
			)
		);
		
		$instances = array();
		if($results !== false) {
			foreach($results as $result) {
				
				$song = $songs->fetch(
					array(
						'id'=>$result->song_id
					),
					FETCH_SINGLE_TOKEN
				);
				
				$instances[] = array(
					'id'=>$song->id,
					'title'=>$song->title,
					'artist'=>$song->artist,
					'album'=>$song->album,
					'instance'=>$result->song_instance
				);
				
			}
		}
		
		$shuffle = isset($_REQUEST['shuffle']);
		view_manager::set_value('SHUFFLE', $shuffle);
		
		if($shuffle) {
			shuffle($instances);
			
			if($session->logged_in &&
			   !$r->sContains("tinytape_badges_" . $session->username, "shuffler") &&
			   !$r->sContains("tinytape_shuffled_" . $session->username, $name)) {
				
				$r->sAdd("tinytape_shuffled_" . $session->username, $name);
				if($r->sSize("tinytape_shuffled_" . $session->username) == 15) {
					
					bless_badge("shuffler");
					view_manager::set_value("BADGE_SHUFFLER", true);
					view_manager::set_value("BADGE", true);
					
				}
				
			}
			
		}
		
		view_manager::set_value('INSTANCES', $instances);
		
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
		
		if($session->logged_in)
			$r->lPush("tinytape_searchhistory_" . $session->username, $term);
		
		$r->lPush("tinytape_searchhistory", $term);
		$r->zIncrBy("tinytape_searchtally", 1, $term);
		
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
