<?php

class methods {
	
	public function get_feed($username, $type, $offset=0) {
		global $session, $r, $tt_badges, $keyval, $db;
		
		if(!user_exists($username))
			return false;
		
		if(!$session->logged_in)
			return false;
		
		switch($type) {
			case "fullfeed":
			case "searchhistory":
				if(!$session->logged_in || ($session->username != $username && !$session->admin))
					return false;
		}
		
		view_manager::set_value("FEED_USERNAME", $username);
		view_manager::set_value("FEED_TYPE", $type);
		
		$json_data = false;
		$payload = "";
		ob_start();
		
		function clearhistory($type, $username) {
			global $session;
			
			// This isn't so much a "safety" thing so much as it is a "there's not support for it" thing.
			if($session->username != $username && $session->admin)
				return;
			?>
			<div id="feed_clearhistory">
				<a href="#" onclick="clear_history('<?php echo $type; ?>');return false;">Clear History</a>
			</div>
			<?php
		}
		
		switch($type) {
			case "fullfeed":
			case "feed":
			case "mentions":
			case "followeefeed":
				$items = $r->lGetRange("tinytape_{$type}_$username", 0, 25);
				if($items) {
					foreach($items as $feed_item)
						require(PATH_PREFIX . "/feed_item.php");
				} else {
					?>
					<div id="feed_empty">
						There are no items to display.
					</div>
					<?php
				}
				break;
				
			case "searchhistory":
				clearhistory($type, $username);
				
				$items = $r->lGetRange("tinytape_searchhistory_$username", 0, 50);
				
				foreach($items as $item) {
					$item_hash = md5($item);
				?>
				<div class="feed_item searchhistory">
					<a class="fi_delete" href="#" onclick="return delete_post('<?php echo addslashes(htmlentities($username)); ?>', 'searchhistory', '<?php echo $item_hash; ?>');">Delete</a>
					<a href="<?php echo URL_PREFIX; ?>search?q=<?php echo urlencode($item); ?>"><?php echo htmlentities($item); ?></a>
				</div>
				<?php
				}
				break;
				
			case "history":
				if($username == $session->username)
					clearhistory($type, $username);
				// Continue right into favorites
			case "favorites":
				
				if($type == "history")
					$items = $r->lGetRange("tinytape_{$type}_$username", 0, 30);
				else
					$items = $r->sMembers("tinytape_{$type}_$username");
				
				if(!$items) {
					?>
					<div id="feed_empty">
						There are no items to display.
					</div>
					<?php
					break;
				}
				
				$song_table = $db->get_table("songs");
				// TODO : Pull instance data in
				// $song_instance_table = $db->get_table("song_instances");
				
				$json_data = array();
				
				echo '<ul class="songlist">';
				foreach($items as $item) {
					$instance = 0;
					if(($uscorepos = strpos($item, "_")) !== false) {
						$instance = (int)substr($item, $uscorepos + 1);
						$item = (int)substr($item, 0, $uscorepos);
					}
					$song = $song_table->fetch(
						array( "id"=>$item ),
						FETCH_SINGLE_ARRAY
					);
					
					
					$uid = md5(uniqid());
					
					$rtitle = $song["title"];
					$rartist = $song["artist"];
					$ralbum = $song["album"];
					
					$rid = $song["id"];
					
					require(PATH_PREFIX . "/result.php");
					
					$json_data[$uid] = array(
						"service"=>"tinytape",
						"nofav"=>$type == "favorites",
						"resource"=>array(
							"id"=>(int)$item,
							"instance"=>(int)$instance,
						),
						"metadata"=>array(
							"title"=>htmlentities($rtitle),
							"artist"=>htmlentities($rartist),
							"album"=>htmlentities($ralbum)
						)
					);
				}
				
				echo '</ul>';
				
		}
		
		$payload = ob_get_clean();
		
		// Do some basic minification
		$payload = str_replace("\t", "", $payload);
		$payload = str_replace("\n", "", $payload);
		
		return new JSONResponse(array(
			"payload"=>$payload,
			"register"=>$json_data
		));
	}
	
	public function delete_item($username, $type, $hash) {
		global $session, $r, $db;
		
		if(!user_exists($username) || !$session->logged_in || strlen($hash) != 32)
			return false;
		
		if($session->username != $username && !$session->admin)
			return false;
		
		$key = "tinytape_{$type}_$username";
		
		switch($type) {
			case "fullfeed":
			case "feed":
			case "mentions":
			case "followeefeed":
			case "searchhistory":
			case "history":
				
				for($i=0;$i<1000;$i++) { // This might be increasable
					$item = $r->lGet($key, $i);
					if($item === false)
						break;
					if(md5($item) != $hash)
						continue;
					$rem = $r->lRemove($key, $item, 1);
					return new JSONResponse(array(
						"deleted"=>true,
						"badge"=>false
					));
					
				}
				
				break;
				
		}
		
		return new JSONResponse(array(
			"badge"=>false
		));
	}
	
	public function _empty($history) {
		global $r, $session;
		
		if(!$session->logged_in)
			return false;
		
		$histories = array("history"=>1, "searchhistory"=> 1);
		if(!isset($histories[$history]))
			return false;
		
		$r->delete("tinytape_{$history}_" . $session->username);
		
	}
	
}
