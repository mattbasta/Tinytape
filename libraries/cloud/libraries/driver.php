<?php

/**
 * Abstract Class Driver
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

abstract class cloud_driver {
	
	private $hasInit = false; // Should be set to true on init
	private $credentials;
	
	// Constructors and Destructors
	public function __construct($credentials) {
		$this->credentials = $credentials;
		// Initialize the database driver
		
		return $this->init($credentials);
	}
	public function __destruct() {
		return $this->close();
	}
	
	// State Functions
	abstract public function init($credentials);
	abstract public function close();
	public function hasBeenInit() {return $this->hasInit;}
	
	
	// Table functions
	abstract public function get_table_list();
	abstract public function get_table($name);
	public function table($name) {return $this->get_table($name);} // Simple alias
	
	
	// Security Functions
	public function escape($data) {
		switch(true) {
			case is_integer($data):
				return $this->escapeInteger($data);
			case is_float($data):
				return $this->escapeFloat($data);
			case is_string($data):
				return $this->escapeString($data);
			case is_bool($data):
				return $this->escapeBool($data);
			case is_array($data):
				return $this->escapeList($data);
			case is_object($data):
				switch(true) {
					case $data instanceof cloud_column:
						return $this->prepareSimpleToken($data->name);
					case $data instanceof simpleToken:
						return $this->prepareSimpleToken($data);
					case $data instanceof logicCombinator:
						return $this->prepareCombinator($data);
					case $data instanceof comparison:
						return $this->prepareComparison($data);
					case $data instanceof listOrder:
						return $this->prepareListOrder($data);
					case $data instanceof cloud_unescaped:
						return $data->getValue();
				}
		}
	}
	abstract public function escapeBool($data); // There should never be quotes with boolean
	abstract public function escapeString($data);
	abstract public function escapeInteger($data);
	abstract public function escapeFloat($data); // Helps out with number padding
	
	abstract public function escapeList($array, $commas = true, $escape = true); // A list of tokens (i.e.: x, y, z)
	
	abstract public function prepareSimpleToken($token);
	abstract public function prepareCombinator($combinator);
	abstract public function prepareComparison($comparison);
	abstract public function prepareListOrder($listorder);
	
}
