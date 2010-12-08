<?php

class methods {
	public function __default() {
		global $session, $r;
		
		if(!$session->admin)
			return false;
		
		view_manager::set_value("title", "Admin");
		
		view_manager::add_view(VIEW_PREFIX . "shell");
		view_manager::add_view(VIEW_PREFIX . "admin/tapes");
		
		return view_manager::render_as_httpresponse();
		
	}
	public function delete_long() {
		global $session, $r;
		
		if(!$session->admin)
			return false;
		
		$t = $r->sMembers("tinytape_tapes");
		foreach($t as $tape) {
			if(strlen($tape) > 64) {
				$r->sRemove("tinytape_tapes", $tape);
				$r->hDel("tinytape_tapeowner", $tape);
				$r->delete("tinytape_tape_" . $tape);
				$r->delete("tinytape_tapecontents_" . $tape);
			}
		}
		header("Location: " . URL_PREFIX . "admin/tape");
		
		return false;
		
	}
	public function delete_tape() {
		global $session, $r;
		
		if(!$session->admin)
			return false;
		
		$r->sRemove("tinytape_tapes", $_POST["tape"]);
		$r->hDel("tinytape_tapeowner", $_POST["tape"]);
		$r->delete("tinytape_tape_" . $_POST["tape"]);
		$r->delete("tinytape_tapecontents_" . $_POST["tape"]);
		header("Location: " . URL_PREFIX . "admin/tape");
		
		return false;
		
	}
	public function view($tape="") {
		global $session, $r, $tt_badges;
		
		if(!$session->admin)
			return false;
		
		if(!empty($_REQUEST['tape']))
			$tape = $_REQUEST['tape'];
		$badges = $r->sMembers("tinytape_badges_$username");
		$shuffled = $r->sMembers("tinytape_shuffled_$username");
		
		view_manager::add_view(VIEW_PREFIX . "shell");
		view_manager::add_view(VIEW_PREFIX . "admin/tape_view");
		
		view_manager::set_value("TAPE", $tape);
		
		return view_manager::render_as_httpresponse();
		
	}
}
