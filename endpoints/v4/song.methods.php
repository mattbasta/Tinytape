<?php

class methods {
	public $score = 0;
	
	public function __default() {
		
		header("Location: " . URL_PREFIX);
		return false;
		
	}
	public function _new() {
		global $session;
		
		if(!$session->logged_in) {
			header("Location: " . URL_PREFIX . "login?redirect=" . URL_PREFIX . "song/new");
			return false;
		}
		
		view_manager::set_value("TITLE", "Add a new song to Tinytape");
		
		view_manager::add_view(VIEW_PREFIX . "shell");
		view_manager::add_view(VIEW_PREFIX . "song/new");
		
		return view_manager::render_as_httpresponse();
		
	}
	public function create() {
		global $session, $db, $r;
		if(!$session->logged_in) {
			header("Location: " . URL_PREFIX . "login?redirect=" . urlencode(URL_PREFIX . "song/new"));
			return false;
		}
		
		$songs = $db->get_table("songs");
		
		if(	empty($_REQUEST['track']) ||
			empty($_REQUEST['artist'])) {
			
			header("Location: " . URL_PREFIX . "song/new?error=-missing");
			
		}elseif($existing = $songs->fetch(
				array(
					"title"=>$_REQUEST['track'],
					"artist"=>$_REQUEST['artist']
				),
				FETCH_ARRAY
			)) {
			$existing = array_pop($existing);
			header("Location: " . URL_PREFIX . "song/" . $existing['id']);
			echo "Found Alternate";
			
		}elseif(!empty($_REQUEST['timestamp']) &&
				!empty($_REQUEST['id']) &&
				!empty($_REQUEST['token']) ) {
			
			$time = time() - ((int)$_REQUEST['timestamp']);
			$token = sha1($_REQUEST['id'] . $_REQUEST['timestamp'] . '9ZLPSOFIT18KT4VF');
			if($time > 360 || $time < 0 || $token !== $_REQUEST['token']) {
				header("Location: " . URL_PREFIX . "song/new?error=-token");
				return false;
			}
			
			$id = $songs->insert_row(
				0,
				array(
					'title'=>$_REQUEST['track'],
					'artist'=>$_REQUEST['artist'],
					'album'=>$_REQUEST['album']
				)
			);
			
			$r->zIncrBy("tinytape_scores", 10, $session->username);
			$r->zIncrBy("tinytape_createcount", 1, $session->username);
			$r->hSet("tinytape_songcreatedby", $id, $session->username);
			$r->hSet("tinytape_title", $id, $_REQUEST["track"]);
			$r->hSet("tinytape_artist", $id, $_REQUEST["artist"]);
			$r->hSet("tinytape_album", $id, $_REQUEST["album"]);
			
			$r->sAdd("tinytape_songs", $id);
			
			header('Location: ' . URL_PREFIX . "song/search/$id?bonus");
			
		}
		
		return false;
		
	}
	
	public function favorite($id, $instance="") {
		global $r, $session, $tt_badges;
		
		if(empty($id) || empty($_GET["uid"]))
			return false;
		
		$username = $session->username;
		
		$fid = (int)$id . (empty($instance)?"":("_".(int)$instance));
		$fkey = "tinytape_favorites_$username";
		$uid = $_GET["uid"];
		
		header("Content-type: text/javascript");
		
		if($r->sContains($fkey, $fid)) {
			$r->sRemove($fkey, $fid);
			$r->sRemove("tinytape_favorited_" . $id, $username);
			$r->zIncrBy("tinytape_favorited", -1, $id);
			$r->zIncrBy("tinytape_favorites", -1, $username);
			
			return new HttpResponse("un_favorite('$uid');");
		} else {
			$r->sAdd($fkey, $fid);
			$r->sAdd("tinytape_favorited_" . $id, $username);
			$r->zIncrBy("tinytape_favorited", 1, $id);
			$r->zIncrBy("tinytape_favorites", 1, $username);
			
			if(!$r->sContains("tinytape_badges_$username", "opinionated") &&
			   $r->zScore("tinytape_favorites", $username) >= 20) {
				
				bless_badge("opinionated");
				
				$badge_json = json_encode($tt_badges["opinionated"]);
				
				return new HttpResponse("is_favorite('$uid');\nbadge($badge_json);");
				
			}
			
			return new HttpResponse("is_favorite('$uid');");
		}
		
	}
	
