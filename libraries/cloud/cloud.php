<?php

/**
 * Serverboy Cloud
 * http://cloud.serverboy.net/
 *
 * PHP version 5
 *
 * Copyright 2011 Matt Basta
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

require(CLOUD_PATH_PREFIX . 'libraries/simpleObjects.php');
// Universal DB Driver Interface
require(CLOUD_PATH_PREFIX . 'libraries/driver.php');
require(CLOUD_PATH_PREFIX . 'libraries/driver.table.php');
require(CLOUD_PATH_PREFIX . 'libraries/column.php');
// Token class and helpers
require(CLOUD_PATH_PREFIX . 'libraries/token.php');


class cloud {
	
	public static function connect($type, $credentials) {
		
		$db_path = CLOUD_PATH_PREFIX . 'engines/' . $type . '.driver.php';
		require_once($db_path);
		
		$db_class = $type . '_driver';
		
		$db = new $db_class($credentials);
		
		return $db;
		
	}
	
	
}

function _comp($x, $comparison, $y) {
	return new comparison($x, $comparison, $y);
}
function _and() {
	if(func_num_args() > 1)
		return new logicCombinator(func_get_args(), 'AND');
	else {
		$arg = func_get_arg(0);
		if(is_array($arg))
			return new logicCombinator($arg, 'AND');
		else
			return $arg;
	}
}
function _st($token) {
	if($token instanceof simpleToken)
		return $token;
	return new simpleToken($token);
}
function _or() {
	if(func_num_args() > 1)
		return new logicCombinator(func_get_args(), 'OR');
	else {
		$arg = func_get_arg(0);
		if(is_array($arg))
			return new logicCombinator($arg, 'OR');
		else
			return $arg;
	}
}

