<?php

class methods {
	
	public function load($username) {
		global $session, $db;
		
		if(!$session->logged_in || empty($username)) {
			return false;
		}
		
		$tape_table = $db->get_table("tapes");
		$tapes = $tape_table->fetch(
			array(
				"user"=>$username
			),
			FETCH_ARRAY
		);
		
		view_manager::set_value("TAPES", $tapes);
		view_manager::set_value("API", true);
		view_manager::add_view(VIEW_PREFIX . "snippets/tapelist");
		
		return view_manager::render_as_httpresponse();
		
	}
	
}
