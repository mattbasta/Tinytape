<?php

class methods {
	
	public function __default($shorturl="") {
		
		$append = "";
		if(!empty($shorturl) && strlen($shorturl) > 1) {
			
			$id = $this->decode_id(substr($shorturl, 1));
			
			switch($shorturl[0]) {
				case 's':
					$append = "song/view/" . $id;
					break;
			}
			
		}
		
		header("Location: http://tinytape.com/" . $append);
		return false;
	}
	
	public function a($artist) { // Artist
		
		header("Location: http://tinytape.com/song/artist/" . $artist);
		return false;
		
	}
	
	public function get_song($id) {
		$id = (int)$id;
		$out = "";
		
		while($id > 0) {
			$char = $id % 62;
			$id -= $char;
			$id /= 62;
			
			$char += 55;
			if($char < 65) $char -= 7;
			elseif($char > 90) $char += 6;
			$out = chr($char) . $out;
			
		}
		
		return new HttpResponse($out);
		
	}
	
	private function decode_id($id_raw) {
		
		$sl = strlen($id_raw);
		$id =0;
		for($i=0;$i<$sl;$i++) {
			
			$v = ord($id_raw[$sl - $i - 1]);
			if($v >= 97)
				$v -= 6;
			elseif($v <= 57)
				$v += 7;
			
			$v -= 55;
			if($v < 0)
				return 0;
			elseif($v > 62)
				return 0;
			
			$id += $v * pow(62, $i);
			
		}
		
		return $id;
		
	}
	
}