	public function best($id) {
		global $db, $r;
		
		if(empty($id))
			return false;
		
		$songs = $db->get_table('songs');
		$song = $songs->fetch(
			array(
				'id'=>$id
			),
			FETCH_SINGLE_TOKEN
		);
		
		if($song === false) {
			return new HttpResponse('{"error":"Song could not be found."}');
		}
		
		require(PATH_PREFIX . "/best.php");
		
		$badge = $this->mark_history($id);
		$response = findBestInstance($song);
		$response["badge"] = $badge;
		$response["points"] = $this->score;
		return new JSONResponse($response);
		
	}
	
	public function instance($id, $instance) {
		global $db;
		
		if(empty($id))
			return false;
		
		$songs = $db->get_table('songs');
		$song_instance = $db->get_table('song_instance');
		
		$res = $song_instance->fetch(
			array(
				'id'=>(int)$instance,
				'song_id'=>(int)$id
			),
			FETCH_SINGLE,
			array(
				'columns'=>'service_resource'
			)
		);
		
		if($res == false)
			return new JSONResponse(array(
				'error'=>'The song instance could not be found.'
			));
		
		$badge = $this->mark_history($id);
		return new JSONResponse(array(
			'guess'=>true,
			'service'=>'youtube',
			'resource'=>$res,
			'badge'=>$badge,
			'points'=>$this->score
		));
		
	}
	
	public function random() {
		global $r;
		
		$id = $r->sRandMember("tinytape_songs");
		
		header("Location: " . URL_PREFIX . "song/view/" . $id);
		
	}
	
	public function view($id) {
		global $db, $r, $session;
		
		if(empty($id)) {
			header("Location: " . URL_PREFIX . "search");
			return false;
		}
		
		
		$songs = $db->get_table('songs');
		$song_instance = $db->get_table('song_instance');
		
		$song = $songs->fetch(
			array(
				'id'=>$id
			),
			FETCH_SINGLE_TOKEN
		);
		
		if($song === false) {
			return $this->not_found();
		}
		
		view_manager::add_view(VIEW_PREFIX . "shell");
		
		$instances = $song_instance->fetch(
			array(
				'song_id'=>$song->id
			),
			FETCH_ARRAY,
			array(
				"columns"=>array(
					"service_resource",
					"version_name",
					"live",
					"acoustic",
					"clean",
					"remix",
					"remix_name",
					"id"
				)
			)
		);
		
		view_manager::add_view(VIEW_PREFIX . "song/song");
		view_manager::set_value('ID', $song->id);
		
		view_manager::set_value('BONUS', isset($_REQUEST['bonus']));
		
		view_manager::set_value('THUMBNAIL', URL_PREFIX . "api/albumart/redirect?title=" . urlencode($song->title) . "&artist=" . urlencode($song->artist) . "&size=medium");
		view_manager::set_value('TITLE', $song->title);
		view_manager::set_value('ARTIST', $song->artist);
		view_manager::set_value('ALBUM', $song->album);
		
		view_manager::set_value('REDIRECT', '/song/' . $song->id);
		view_manager::set_value('INSTANCES', $instances);
		
		return view_manager::render_as_httpresponse();
		
	}
	
	public function search($id) {
		global $db, $r, $session;
		
		if(empty($id)) {
			header("Location: " . URL_PREFIX . "search");
			return false;
		}
		
		if(!$session->logged_in) {
			header('Location: ' . URL_PREFIX . 'login?redirect=/song/search/' . $id);
			return false;
		}
		
		
		$songs = $db->get_table('songs');
		
		$song = $songs->fetch(
			array(
				'id'=>$id
			),
			FETCH_SINGLE_TOKEN
		);
		
		if($song === false)
			return $this->not_found();
		
		view_manager::add_view(VIEW_PREFIX . "shell");
		view_manager::set_value('ID', (int)$id);
		view_manager::set_value('TITLE', $song->title);
		view_manager::set_value('ARTIST', $song->artist);
		view_manager::set_value('ALBUM', $song->album);
		
		view_manager::set_value('BONUS', isset($_GET["bonus"]));
		
		
		require(PATH_PREFIX . "/best.php");
		$results = findAll($song->title, $song->artist, $song->album, true, $id);
		
		view_manager::set_value('RESULTS', $results);
		view_manager::add_view(VIEW_PREFIX . 'song/yt_results');
		
		return view_manager::render_as_httpresponse();
		
	}
	
