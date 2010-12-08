<?php

/**
 * Abstract Class Driver
 * Table Interface
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

define('FETCH_RESULT', 0); // A result object
define('FETCH_COUNT', 1); // An integer count
define('FETCH_ARRAY', 2); // An array of values from the database
define('FETCH_SINGLE_ARRAY', 3); // A single array of values from a row in the database
define('FETCH_TOKENS', 4); // An array of tokens
define('FETCH_SINGLE_TOKEN', 5); // A single token
define('FETCH_UNLOADED_TOKENS', 6); // An array of tokens (loaded on access)
define('FETCH_SINGLE_UNLOADED_TOKEN', 7); // A single token (loaded on access)
define('FETCH_SINGLE', 8); // A single value

interface cloud_driver_table {
	
	public function __construct($connection, $driver, $name);
	public function destroy();
	
	
	public function get_driver();
	
	public function get_columns();
	public function get_primary_column();
	public function create_column($position, $column);
	public function delete_column($name);
	
	public function get_length(); // To return an integer row count
	
	// $id should be the primary key for the row
	// Functions should return the token for the row
	public function insert_row($id, $values);
	public function upsert_row($id, $values);
	public function update_row($id, $values);
	public function delete_row($id);
	
	// Safety first...default to False
	public function update($conditions = false, $values = '', $limit = -1, $order = '');
	public function delete($conditions = false, $limit = -1, $order = '');
	
	// The pseudocolumn "_primary_key" should be used to denote the primary key
	/*
	Params
		- Columns
		- Limit
		- Offset
		- Order
		- Array ID (Expects column name)
	*/
	public function fetch($conditions = '', $return = 0, $params = '');
	public function fetch_exists($conditions = '');
	
	// Improve performance by sending write operations as a single transaction
	public function start_write_transaction();
	public function flush_write_transaction();
	
}