<?php

class methods {
	public function __default() {
		global $session, $r;
		
		if(!$session->admin)
			return false;
		
		view_manager::set_value("TITLE", "Admin");
		view_manager::set_value("STATS", $r->hLen("tinytape_title"));
		
		view_manager::add_view(VIEW_PREFIX . "shell");
		view_manager::add_view(VIEW_PREFIX . "admin/admin");
		
		return view_manager::render_as_httpresponse();
		
	}
	public function emails() {
		global $session, $db, $r;
		
		if(!$session->admin)
			return false;
		
		$users = $db->get_table("users");
		$uarr = $users->fetch(
			TRUE,
			FETCH_ARRAY
		);
		
		foreach($uarr as $user) {
			echo $user["username"], "\t", $user["email"], "\n";
		}
		
		return false;
		
	}
	public function seed() {
		global $session, $db, $r;
		
		if(!$session->admin)
			return false;
		
		$songs = $db->get_table("songs");
		$song_instances = $db->get_table("song_instance");
		$sarr = $songs->fetch(
			TRUE,
			FETCH_ARRAY
		);
		
		$tapes = $db->get_table("tapes");
		$song_refs = $db->get_table("song_reference");
		$tarr = $tapes->fetch(
			TRUE,
			FETCH_ARRAY
		);
		
		foreach($tarr as $tape) {
			//$r->delete("tinytape_tape_" . $tape["name"]);
			$r->hSet("tinytape_tapeowner", $tape["name"], $tape["user"]);
			$r->sAdd("tinytape_tapes", $tape["name"]);
			var_dump($tape);
			echo "<br />";
			
			$refs = $song_refs->fetch(
				array("tape_name"=>$tape["name"]),
				FETCH_TOKENS
			);
			if($refs !== false) {
				foreach($refs as $ref) {
					$r_id = "{$ref->song_id}_{$ref->song_instance}";
					$r->sAdd("tinytape_tapecontents_" . $tape["name"], $r_id);
					$r->sAdd("tinytape_addedtotape_" . $ref->song_id, $tape["name"]);
					//$r->rPush("tinytape_tape_" . $tape["name"], $r_id);
				}
			}
		}
		
		foreach($sarr as $song) {
			$id = $song["id"];
			$r->sAdd("tinytape_songs", $id);
			$r->hSet("tinytape_artist", $id, $song["artist"]);
			$r->hSet("tinytape_title", $id, $song["title"]);
			$r->hSet("tinytape_album", $id, $song["album"]);
			var_dump($song);
			echo "<br />";
			
		}
		
		$users = $db->get_table("users");
		$uarr = $users->fetch(
			TRUE,
			FETCH_ARRAY
		);
		
		foreach($uarr as $user) {
			$r->sAdd("tinytape_users", $user["username"]);
			var_dump($user);
			echo "<br />";
		}
		
		$instances = $song_instances->fetch(
			TRUE,
			FETCH_ARRAY
		);
		
		foreach($instances as $instance) {
			$id = $instance["song_id"];
			$score = 1;
			$score += (int)$instance["live"] * 3;
			$score += (int)$instance["remix"] * 2;
			$score += (int)$instance["acoustic"] * 2;
			$score += (int)$instance["clean"];
			$score += (strlen($instance["version_name"]) > 0) ? 1 : 0;
			
			$r->zDelete("tinytape_bestmatch_$id", $instance_id);
			$r->zAdd("tinytape_bestmatch_$id", $score, $instance_id);
			
			$resource_id = $instance["service"] . ":" . $instance["service_resource"];
			
			$r->sAdd("tinytape_instances_$id", $resource_id);
			$r->hSet("tinytape_resourceref_$id", $resource_id, $instance["id"]);
			
		}
		
		
		return false;
		
	}
	
}
