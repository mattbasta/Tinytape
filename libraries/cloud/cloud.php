<?php

/**
 * Serverboy Cloud
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

$location = dirname(__FILE__);
define('CLOUD_PATH_PREFIX', $location . (strlen($location) > 1 ? '/' : ''));

// Internal security can be super-secure, but possibly slow.
define('CLOUD_INTERNAL_SECURITY', true);

// Error handling
define('CLOUD_LOG', CLOUD_PATH_PREFIX . '../../../log.txt');
define('CLOUD_ALERT_UNSAFE_QUERIES', false);
define('CLOUD_ALERT_WARNINGS', false);
define('CLOUD_ALERT_ERRORS', true);
define('CLOUD_BREAK_ON_UNSAFE_QUERIES', false);
define('CLOUD_BREAK_ON_WARNINGS', false);
define('CLOUD_BREAK_ON_ERRORS', true);
define('CLOUD_ATTEMPT_UNSAFE_QUERIES', false);

// Base class
require(CLOUD_PATH_PREFIX . 'libraries/base.php');
require(CLOUD_PATH_PREFIX . 'libraries/base_socket.php');
require(CLOUD_PATH_PREFIX . 'libraries/logging.php');
//require(CLOUD_PATH_PREFIX . 'libraries/cache.php');
require(CLOUD_PATH_PREFIX . 'libraries/simpleObjects.php');
// Universal DB Driver Interface
require(CLOUD_PATH_PREFIX . 'libraries/driver.php');
require(CLOUD_PATH_PREFIX . 'libraries/driver.table.php');
require(CLOUD_PATH_PREFIX . 'libraries/returnobj.php');
require(CLOUD_PATH_PREFIX . 'libraries/column.php');
// Token class and helpers
require(CLOUD_PATH_PREFIX . 'libraries/token.php');


class cloud {
	
	public static function create_db($type, $credentials) {
		
		$db_path = CLOUD_PATH_PREFIX . 'engines/' . $type . '.driver.php';
		require_once($db_path);
		
		$db_class = $type . '_driver';
		
		$db = new $db_class($credentials);
		
		return $db;
		
	}
	
	public static function _comp($x, $comparison, $y) {
		return new comparison($x, $comparison, $y);
	}
	public static function _and() {
		if(func_num_args() > 1)
			return new logicCombinator(func_get_args(), 'AND');
		else
			return new logicCombinator(func_get_arg(0), 'AND');
	}
	public static function _st($token) {
		if($token instanceof simpleToken)
			return $token;
		return new simpleToken($token);
	}
	public static function _or() {
		if(func_num_args() > 1)
			return new logicCombinator(func_get_args(), 'OR');
		else
			return new logicCombinator(func_get_arg(0), 'OR');
	}
	
}

