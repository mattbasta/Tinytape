<?php

class methods {
	
	private function get_tapes($username) {
		global $db;
		$tape_table = $db->get_table("tapes");
		return $tape_table->fetch(
			array(
				"user"=>$username
			),
			FETCH_ARRAY
		);
	}
	
	public function load($username) {
		global $session;
		
		if(!$session->logged_in || empty($username)) {
			return false;
		}
		
		$tapes = $this->get_tapes($username);
		
		view_manager::set_value("TAPES", $tapes);
		view_manager::set_value("API", true);
		view_manager::add_view(VIEW_PREFIX . "snippets/tapelist");
		
		return view_manager::render_as_httpresponse();
		
	}
	
	public function add_to_tape($song_id, $instance=0) {
		global $session, $db;
		
		if(!$session->logged_in || empty($song_id)) {
			return false;
		}
		
		$tapes = $this->get_tapes($session->username);
		
		view_manager::set_value("TAPES", $tapes);
		view_manager::set_value("SONG", $song_id);
		view_manager::set_value("INSTANCE", $instance);
		view_manager::set_value("API", true);
		view_manager::add_view(VIEW_PREFIX . "snippets/addtotape");
		
		return view_manager::render_as_httpresponse();
		
	}
	
}
