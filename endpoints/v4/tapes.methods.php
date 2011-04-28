<?php

class methods {
	
	public function __default() {
		
		// No global list of tapes.
		header("Location: " . URL_PREFIX . "account");
		return false;
		
	}
	
	public function _new($ajax="") {
		global $session;
		
		if(!$session->logged_in) {
			header("Location: " . URL_PREFIX . "login?redirect=" . urlencode(URL_PREFIX . "tapes/new"));
			return false;
		}
		
		view_manager::set_value("TITLE", "Create a new tape");
		view_manager::set_value("AJAX", $ajax = !empty($ajax));
		view_manager::add_view(VIEW_PREFIX . "tapes/new_bare");
			
		if($ajax)
			view_manager::add_view(VIEW_PREFIX . "tapes/new");
		else
			view_manager::add_view(VIEW_PREFIX . "tapes/new_ajax");
		
		return view_manager::render_as_httpresponse();
		
	}
	
	public function create() {
		global $db, $r, $session;
		
		if(!$session->logged_in) {
			header("Location: " . URL_PREFIX . "login?redirect=" . urlencode(URL_PREFIX . "tapes/new"));
			return false;
		}
		
		$username = $session->username;
		
		if(empty($_REQUEST["url"]) ||
		   empty($_REQUEST["title"]) ||
		   empty($_REQUEST["color"])) {
			header("Location: " . URL_PREFIX . "tapes/new?invalid=missing");
			return false;
		}
		
		$tape = $_REQUEST["url"];
		$title = $_REQUEST["title"];
		$color = substr($_REQUEST["color"], 1);
		
		$acceptable_chars = COLOR_REGEX;
		preg_match($acceptable_chars, $color, $color_matches);
		$acceptable_chars = TAPE_REGEX;
		preg_match($acceptable_chars, $tape, $url_matches);
		if(strlen($title) < 3 ||
		   strlen($color) != 6 ||
		   strlen($tape) < 2 ||
		   strlen($tape) > 64 ||
		   $tape == "delete_song" ||
		   count($color_matches) != 1 ||
		   count($url_matches) != 1) {
			header("Location: " . URL_PREFIX . "tapes/new?invalid=badvalue");
			break;
		}
		if($r->sContains("tinytape_tapes", $tape)) {
			header("Location: " . URL_PREFIX . "tapes/new?invalid=exists");
			break;
		}
		
		$tapes = $db->get_table("tapes");
		$tapes->insert_row(
			0,
			array(
				"name"=>$tape,
				"user"=>$username,
				"title"=>htmlentities($title), // Escaped here for legacy purposes
				"color"=>$color
				)
		);
		$r->sAdd("tinytape_tapes", $tape);
		$r->hSet("tinytape_tapeowner", $tape, $username);
		
		
		// Post to the news feed
		$post = array(
			"timestamp"=>$_SERVER["REQUEST_TIME"],
			"username"=>$username,
			"version"=>1,
			"type"=>"new_tape",
			"payload"=>array(
				"name"=>$tape,
				"title"=>$title,
				"color"=>$color
			)
		);
		push_to_follower_feeds($post, $username);
		push_to_feed("tinytape_feed_$username", $post);
		push_to_feed("tinytape_fullfeed_$username", $post);
		
		
		header("Location: " . URL_PREFIX . "tape/" . $tape);
		return false;
		
	}
	
	public function delete($tape="") {
		global $session, $r, $db;
		
		if(empty($tape) ||
		   !$session->logged_in ||
		   !$r->sContains("tinytape_tapes", $tape) ||
		   $r->hGet("tinytape_tapeowner", $tape) != $session->username) {
			echo "Not Deleted";
			//header("Location: " . URL_PREFIX . "account");
			return false;
		}
		
		$r->sRemove("tinytape_tapes", $tape);
		$r->delete("tinytape_tape_$tape");
		$r->delete("tinytape_tapecontents_$tape");
		$r->hDel("tinytape_tapeowner", $tape);
		$tapes = $db->get_table("tapes");
		$t = $tapes->fetch(array("name"=>$tape), FETCH_SINGLE_TOKEN);
		$t->destroy();
		
		//header("Location: " . URL_PREFIX . "account");
		return false;
		
	}
	
}
