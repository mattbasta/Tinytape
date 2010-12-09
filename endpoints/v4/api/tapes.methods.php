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
		global $session;
		
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
	
	public function edit($tape, $field) {
		global $session, $db, $r;
		
		if(!$session->logged_in
		   || empty($tape)
		   || !$r->sContains("tinytape_tapes", $tape)
		   || $r->hGet("tinytape_tapeowner", $tape) != $session->username
		   || empty($_REQUEST['value'])) {
			return false;
		}
		
		$tapes = $db->get_table("tapes");
		$tape = $tapes->fetch(
			array("name"=>$tape),
			FETCH_SINGLE_TOKEN
		);
		
		$value = $_REQUEST["value"];
		$success = true;
		
		switch($field) {
			case "title":
				if(strlen($value) < 3) {
					$success = false;
					break;
				}
				$tape->title = htmlentities($value);
				break;
			case "color":
				preg_match(COLOR_REGEX, $value, $color_matches);
				if(strlen($value) != 6
				   || count($color_matches) != 1) {
					$success = false;
					break;
				}
				$tape->color = $value;
				break;
			// We don't allow URL modifications. Too many edge cases (and it would jack the feeds)
			default:
				$success = false;
		}
		
		if($success)
			return new HttpResponse($value);
		else
			return new HttpResponse($tape->getValue($field));
		
	}
	
	public function reorder($tape) {
		global $session, $db, $r;
		
		if(!$session->logged_in
		   || empty($tape)
		   || !$r->sContains("tinytape_tapes", $tape)
		   || $r->hGet("tinytape_tapeowner", $tape) != $session->username
		   || empty($_REQUEST['order'])) {
			return new JSONResponse(array("error"=>"Invalid request"));
		}
		
		// Double array flip to eliminate all duplicates
		$order = json_decode($_REQUEST["order"], true);
		$dup = array_flip(array_flip($order));
		if(count($dup) < count($order))
			return new JSONResponse(array("error"=>"Duplicate entries"));
		
		// Convert the keys to ints and test their presence in the tape
		$inty = array();
		foreach($order as $k=>$v) {
			if(!$r->sContains("tinytape_tapecontents_$tape", $v))
				return new JSONResponse(array("error"=>"Unknown entities"));
			$inty[(int)$k] = $v;
		}
		
		ksort($inty);
		
		$tempid = uniqid();
		foreach($inty as $k=>$song) {
			$r->rPush("tinytape_tapereorder_$tempid", $song);
		}
		
		//var_dump($r->lGetRange("tinytape_tapereorder_$tempid", 0, -1));
		//var_dump($r->lGetRange("tinytape_tape_$tape", 0, -1));
		
		$r->delete("tinytape_tape_$tape");
		$r->renameKey("tinytape_tapereorder_$tempid", "tinytape_tape_$tape");
		
		//var_dump($r->lGetRange("tinytape_tape_$tape", 0, -1));
		
		return new JSONResponse(array("success"=>true));
		
	}
	
}
