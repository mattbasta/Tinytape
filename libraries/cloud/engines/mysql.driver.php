<?php

/**
 * Cloud MySQL Driver
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

class mysql_driver extends cloud_driver {
	private $connection;
	
	public function init($credentials) {
		// Connect
		$connection = new mysqli($credentials['server'], $credentials['username'], $credentials['password']);
		
		// Select the database
		if(!empty($credentials['database']))
			$connection->select_db($credentials['database']);
		
		// Store the connection
		$this->connection = $connection;
	}
	
	public function close() {
		@$this->connection->close();
	}
	
	// Table Functions
	public function create_table($name, $columns) {
		$query = "CREATE TABLE {$this->prepareSimpleToken($name)} (";
		$cols = array();
		$indices = array();
		foreach($columns as $column) {
			$col = $column->name;
			$col .= ' ' . strtoupper($column->type);
			if($column->length !== false)
				$col .= "({$column->length})";
			if($column->_default !== false)
				$col .= ' DEFAULT ' . $this->escape($column->_default);
			if($column->extra !== false)
				$col .= ' ' . $column->extra;
			if($column->key !== false) {
				switch($column->key) {
					case 'PRI':
						$col .= ' PRIMARY KEY';
						break;
					case 'UNI':
						$col .= ' UNIQUE KEY';
						break;
					default:
						if(isset($indices[$column->key]))
							$indices[$column->key][] = $column->name;
						else
							$indices[$column->key] = array( $column->name );
				}
			}
			$cols[] = $col;
		}
		foreach($indices as $name=>$index)
			$cols[] = 'INDEX ' . $name . ' (' . implode(', ', $index) . ')';
		$query .= implode(', ', $cols);
		$query .= ");";
		
		if(defined("DEBUG"))
			echo "$query\n";
		
		$this->connection->query($query);
	}
	public function get_table_list() {
		$result = $this->connection->query('SHOW TABLES;');
		
		$tab_out = array();
		while($table = $result->fetch_array())
			$tab_out[] = $table[0];
		
		return $tab_out;
	}
	
	public function get_table($name) {
		return new mysql_driver_table($this->connection, $this, $name);
	}
	
	
	public function escapeBool($data) {return $data ? 1 : 0;}
	public function escapeString($data) {
		return '"' . $this->connection->real_escape_string($data) . '"';
	}
	public function escapeInteger($data) {return (int)$data;}
	public function escapeFloat($data) {return (float)$data;}
	
	public function escapeList($array, $commas = true, $escape = true) {
		if(!is_array($array))
			return $escape ? $this->escape($array) : $array;
		
		$final = array();
		foreach($array as $key=>$item) {
			$build = $escape ? $this->escape($item) : $item;
			if(is_string($key))
				$final[] = $this->prepareSimpleToken($key) . " = " . $build;
			else
				$final[] = $build;
		}
		return implode($commas ? ", " : " ", $final);
	}
	
	public function escapeConditions($array) {
		if(!is_array($array))
			return $this->escape($array); # We can force this for conditions
		
		$final = array();
		foreach($array as $key=>$item) {
			if(is_object($item)) {
				$final[] = $this->escape($item);
				continue;
			}
			
			if(is_string($key))
				$final[] = $this->prepareSimpleToken($key) . " = " . $this->escape($item);
			elseif(is_array($item))
				$final[] = $this->escapeConditions($item);
		}
		return implode(" AND ", $final);
	}
	
	public function prepareSimpleToken($token) {
		if($token instanceof simpleToken)
			$tokentext = $token->getToken();
		else
			$tokentext = $token;
		$tokentext = str_replace("\n", '', $tokentext);
		$tokentext = str_replace("\r", '', $tokentext);
		$tokentext = str_replace("\t", '', $tokentext);
		$tokentext = '`' . str_replace('`', "'", $tokentext) . '`';
		return $tokentext;
	}
	
	public function prepareCombinator($combinator) {
		$logic = strtoupper($combinator->getLogic());
		$terms = $combinator->getTerms();
		
		if(count($terms) == 1) {
			switch($logic) {
				case 'NOT':
					return 'NOT ' . $this->escape($terms[0]);
				case 'XOR':
					/*
					In theory, XOR should never be used with one term because it would always return
					false. We're going to be cool and make it like OR and AND, though.
					*/
				case 'OR':
				case 'AND':
					return $this->escape($terms[0]); // No logic involved!
			}
		} else {
			$build = array();
			
			switch($logic) {
				case 'NOT':
					foreach($terms as $term)
						$build[] = 'NOT ' . $this->escape($term);
					return implode(' AND ', $build);
				case 'XOR':
				case 'OR':
				case 'AND':
					foreach($terms as $term)
						$build[] = $this->escape($term);
						
					$backtrace = debug_backtrace(true);
					$caller = $backtrace[ min(count($backtrace), 2) ]['function'];
					if($caller == 'prepareCombinator' || $caller == 'escapeConditions')
						return '(' . implode(" $logic ", $build) . ')';
					else
						return implode(" $logic ", $build);
			}
		}
	}
	
	public function prepareComparison($comparison) {
		return "{$this->escape($comparison->getObject1())} {$comparison->getOperation()} {$this->escape($comparison->getObject2())}";
	}
	
	public function prepareListOrder($listorder) {
		return $this->prepareSimpleToken($listorder->getVariable()) . ' ' . $listorder->getOrder();
	}
}


