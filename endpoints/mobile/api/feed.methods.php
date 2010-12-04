<?php

class methods {
	public function get_feed($username, $type) {
		global $session, $r, $tt_badges;
		
		if(!user_exists($username))
			return false;
		
		switch($type) {
			case "fullfeed":
				if(!$session->logged_in)
					return false;
		}
		
		
		
		
	}
}
