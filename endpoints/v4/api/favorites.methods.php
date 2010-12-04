<?php

class methods {
	public function determine() {
		global $r, $session;
		
		if(empty($_REQUEST["favorites"]) || !$session->logged_in)
			return false;
		
		$favorites = $_REQUEST["favorites"];
		$favorites = explode(",", $favorites);
		
		$output = array();
		$username = $session->username;
		
		foreach($favorites as $fav) {
			if($r->sContains("tinytape_favorites_" . $username, $fav)) {
				$output[] = $fav;
			}
		}
		
		return new JSONResponse($output);
		
	}
	
}
