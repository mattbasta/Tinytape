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
			header("Location: /images/noart.jpg?nomatch");
		} else {
			header("Location: " . $result);
		}
		
		return false;
	}
	
	public function proxy() {
		
		$result = $this->determine();
		if($result == false)
			$result = PATH_PREFIX . "/../static/images/noart.jpg";
		
		echo file_get_contents($result);
		
		return false;
	}
	
	private function determine() {
		global $keyval;
		
		if(!isset($_REQUEST['artist']))
			die('Missing artist');
		
		$artist = urlencode($_REQUEST['artist']);
		$size_spec = !empty($_REQUEST['size'])?$_REQUEST['size']:"large";
		
		$url = "http://ws.audioscrobbler.com/2.0/?format=json&method=artist.getImages&api_key=" . LASTFM_APIKEY . "&artist=$artist";
		
		if(!($json_data = $keyval->get("tt_aacache.$url.$size_spec")))
			$keyval->set("tt_aacache.$url.$size_spec", $json_data = file_get_contents($url));
		
		$json = json_decode($json_data, true);
		
		if($json["error"] ||
		   empty($json["images"]) ||
		   empty($json["images"]["image"]))
			return false;
		else {
			
			$match = $json["images"]["image"][0];
			if(empty($match["sizes"]["size"]))
				return "/images/noart.jpg";
			foreach($match["sizes"]["size"] as $size)
				if($size["name"] == $size_spec)
					return $size["#text"];
			
		}
		
		return false;
		
	}
	
}
