<?php

class methods {
	public function __default() {
		global $session, $r;
		
		if(!$session->admin)
			return false;
		
		view_manager::set_value("title", "Admin");
		
		view_manager::add_view(VIEW_PREFIX . "shell");
		view_manager::add_view(VIEW_PREFIX . "admin/songs");
		
		return view_manager::render_as_httpresponse();
		
	}
	public function merge() {
		global $session, $r, $db;
		
		if(!$session->admin)
			return false;
		
		if(empty($_REQUEST["source"]) ||
		   empty($_REQUEST["target"]))
			return false;
		
		$source = $_REQUEST["source"];
		$target = $_REQUEST["target"];
		
		if(!$r->sContains("tinytape_songs", $source) ||
		   !$r->sContains("tinytape_songs", $target))
			return false;
		
		$r->zIncrBy("tinytape_addedtotape", $r->zScore("tinytape_addedtotape", $source), $target);
		$r->zIncrBy("tinytape_songplays", $r->zScore("tinytape_songplays", $source), $target);
		
		foreach($r->sMembers("tinytape_instances_$source") as $inst)
			$r->sAdd("tinytape_instances_$target", $inst);
		
		foreach($r->hKeys("tinytape_resourceref_$source") as $res)
			$r->hSet("tinytape_resourceref_$target", $res, $r->hGet("tinytape_resourceref_$source", $res));
		
		foreach($r->zRange("tinytape_songplays_$source", 0, -1) as $player)
			$r->zIncrBy("tinytape_playcounts_$player", $r->zScore("tinytape_playcounts_$player", $source), $target);
		
		$song_instances = $db->get_table("song_instance");
		$song_references = $db->get_table("song_reference");
		
		$song_instances->update(
			array("song_id"=>$source),
			array("song_id"=>$target)
		);
		$song_references->update(
			array("song_id"=>$source),
			array("song_id"=>$target)
		);
		
		$this->delete($source, false);
		
		header("Location: " . URL_PREFIX . "song/view/" . $target);
		return false;
		
	}
	public function delete($source, $delete_instances=false) {
		global $session, $r, $db;
		
		if(!$session->admin)
			return false;
		
		if((empty($source) || $source = "ref") && !empty($_REQUEST["source"]))
			$source = $_REQUEST["source"];
		
		if($delete_instances && !$r->sContains("tinytape_songs", $source))
			return false;
		
		$r->sRemove("tinytape_songs", $source);
		
		$r->zRemove("tinytape_addedtotape", $source);
		$r->zRemove("tinytape_songplays", $source);
		$r->hDel("tinytape_album", $source);
		$r->hDel("tinytape_artist", $source);
		$r->hDel("tinytape_title", $source);
		
		$r->zIncrBy("tinytape_createcount", -1, $r->hGet("tinytape_songcreatedby", $source));
		$r->hDel("tinytape_songcreatedby", $source);
		
		$r->zRemove("tinytape_favorited", $source);
		foreach($r->sMembers("tinytape_favorited_$source") as $favr) {
			if($r->sRemove("tinytape_favorites_$favr", $source))
				$r->zIncrBy("tinytape_favorites", -1, $favr);
		}
		$r->delete("tinytape_favorited_$source");
		$r->delete("tinytape_instances_$source");
		$r->delete("tinytape_resourceref_$source");
		
		foreach($r->zRange("tinytape_songplays_$source", 0, -1) as $player)
			$r->zRemove("tinytape_playcounts_$player", $source);
		
		$songs = $db->get_table("songs");
		$songs->delete(array("id"=>$source));
		if($delete_instances) {
			$song_instances = $db->get_table("song_instance");
			$song_references = $db->get_table("song_reference");
			$song_instances->delete(array("song_id"=>$source));
			$song_references->delete(array("song_id"=>$source));
			header("Location: " . URL_PREFIX . "admin/songs");
		}
		return false;
		
	}
}