class mysql_driver_table implements cloud_driver_table {
	
	private $connection;
	private $driver;
	private $name;
	private $primary_cache;
	
	private $column_cache;
	
	public function __construct($connection, $driver, $name, $primary="") {
		$this->connection = $connection;
		$this->driver = $driver;
		$this->name = $name;
		if(!empty($primary))
			$this->primary_cache = $primary;
	}
	
	public function drop() {
		$this->connection->query("DROP TABLE " . $this->driver->escape(_st($this->name)));
		
		$this->connection = null;
		$this->driver = null;
		$this->name = null;
		$this->primary_cache = null;
		$this->column_cache = null;
	}
	
	public function get_driver() {return $this->driver;}
	
	public function get_columns() {
		if($this->column_cache) return $this->column_cache;
		
		$query = $this->connection->query('DESCRIBE ' . $this->driver->prepareSimpleToken($this->name) . ';');
		if(!$query)
			throw new Exception("Table does not exist");
		
		$columns = array();
		while($result = $query->fetch_array()) {
			$typelen = $result[1];
			$parpos = strpos($typelen, '(');
			if(strpos($typelen, '(') !== false) {
				$type = substr($typelen, 0, $parpos);
				$length = substr($typelen, $parpos + 1, strlen($typelen) - 2 - $parpos);
			} else {
				$type = $typelen;
				$length = 0;
			}
			$column = new cloud_column($result[0], $type, $length, $result[3], $result[4], $result[5]);
			$columns[$result[0]] = $column;
		}
		
		$this->column_cache = $columns;
		return $columns;
	}
	
	public function get_primary_column() {
		if($this->primary_cache) return $this->primary_cache;

		$columns = $this->get_columns();
		foreach($columns as $column) {
			if($column->key == 'PRI') {
				$this->primary_cache = $column;
				return $column;
			}
		}
	}
	
	public function get_length() {
		$vana = ini_set('mysql.trace_mode','Off');
		
		$temp = $this->connection->query("SELECT SQL_CALC_FOUND_ROWS * FROM {$this->driver->prepareSimpleToken($this->name)} LIMIT 1");
		$result = $this->connection->query("SELECT FOUND_ROWS()");
		$total = $result->fetch_row();
		
		ini_set('mysql.trace_mode',$vana);
		
		return $total[0];
	}
	
	public function insert($values) {
		$driver = $this->driver;
		
		$keys = array_keys($values);
		$values = array_values($values);
		
		foreach($keys as &$key)
			$key = _st((string)$key);
		
		$query = "INSERT INTO {$driver->prepareSimpleToken($this->name)} ({$driver->escapeList($keys)}) VALUES ({$driver->escapeList($values)});";
		$query = $this->connection->query($query);
		
		return $this->connection->insert_id;
		
	}
	
	public function update($conditions, $values, $limit = -1, $order = '') {
		
		if(empty($conditions))
			return false;
		
		$driver = $this->driver;
		
		$query = "UPDATE {$driver->prepareSimpleToken($this->name)} SET " . $driver->escapeList($values);
		if($conditions !== true)
			$query .= " WHERE " . $driver->escapeConditions($conditions);
		
		
		if($limit > -1) {
			if(!empty($order))
				$query .= " ORDER BY {$driver->escapeList($order)}";
			$limit = (int)$limit;
			$query .= " LIMIT $limit";
		}
		
		$query .= ';';
		$this->connection->query($query);
	}
	
	public function delete($conditions, $limit = -1, $order = '') {
		$driver = $this->driver;
		
		$query = "DELETE FROM {$driver->prepareSimpleToken($this->name)} WHERE " . $driver->escapeConditions($conditions);
		
		if($limit > -1) {
			if(!empty($order))
				$query .= " ORDER BY {$driver->escapeList($order)}";
			$limit = (int)$limit;
			$query .= " LIMIT $limit";
		}
		
		$query .= ';';
		$this->connection->query($query);
	}
	
