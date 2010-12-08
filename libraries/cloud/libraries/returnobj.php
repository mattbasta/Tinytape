<?php

/**
 * Abstract Return Object
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

abstract class cloud_return extends cloud_base {
	
	private $pointer = 0;
	private $length = 0;
	
	public function __construct($construct) {
		// Initialize the database driver
		return $this->init($construct);
	}
	public function __destruct() { $this->close(); }
	
	// State Functions
	abstract public function init($construct);
	abstract public function close();
	
	// Retrieval Functions
	abstract public function next_array();
	abstract public function next_token();
	abstract public function peek_array();
	abstract public function peek_token();
	
	// Result Set Functions
	abstract public function rewind();
	abstract public function skip($count = 1);
	abstract public function seek($index);
	abstract public function slide($until); // Seek until condition ($until) is met (array of conditions)
	abstract public function remaining();
	abstract public function size();
	
}