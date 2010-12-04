<?php

class methods {
	public function track() {
		global $keyval;
		
		$query = urlencode($_REQUEST['q']);
		$url = "http://ws.audioscrobbler.com/2.0/?format=json&method=track.search&api_key=" . LASTFM_APIKEY . "&track=$query";
		if(!empty($_REQUEST["artist"]))
			$url .= "&artist=" . urlencode($_REQUEST[$opposite]);
		
		$json = $this->cacheurl($url);
		
		if($json["error"] || empty($json["results"]))
			return false;
		
		$output = "";
		
		if(!is_array($json["results"]["trackmatches"]))
			return false;
		
		if(isset($json["results"]["trackmatches"]["track"]["name"]))
			$output .= $this->process($json["results"]["trackmatches"]["track"], "track");
		else
			foreach($json["results"]["trackmatches"]["track"] as $item)
				$output .= $this->process($item, "track");
		
		return new HttpResponse($output);
		
	}
	public function artist() {
		global $keyval;
		
		$query = urlencode($_REQUEST['q']);
		$url = "http://ws.audioscrobbler.com/2.0/?format=json&method=artist.search&api_key=" . LASTFM_APIKEY . "&artist=$query";
		if(!empty($_REQUEST["track"]))
			$url .= "&track=" . urlencode($_REQUEST[$opposite]);
		
		$json = $this->cacheurl($url);
		
		if($json["error"] || empty($json["results"]))
			return false;
		
		$output = "";
		
		if(!is_array($json["results"]["trackmatches"]))
			return false;
		
		if(isset($json["results"]["artistmatches"]["artist"]["name"]))
			$output .= $this->process($json["results"]["artistmatches"]["artist"], "artist");
		else
			foreach($json["results"]["artistmatches"]["artist"] as $item)
				$output .= $this->process($item, "artist");
		
		return new HttpResponse($output);
		
	}
	private function cacheurl($url) {
		global $keyval;
		
		if(!($json_data = $keyval->get($url)))
			$keyval->set($url, $json_data = file_get_contents($url), null, 3600 * 24);
		return json_decode($json_data, true);
		
	}
	private function process($item, $type) {
		if($type == "track")
			return json_encode(array("title"=>$item["name"], "artist"=>$item["artist"])) .  "\n";
		else
			return str_replace("\n", '', $item["name"]) . "\n";
	}
}