	public function add_to_tape($tape, $song, $instance=0) {
		global $db, $r, $session, $tt_badges;
		
		if(!$session->logged_in) {
			header('Location: ' . URL_PREFIX . 'login?redirect=/tape' . urlencode($tape));
			return false;
		}
		
		$r_id = "{$song}_$instance";
		
		if(!$r->sContains("tinytape_tapes", $tape))
			return new HttpResponse("No such tape");
		if(!$r->sContains("tinytape_songs", $song))
			return new HttpResponse("No such song");
		if($r->hGet("tinytape_tapeowner", $tape) != $session->username) {
			return new HttpResponse("Access denied: " . $r->hGet("tinytape_tapeowner", $tape));
		}
		
		view_manager::set_value("URL_ID", $song . "/" . $instance);
		view_manager::set_value("TAPE", $tape);
		
		if($r->sContains("tinytape_tapecontents_$tape", $r_id)) {
			view_manager::add_view(VIEW_PREFIX . "tapes/alreadyexists");
			return view_manager::render_as_httpresponse();
		}
		
		$song_references = $db->get_table("song_reference");
		$song_references->insert_row(
			0,
			array(
				"song_id"=>$song,
				"tape_name"=>$tape,
				"index"=>0,
				"song_instance"=>$instance
			)
		);
		$r->sAdd("tinytape_tapecontents_$tape", $r_id);
		$r->rPush("tinytape_tape_$tape", $r_id);
		$r->zIncrBy("tinytape_addedtotape", 1, $song);
		$r->zIncrBy("tinytape_addedsongstotape", 1, $session->username);
		$r->zIncrBy("tinytape_scores", 1, $session->username);
		
		$unclaimed_badges = $r->sDiff("tinytape_badgesets", "tinytape_badges_" . $session->username);
		
		$songs = $r->sMembers("tinytape_tapecontents_$tape");
		$num_songs = count($songs);
		foreach($unclaimed_badges as $badge) {
			// We're going to assume that every song already in the tape has been through this same process
			if(!$r->sContains("tinytape_badgeset_$badge", $song))
				continue;
			
			$required_songs = (int)$r->hGet("tinytape_badgesets_required", $badge);
			// Should we even look further?
			if($required_songs <= $num_songs) {
				$badge_songs = $r->sMembers("tinytape_badgesets_$badge");
				$acquired_songs = 0;
				foreach($songs as $song) {
					if(($spos = strpos($song, "_")) !== false)
						$song = substr($song, 0, $spos);
					if($r->sContains("tinytape_badgeset_$badge", $song)) {
						$acquired_songs++;
						if($acquired_songs >= $required_songs)
							break;
					}
				}
				if($acquired_songs >= $required_songs) {
					
					bless_badge($badge);
					
					view_manager::add_view(VIEW_PREFIX . "tapes/got_badge");
					view_manager::set_value("BADGE", $tt_badges[$badge]);
					return view_manager::render_as_httpresponse();
				}
				
			}
			
		}
		
		view_manager::add_view(VIEW_PREFIX . "tapes/addedtotape");
		return view_manager::render_as_httpresponse();
		
	}
	
	public function addinstance($id) {
		global $db, $r, $session;
		
		if(empty($id) ||
		   empty($_REQUEST["sc_service_resource"]) ||
		   empty($_REQUEST["sc_service"]) ||
		   $_REQUEST["sc_service"] != "youtube")
			return false;
		
		if(!(isset($_POST['sc_service']) &&
			 isset($_POST['sc_service_resource']) &&
			 strlen($_POST['sc_service_resource']) < 15 && // Really jank sanitation.
			 isset($_POST['version_name']) &&
			 isset($_POST['event_name']) &&
			 isset($_POST['remix_name']) &&
			 isset($_POST['remix_artist'])
			 ))
			return false;
		
		if(!$session->logged_in) {
			header('Location: ' . URL_PREFIX . 'login?redirect=/song/search/' . $id);
			return false;
		}
		
		$songs = $db->get_table('songs');
		$song = $songs->fetch(
			array( 'id'=>$id ),
			FETCH_SINGLE_TOKEN
		);
		
		if($song === false)
			return $this->not_found();
		
		$resource = $_POST['sc_service_resource'];
		$service = "youtube";
		
		$resource_id = "$service:$resource";
		if($r->sContains("tinytape_instances_$id", $resource_id)) {
			return new JSONResponse(array(
				"instance"=>$r->hGet("tinytape_resourceref_$id", $resource_id),
				"badge"=>false,
				"points"=>false
			));
		}
		
		$r->sAdd("tinytape_instances_$id", $resource_id);
		
		//////
		$p_live = isset($_POST['live']);
		$p_event_name = $p_live ? $_POST['event_name']:'';
		$p_clean = isset($_POST['clean']);
		$p_acoustic = isset($_POST['acoustic']);
		$p_remix = isset($_POST['remix']);
		$p_remix_name = $p_remix ? $_POST['remix_name']:'';
		$p_remix_artist = $p_remix ? $_POST['remix_artist']:'';
		//////
		
		$song_instance = $db->get_table('song_instance');
		$instance = $song_instance->insert_row(
			0,
			array(
				'song_id'=>$id,
				'service'=>$service,
				'service_resource'=>$resource,
				'version_name'=>$_POST['version_name'],
				'live'=>$p_live,
				'live_event'=>$p_event_name,
				'clean'=>$p_clean,
				'acoustic'=>$p_acoustic,
				'remix'=>$p_remix,
				'remix_name'=>$p_remix_name,
				'remix_artist'=>$p_remix_artist
			)
		);
		$r->hSet("tinytape_resourceref_$id", $resource_id, $instance);
		
		$score = 1;
		$r->zIncrBy("tinytape_bestmatch_$id", $score, $instance);
		
		$points = $r->zIncrBy("tinytape_scores", 3, $session->username);
		
		return new JSONResponse(array(
			"instance"=>$instance,
			"badge"=>false,
			"points"=>$points
		));
		
	}
	
