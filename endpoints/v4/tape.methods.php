<?php

class methods {
	
	public function __default($name) {
		global $db, $keyval, $r, $session;
		
		if(empty($name)) {
			header("Location: " . URL_PREFIX . "tapes");
			return false;
		}
		
		$tapes = $db->get_table('tapes');
		
		if(!$r->sContains("tinytape_tapes", $name))
			return $this->not_found();
		
		$tape = $tapes->fetch(
			array(
				'name'=>$name
			),
			FETCH_SINGLE_TOKEN
		);
		
		view_manager::add_view(VIEW_PREFIX . 'tape');
		view_manager::set_value('TITLE', $tape->title);
		view_manager::set_value('ID', $tape->name);
		view_manager::set_value('OWNER', $tape->user);
		view_manager::set_value('COLOR', $tape->color);
		
		$results = $r->lGetRange("tinytape_tape_$name", 0, -1);
		
		$instances = array();
		if($results !== false) {
			foreach($results as $result) {
				
				$raw_bits = explode("_", $result);
				$song_id = $raw_bits[0];
				
				$instances[] = array(
					'id'=>$song_id,
					"title"=>$r->hGet("tinytape_title", $song_id),
					"artist"=>$r->hGet("tinytape_artist", $song_id),
					"album"=>$r->hGet("tinytape_album", $song_id),
					'instance'=>isset($raw_bits[1])?$raw_bits[1]:0
				);
				
			}
		}
		
		$shuffle = isset($_REQUEST['shuffle']);
		view_manager::set_value('SHUFFLE', $shuffle);
		
		// Shuffle badge ftw
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
			
			view_manager::set_value('SHUFFLE', true);
		}
		
		view_manager::set_value('INSTANCES', $instances);
		view_manager::set_value('CAN_DELETE_SONGS', $session->admin);
		
		return view_manager::render_as_httpresponse();
		
	}
	
	public function delete_song($name, $song_id="", $instance_id=0) {
		global $session, $r, $db;
		
		if(empty($name) || !$r->sContains("tinytape_tapes", $name)) 
			return false;
		
		$tapes = $db->get_table('tapes');
		
		$tape = $tapes->fetch(
			array(
				'name'=>$name
			),
			FETCH_SINGLE_TOKEN
		);
		
		if($tape->user != $session->user && !$session->admin)
			return false;
		//echo "tapecontents_$name", "{$song_id}_{$instance_id}";
		//return false;
		$r->lRemove("tinytape_tape_$name", "{$song_id}_{$instance_id}", 1);
		
		$references = $db->get_table("song_reference");
		$ref = $references->fetch(array(
			"song_id" => $song_id,
			"tape_name" => $name,
			"song_instance" => $instance_id
		), FETCH_SINGLE_TOKEN);
		if($ref)
			$ref->destroy();
		
		if(!empty($_GET["uid"])) {
			$uid = $_GET["uid"];
			$uid = strip_tags($uid);
			$uid = addslashes($uid);
			echo "$('#$uid').fadeOut();";
		}
		
		return false;
		
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
