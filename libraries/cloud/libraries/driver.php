<?php

/**
 * Abstract Class Driver
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

abstract class cloud_driver extends cloud_base {
	
	private $hasInit = false; // Should be set to true on init
	
	// Constructors and Destructors
	public function __construct($credentials) {
		// Securely stuff away our credentials
		self::readonly('credentials', $credentials);
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
	abstract public function create_table($name, $columns);
	abstract public function get_table_list();
	abstract public function get_table($name);
	public function delete_table($name) {
		// Retrieve the table to delete
		$table = $this->get_table($name);
		// Destroy it
		$table->destroy();
	}
	
	
	// Security Functions
	public function escape($data, $no_quotes = false) {
		switch(true) {
			case is_integer($data):
				return $this->escapeInteger($data, $no_quotes);
			case is_float($data):
				return $this->escapeFloat($data, $no_quotes);
			case is_string($data):
				return $this->escapeString($data, $no_quotes);
			case is_bool($data):
				return $this->escapeBool($data);
			case is_array($data):
				return $this->escapeArray($data, $no_quotes);
			case is_object($data):
				switch(true) {
					case $data instanceof simpleToken:
						return $this->prepareSimpleToken($data, $no_quotes);
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
	abstract public function escapeString($data, $no_quotes = false);
	abstract public function escapeInteger($data, $no_quotes = false);
	abstract public function escapeFloat($data, $no_quotes = false); // Helps out with number padding
	abstract public function escapeArray($data, $type = 0, $no_quotes = false); // Helps with delimited values
	/*
		Escape Array Types:
		- 0 :	Nondelimited
		- 1 :	Delimited
		- 2 :	Comparison
	*/
	
	abstract public function prepareSimpleToken($token, $no_quotes = false);
	abstract public function prepareCombinator($combinator);
	abstract public function prepareComparison($comparison);
	abstract public function prepareListOrder($listorder);
	
	
}