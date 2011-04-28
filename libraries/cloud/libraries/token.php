<?php

/**
 * Database Token
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

class cloud_token {
	
	private $table;
	private $index_name;
	private $index;
	private $data;
	private $fetched = false;
	
	public function __construct($table, $index_name, $index, $values = '') {
		// Securely store the database information
		$this->table = $table;
		$this->index_name = $index_name;
		$this->index = $index;
		
		if(!empty($values)) {
			$this->fetched = true;
			$this->data = $values;
		}
	}
	
	// Basic CRUD functions (excluding creation; token already exists!)
	public function __get($name) {
		if($this->fetched) {
			return $this->data[$name];
		} else {
			$data = $this->table->fetch(array($this->index_name => $this->index), FETCH_SINGLE_ARRAY);
			$this->data = $data;
			$this->fetched = true;
		}
		return $data[$name];
	}
	
	public function getValue($name) {return $this->__get($name);}
	
	public function getBoolValue($name) {
		$val = $this->getValue($field);
		return !(empty($val) || $val === "0" || $val === 0);
	}
	
	public function __set($name, $value) {
		$this->table->update(
			array( $this->index_name => $this->index ),
			array( $name => $value )
		);
		
		$this->data[$name] = $value;
	}
	
	public function setValues($params) {
		$this->table->update(
			array( $this->index_name => $this->index ),
			$params
		);
		
		foreach($params as $key=>$value)
			$this->data[$key] = $value;
	}
	
	public function destroy() {
		$this->table->delete(
			array(
				$this->index_name => $this->index
			),
			1
		);
		
		$this->fetched = false;
		$this->table = null;
		$this->index_name = null;
		$this->index = null;
		
	}
}
