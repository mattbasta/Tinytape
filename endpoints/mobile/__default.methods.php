<?php

class methods {
	public function __default() {
		
		view_manager::set_value("TITLE", "Tinytape");
		
		view_manager::add_view(VIEW_PREFIX . "shell");
		view_manager::add_view(VIEW_PREFIX . "home");
		
		return view_manager::render_as_httpresponse();
		
	}
	
	public function badge($badge) {
		global $tt_badges;
		
		if(!isset($tt_badges[$badge]))
			return false;
		
		view_manager::set_value("TITLE", "Badge");
		view_manager::set_value("BADGE", $badge);
		$ttb = $tt_badges[$badge];
		view_manager::set_value("TITLE", $ttb["title"]);
		view_manager::set_value("IMAGE", $ttb["image"]);
		view_manager::set_value("DESCRIPTION", $ttb["description"]);
		
		view_manager::add_view(VIEW_PREFIX . "shell");
		view_manager::add_view(VIEW_PREFIX . "badge");
		
		return view_manager::render_as_httpresponse();
		
	}
	
}
