<?php

/**
 * Database Token
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

class cloud_token extends cloud_base {
	/**
	 * Secure variables:
	 *  table	- Database table
	 *   > A database table object
	 *  index	- Table index
	 *   > An array, consider a conditional array providing an intersection
	 *  data	- Currently available column data
	 *   > An array, contains cached remote values for the row
	 *  fetched	- Has column data been fetched
	 *   > Boolean, describes whether the data has been downloaded yet
	 */
	
	public function __construct($driver, $table, $index, $values = '') {
		// Securely store the database information
		self::secure( 'driver',		$driver );
		self::secure( 'table',		$table );
		self::secure( 'index',		$index );
		
		if(empty($values))
			self::secure('fetched', false);
		else {
			self::secure('fetched', true);
			self::secure('data', $values);
		}
	}
	
	// Basic CRUD functions (excluding creation; token already exists!)
	public function __get($name) {
		if(self::secure('fetched')) {
			$data = self::secure('data');
		} else {
			$driver = self::secure('driver');
			$table = self::secure('table');
			
			$data = $table->fetch(array('_primary_key' => self::secure('index')), FETCH_SINGLE_ARRAY);
			self::secure('data', $data);
			self::secure('fetched', true);
		}
		return $data[$name];
	}
	public function getValue($name) {return $this->__get($name);}
	public function getBoolValue($name) {
		$val = $this->getValue($field);
		return !(empty($val) || $val === "0" || $val === 0);
	}
	public function __set($name, $value) {
		
		// Get the database info
		$table = self::secure('table');
		$index = self::secure('index');
		
		// Perform the query
		$table->update_row(
			$index,
			array( $name => $value )
		);
		
		// Reset the value within the secure storage for caching's sake
		$data = self::secure('data');
		$data[$name] = $value;
		self::secure('data', $data);
	}
	public function setValues($params) {
		// TODO : Optimize this and the above function to use fewer DB queries when running a large set of updated columns.
		foreach($params as $param=>$value)
			$this->__set($param, $value);
	}
	public function destroy() {
		$table = self::secure('table');
		$table->delete(
			array(
				'_primary_key' => self::secure('index')
			),
			1
		);
		
		self::secure('fetched', false);
		self::secure('table', false, true);
		self::secure('index', false, true);
		
	}
	
}