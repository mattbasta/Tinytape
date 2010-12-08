<?php

class methods {
	
	public function edit($song_id, $field) {
		global $session, $db, $r, $user_score;
		
		if(!$session->logged_in
		   || empty($song_id)
		   || !$r->sContains("tinytape_songs", $song_id)
		   || $user_score < EDIT_SONG_MIN_POINTS
		   || empty($_REQUEST['value'])) {
			return false;
		}
		
		$songs = $db->get_table("songs");
		$song = $songs->fetch(
			array("id"=>(int)$song_id),
			FETCH_SINGLE_TOKEN
		);
		
		$value = $_REQUEST["value"];
		$success = true;
		
		switch($field) {
			case "title":
			case "artist":
			case "album":
				$old_value = $song->getValue($field);
				if($value == $old_value) {
					$success = false;
					break;
				}
				
				if($old_updater = $r->hGet("tinytape_updated_$field", $song_id)) {
					$r->zIncrBy("tinytape_karma", -1, $old_updater);
					$r->zIncrBy("tinytape_karmafrom_" . $session->username, -1, $old_updater);
				}
				
				$song->setValues(array($field=>$value));
				$r->hSet("tinytape_$field", $song_id, $value);
				$r->hSet("tinytape_updated_$field", $song_id, $value);
				break;
			default:
				$success = false;
		}
		
		if($success)
			return new HttpResponse($value);
		else
			return new HttpResponse($song->getValue($field));
		
	}
	
}
