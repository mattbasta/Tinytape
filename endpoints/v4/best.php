<?php

/*
The Best Match Algorithm
*/


function findBest($title, $artist, $album='') {
	global $keyval;
	
	$mid = "tinytape/v4/best/single/" . sha1("$title/$artist/$album");
	
	if($best = $keyval->get($mid))
		return json_decode($best, true);
	
	header('Cached: false');
	
	$results = findAll($title, $artist, $album);
	
	$best = array_shift($results);
	
	$output = array(
		'guess'=>true,
		'service'=>'youtube',
		'resource'=>$best['service_resource']
	);
	
	$keyval->set($mid, json_encode($output));
	return $output;
	
}

function findAll($title, $artist, $album='', $exclude_existing=false, $master_id=null) {
	global $keyval, $r;
	
	$mid = "tinytape/v4/best/all/" . sha1("$title/$artist/$album") . "/v2.1";
	
	if($output = $keyval->get($mid)) {
		$decoded = json_decode($output, true);
		
		if($exclude_existing) {
			$changed = false;
			foreach($decoded as $name=>$result) {
				$resource_id = "youtube:$name";
				
				if($r->sContains("tinytape_instances_$master_id", $resource_id)) {
					unset($decoded[$name]);
					$changed = true;
				}
			}
			
			if($changed)
				$keyval->set($mid, json_encode($decoded));
			
		}
		return $decoded;
	}
	header('Cached: false');
	
	$qstrings = array(
		$artist . ' ' . $title,
		$artist . ' ' . $title . ' official',
		$artist . ' ' . $title . ' hq',
		$artist . ' ' . $title . ' radio edit',
		$artist . ' ' . $title . ' radio version',
		$artist . ' ' . $title . ' lyrics',
		$title . ' ' . $artist,
		$title
	);
	
	if(!empty($album))
		$qstrings[] = "$title $album";
	
	$qbase = 'http://gdata.youtube.com/feeds/api/videos?v=2&max-results=25&alt=json&q=';
	
	$ytqueries = array();
	
	foreach($qstrings as $qs) {
		$ytqueries[] = $qbase . urlencode($qs);
	}
	
	$results = array();
	$result_score = array();
	
	// TODO : Use curl_mutli_init to run these simultaneously
	
	foreach($ytqueries as $q) {
		$feed = file_get_contents($q);
		$jfeed = json_decode($feed, true);
		$jentries = $jfeed['feed']['entry'];
		
		if(empty($jentries))
			continue;
			
		foreach($jentries as $entry) {
			$id = $entry['id']['$t'];
			$id = explode(':', $id);
			$id = $id[count($id) - 1];
			
			$version_title = $entry['title']['$t'];
			
			$resource_id = "youtube:$id";
			if($exclude_existing && $r->sContains("tinytape_instances_$master_id", $resource_id))
				continue;
			
			if(isset($results[$id])) {
				$result_score[$id]++;
				continue;
			}
			
			$unembed = false;
			foreach($entry['yt$accessControl'] as $permission) {
				if($permission["action"] == "embed") {
					$unembed = $permission["permission"] == "denied";
					break;
				}
			}
			if($unembed)
				continue;
			
			$result = array(
				'title'=>$version_title,
				'description'=>$version_title . '; ' . $entry['content']['$t'],
				'service_resource'=>$id,
				"service"=>"youtube"
			);
			$results[$id] = $result;
			
			// TODO : Possibly look at $result['media$group']['media$category'] to increase score
			// TODO : Possibly drop the score if it's a VEVO result
			
			$result_score[$id] = 1;
		}
		
	}
	
	// Remove dirty results
	foreach($result_score as $name=>$score) {
		if($score < 2) {
			unset($result_score[$name]);
			unset($results[$name]);
		}
	}
	
	array_multisort($result_score, SORT_DESC, $results);
	
	// TODO : Implement spammy filter here
	
	$keyval->set($mid, json_encode($results));
	
	return $results;
	
}

function findBestInstance($song_token) {
	global $db;
	
	$song_instance = $db->get_table('song_instance');
	
	$instance = $song_instance->fetch(
		array(
			'song_id'=>$song_token->id
		),
		FETCH_SINGLE_TOKEN,
		array(
			'columns'=>array(
				'service',
				'service_resource',
				new cloud_unescaped('(acoustic * 2 + clean + live * 4 + remix * 4 + IF(LENGTH(version_name) > 0, 2, 0)) AS `relevance`')
			),
			'order'=>new listOrder('relevance', 'ASC')
		)
	);
	
	if($instance === false) {
		
		$best = findBest($song_token->title, $song_token->artist, $song_token->album);
		
		if($best === false)
			return array( 'error'=>'No copies of this song could be found :(' );
		else
			return $best;
		
	} else {
		
		return array(
			'service'=>$instance->service,
			'resource'=>$instance->service_resource
		);
		
	}
	
}