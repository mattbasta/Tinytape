<?php

require(PATH_PREFIX . '/best.php');

class methods {
	public function __default() {
		
		$result = $this->determine();
		if($result == false) {
			return new HttpResponse('{"error":true}');
		} else {
			return new JSONResponse(array(
				"error"=>false,
				"image"=>$result
			));
		}
		
	}
	
	public function redirect() {
		
		$result = $this->determine();
		if($result == false) {
			header("Location: /images/noart.jpg");
		} else {
			header("Location: " . $result);
		}
		
		return false;
	}
	
	private function determine() {
		global $keyval;
		
		if(!isset($_REQUEST['artist']))
			die('Missing artist');
		if(empty($_REQUEST['size']))
			die('Missing size');
		
		$artist = urlencode($_REQUEST['artist']);
		$size = $_REQUEST['size'];
		
		
		if(isset($_REQUEST['title'])) {
			
			$title = urlencode($_REQUEST['title']);
			
			$url = "http://ws.audioscrobbler.com/2.0/?format=json&method=track.search&api_key=" . LASTFM_APIKEY . "&track=$title&artist=$artist&limit=1";
			
			if(!($json_data = $keyval->get("tt_aacache.$url.$size")))
				$keyval->set("tt_aacache.$url", $json_data = file_get_contents($url));
			
			$json = json_decode($json_data, true);
			
			if($json["error"] ||
			   empty($json["results"]) ||
			   empty($json["results"]["trackmatches"]))
				return false;
			else {
				
				$match = $json["results"]["trackmatches"]["track"];
				if(empty($match["image"]) || strlen(trim($match["image"])) == 0)
					return "/images/noart.jpg";
				foreach($match["image"] as $image)
					if($_REQUEST["size"] == $image["size"])
						return $image["#text"];
				
			}
		} elseif(isset($_REQUEST['album'])) {
			
			$album = urlencode($_REQUEST['album']);
			
			$url = "http://ws.audioscrobbler.com/2.0/?format=json&method=album.getInfo&api_key=" . LASTFM_APIKEY . "&album=$album&artist=$artist&limit=1";
			
			if(!($json_data = $keyval->get("tt_aacache.$url.$size")))
				$keyval->set("tt_aacache.$url", $json_data = file_get_contents($url));
			
			$json = json_decode($json_data, true);
			
			if($json["error"] ||
			   empty($json["album"]) ||
			   empty($json["album"]["image"]))
				return false;
			else
				foreach($json["album"]["image"] as $image)
					if($_REQUEST["size"] == $image["size"])
						return $image["#text"];
		} else {
			die('Missing title/album');
		}
		
		return false;
		
	}
	
}