	private function not_found() {
		global $session, $r;
		view_manager::add_view(VIEW_PREFIX . "shell");
		view_manager::add_view(VIEW_PREFIX . "not_found");
		
		if($session->logged_in && !$r->sContains("tinytape_badges_" . $session->username, "cluso")) {
			bless_badge("cluso");
			view_manager::set_value("BADGE", true);
		}
		
		return view_manager::render_as_httpresponse();
	}
	
	private function mark_history($id) {
		global $r, $session, $tt_badges;
		
		if($session->logged_in) {
			
			$username = $session->username;
			
			if(THROTTLE_DUPLICATE_ENABLE) {
				# No double dipping throttling
				$last_song_played = $r->lGet("tinytape_history_$username", 0);
				if($last_song_played == $id)
					return false;
			}
			
			$r->lPush("tinytape_history_$username", $id);
			$r->lPush("tinytape_history", $id);
			
			if(THROTTLE_PERHOUR_ENABLE) {
				# 50 songs in an hour throttling
				$rsongc_key = "tinytape_{$username}_" . floor(time() / 3600);
				$recent_song_count = $r->incr($rsongc_key);
				if($recent_song_count >= THROTTLE_PERHOUR)
					return false;
				elseif($recent_song_count == 1)
					$r->expire($rsongc_key, 3600); // Make the key go away in an hour or so
			}
			
			$session->song_count++;
			
			//$r->hIncrBy("tinytape_scores", $username, 1);
			$this->score = $r->zIncrBy("tinytape_scores", 1, $username);
			
			$r->zIncrBy("tinytape_playcounts_$username", 1, $id);
			$r->zIncrBy("tinytape_playcounts", 1, $username);
			
			$r->zIncrBy("tinytape_songplays", 1, $id);
			$r->zIncrBy("tinytape_songplays_" . $id, 1, $username);
			
			$badge_set = "tinytape_badges_$username";
			
			if(!$r->sContains($badge_set, "zombie") &&
			   $r->zRangeByScore("tinytape_playcounts_$username", 100, "+inf", array("limit"=>array(0,1)))) {
				
				bless_badge("zombie");
				return $tt_badges["zombie"];
			}
			
			$history_count = $r->sSize("tinytape_history_$username");
			
			if(!$r->sContains($badge_set, "enthusiast") &&
			   $history_count == 1000) {
			   
				bless_badge("enthusiast");
				return $tt_badges["enthusiast"];
				
			}elseif(!$r->sContains($badge_set, "aficionado") &&
			        $history_count == 2500) {
					
				bless_badge("aficionado");
				return $tt_badges["aficionado"];
				
			}elseif(!$r->sContains($badge_set, "drofmusic") &&
			        $history_count == 10000) {
				bless_badge("drofmusic");
				return $tt_badges["drofmusic"];
			}
			
			// This will be a feed post.
			if($history_count % 15 == 0 && $session->song_count != false) {
				
				$song_count = max(5, min($session->song_count, 15));
				$songs_raw = $r->lGetRange("tinytape_history_$username", 0, $song_count);
				$songs = array();
				
				foreach($songs_raw as $raw) {
					
					$songs[] = array(
						"id"=>$raw,
						"title"=>$r->hGet("tinytape_title", $raw),
						"artist"=>$r->hGet("tinytape_artist", $raw)
					);
					
				}
				
				$post = array(
					"timestamp"=>$_SERVER["REQUEST_TIME"],
					"username"=>$username,
					"version"=>1,
					"type"=>"songs",
					"payload"=>array(
						"songs"=>$songs,
						"song_count"=>$song_count
					)
				);
				
				push_to_follower_feeds($post, $username);
				push_to_feed("tinytape_feed_$username", $post);
				//push_to_feed("tinytape_fullfeed_$username", $post);
				
				$session->song_count = false;
				
			}
			
		}
		
		return false;
		
	}
	
}
