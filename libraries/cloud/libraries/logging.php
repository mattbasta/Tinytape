<?php

/**
 * Cloud Logging
 * http://cloud.serverboy.net/
 *
 * PHP version 5
 *
 * Copyright 2010 Matt Basta
 * 
 * @author     Matt Basta <matt@serverboy.net>
 * 
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * 
 * 	http://www.apache.org/licenses/LICENSE-2.0
 * 
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 * 
 */

if(!class_exists('logging'))
	require_once(CLOUD_PATH_PREFIX . 'libraries/base_logging.php');

class cloud_logging extends logging {
	
	public static function unsafe_query($feature, $line_item, $symptoms) {
		self::exception(
			array(
				'A query has executed that may not properly virtualize.',
				"Feature:\t" . $feature,
				"Line Item:\t" . $line_item,
				'Symptoms may include:',
				$symptoms
			),
			'UNSAFE_QUERY'
		);
		if(CLOUD_BREAK_ON_UNSAFE_QUERIES)
			die("An attempted query has been deemed potentially unsafe. Execution has been terminated to preserve integrity (per configuration). See log for details.");
		elseif(CLOUD_ALERT_UNSAFE_QUERIES)
			echo '<br />A query will be executed that may cause potentially unsafe results. See log for details.';
		return CLOUD_ATTEMPT_UNSAFE_QUERIES;
	}
	public static function warning($message) {
		self::exception($message, 'WARNING');
		if(CLOUD_BREAK_ON_WARNINGS)
			die('Cloud has encountered a warning and has terminated (per configuration). See log for details.');
		elseif(CLOUD_ALERT_WARNINGS)
			echo '<br />Cloud has encountered a warning. See log for details.';
		return false;
	}
	public static function error($message) {
		self::exception($message, 'ERROR');
		if(CLOUD_BREAK_ON_ERRORS)
			die('Cloud has encountered an error and has terminated (per configuration). See log for details.');
		elseif(CLOUD_ALERT_ERRORS)
			echo '<br />Cloud has encountered an error. See log for details.';
		return false;
	}
	
	public static function exception($message, $severity = 'ERROR') {
		self::writeLog($severity . "\t" . time());
		self::writeLog($message);
		return false;
	}
	public static function writeLog($data, $file = null, $indent = 0) {
		if(!isset($file))
			$file = fopen(CLOUD_LOG, 'a');
		if(is_string($data)) {
			fwrite($file, "\n");
			fwrite($file, str_repeat(' ', $indent));
			fwrite($file, $data);
		} elseif(is_array($data))
			foreach($data as $line)
				self::writeLog($line, $file, $indent + 1);
		if($indent == 0)
			fclose($file);
	}
	
}