	public function fetch($conditions, $return, $params = '') {
		$driver = $this->driver;
		
		// Tell the driver what the primary key is so we can escape it out
		$pcol = $this->get_primary_column();
		
		if(!is_array($params))
			$params = array();
		$columns = isset($params['columns']) ? $params['columns'] : '*';
		$limit = isset($params['limit']) ? (int)$params['limit'] : -1;
		$offset = isset($params['offset']) ? (int)$params['offset'] : 0;
		$order = isset($params['order']) ? $params['order'] : '';
		$grouping = isset($params['grouping']) ? $params['grouping'] : '';
		$arrid = isset($params['arrayid']) ? $params['arrayid'] : $this->get_primary_column()->name;
		
		if($return == FETCH_UNLOADED_TOKENS || $return == FETCH_SINGLE_UNLOADED_TOKEN || $return == FETCH_COUNT) { // Unloaded tokens don't need any values.
			$columns = $driver->escape($pcol);
		} else {
			if(is_array($columns)) {
				if($return == FETCH_SINGLE) {
					if(count($columns) > 1)
						$columns = $columns[0];
					if(!($columns instanceof simpleToken))
						$columns = _st($columns);
				} else {
					if($return != FETCH_COUNT && $return != FETCH_SINGLE && $return != FETCH_SINGLE_ARRAY && $return != FETCH_SINGLE_TOKEN) {
						$found_rowid = false;
						foreach($columns as $col) {
							if((is_string($col) && $col == $arrid) || ($col instanceof simpleToken && $col->getToken() == $arrid)) {
								$found_rowid = true;
								break;
							}
						}
						if(!$found_rowid)
							$columns[] = _st($arrid);
					}
					foreach($columns as $key => &$value)
						if(is_string($value))
							$value = _st($value);
				}
			} elseif(is_string($columns) && $columns != '*')
				$columns = _st($columns);
			if(!is_string($columns))
				$columns = $driver->escapeList($columns);
		}
		
		$query = "SELECT $columns FROM {$driver->prepareSimpleToken($this->name)}";
		if(!empty($conditions))
			$query .= " WHERE {$driver->escapeConditions($conditions)}";
		if(!empty($grouping))
			$query .= " GROUP BY {$driver->escapeList($grouping)}";
		if(!empty($order))
			$query .= " ORDER BY {$driver->escapeList($order)}";
		
		if($return == FETCH_SINGLE_ARRAY || $return == FETCH_SINGLE_TOKEN ||
		   $return == FETCH_SINGLE_UNLOADED_TOKEN || $return == FETCH_SINGLE)
			$limit = 1;
		
		if($limit > -1 || $offset > 0) {
			if($limit == -1) // As per the MySQL docs, use a REALLY BIG NUMBER!
				$limit = '18446744073709551615';
			
			$query .= " LIMIT $limit";
			if($offset > 0)
				$query .= " OFFSET $offset";
		}
		
		$query .= ';';
		
		if(defined("DEBUG"))
			echo $query, "\n";
		
		$result = $this->connection->query($query);
		if(defined("DEBUG"))
			echo $this->connection->error, "\n";
		$output = false;
		
		// Nothing is returned
		if($return > 1)
			if($result === false || $result->num_rows == 0)
				return false;
		
		switch($return) {
			case FETCH_COUNT: // Row count
				$output = $result->num_rows;
				break;
			case FETCH_ARRAY: // Array
				$output = array();
				while($row = $result->fetch_array(MYSQLI_ASSOC))
					$output[$row[$arrid]] = $row;
				break;
			case FETCH_SINGLE_ARRAY: // Single Array
				$output = $result->fetch_array(MYSQLI_ASSOC);
				break;
			case FETCH_TOKENS: // Tokens
			case FETCH_UNLOADED_TOKENS: // Unloaded tokens
				if($result->num_rows == 0)
					return false;
				$output = array();
				while($row = $result->fetch_array(MYSQLI_ASSOC)) {
					$output[$row[$arrid]] = new cloud_token(
						$this,
						$pcol->name,
						$row[$pcol->name],
						$return == FETCH_TOKENS ? $row : ''
					);
				}
				break;
			case FETCH_SINGLE_TOKEN: // Single Token
			case FETCH_SINGLE_UNLOADED_TOKEN: // Single Unloaded Token
				if($result->num_rows == 0)
					return false;
				$row = $result->fetch_array(MYSQLI_ASSOC);
				$output = new cloud_token(
					$this,
					$pcol->name,
					$row[$pcol->name],
					$return == FETCH_SINGLE_TOKEN ? $row : ''
				);
				break;
			case FETCH_SINGLE: // Single Value
				$output = $result->fetch_array();
				$output = current($output);
				break;
		}
		
		return $output;
	}
	
	public function fetch_exists($conditions) {
		return $this->fetch(
			$conditions,
			FETCH_COUNT,
			array(
				'limit' => 1,
				'columns' => $this->primary_cache ? $this->primary_cache->name : '*'
			)
		) > 0;
	}
	
	// TODO : Implement this
	public function start_write_transaction() { return false; }
	public function flush_write_transaction() { return false; }
	
}
