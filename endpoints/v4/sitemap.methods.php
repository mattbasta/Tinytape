<?php

header("Content-Type: text/xml");

class methods {
	
	public function __default() {
		view_manager::add_view(VIEW_PREFIX . "sitemaps/listing");
		return view_manager::render_as_httpresponse();
	}
	
	public function tapes() {
		global $r, $keyval;
		
		if(!($output = $keyval->get("tinytape/sitemaps/tapes"))) {
			$pages_raw = $r->sMembers("tinytape_tapes");
			
			$pages = array();
			foreach($pages_raw as $page) {
				$pages[] = "tape/$page";
			}
			
			view_manager::set_value("PAGES", $pages);
			view_manager::set_value("UPDATES", "weekly");
			
			view_manager::add_view(VIEW_PREFIX . "sitemaps/shell");
			
			$output = view_manager::render();
			$keyval->set("tinytape/sitemaps/tapes", $output, 3600 * 6);
		}
		
		return new HttpResponse($output);
		
	}
	
	public function users() {
		global $r, $keyval;
		
		if(!($output = $keyval->get("tinytape/sitemaps/users"))) {
			$pages_raw = $r->zReverseRange("tinytape_scores", 0, -1);
			
			$pages = array();
			foreach($pages_raw as $page) {
				$pages[] = "user/$page";
			}
			
			view_manager::set_value("PAGES", $pages);
			view_manager::set_value("UPDATES", "daily");
			
			view_manager::add_view(VIEW_PREFIX . "sitemaps/shell");
			
			$output = view_manager::render();
			$keyval->set("tinytape/sitemaps/users", $output, 3600 * 6);
		}
		
		return new HttpResponse($output);
		
	}
	
	public function songs() {
		global $r, $keyval;
		
		if(!($output = $keyval->get("tinytape/sitemaps/songs"))) {
			$pages_raw = $r->zReverseRange("tinytape_songplays", 0, -1);
			
			$pages = array();
			foreach($pages_raw as $page) {
				$pages[] = "user/$page";
			}
			
			view_manager::set_value("PAGES", $pages);
			view_manager::set_value("UPDATES", "monthly");
			
			view_manager::add_view(VIEW_PREFIX . "sitemaps/shell");
			
			$output = view_manager::render();
			$keyval->set("tinytape/sitemaps/songs", $output, 3600 * 6);
		}
		
		return new HttpResponse($output);
		
	}
	
}
