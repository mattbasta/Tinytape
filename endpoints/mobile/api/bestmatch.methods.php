<?php

require(PATH_PREFIX . '/best.php');

class methods {
	public function __default() {
		
		if(!isset($_REQUEST['title']))
			die('Missing title');
		if(!isset($_REQUEST['artist']))
			die('Missing artist');
		
		$title = $_REQUEST['title'];
		$artist = $_REQUEST['artist'];
		
		return findBest($title, $artist);
		
	}
}
