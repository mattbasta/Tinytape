<?php

/**
 * Cloud Apache Cassandra Driver
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

class cassandra_driver extends cloud_driver {
	
	public $primary_key = '';
	
	public function init($credentials) {
		// Expects:
		//  - Server
		//  - Port
		//  - Keyspace
		
		// Connect
		$connection = new cassandra($credentials['server'], $credentials['username'], $credentials['password']);
		
		// Store the connection
		self::secure('connection', $connection);
		
	}
	public function close() {
		$connection = self::secure('connection');
		$connection->close();
	}
	
	// Table Functions
	
	public function create_table($name, $columns) {
		$connection = self::secure('connection');
		
		$query = "CREATE TABLE {$this->prepareSimpleToken($name)} (";
		$cols = array();
		$indices = array();
		foreach($columns as $column) {
			$col = $column->name;
			$col .= ' ' . strtoupper($column->type);
			if($column->length !== false)
				$col .= "({$column->length})";
			if($column->def !== false)
				$col .= ' DEFAULT ' . $this->escape($column->def);
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
		
		$connection->query($query);
	}
	public function get_table_list() {
		$connection = self::secure('connection');
		$result = $connection->query('SHOW TABLES;');
		
		$tab_out = array();
		while($table = $result->fetch_array())
			$tab_out[] = $table[0];
		
		return $tab_out;
	}
	public function get_table($name) {
		// TODO : Check for existance.
		return new cassandra_driver_table(self::secure('connection'), $this, $name);
	}
	
	
	public function escapeBool($data) {return $data ? 1 : 0;}
	public function escapeString($data, $no_quotes = false) {
		$connection = self::secure('connection');
		
		if(empty($data))
			return $no_quotes ? '' : '""';
		
		return $no_quotes ? $data : '"' . $connection->real_escape_string($data) . '"';
	}
	public function escapeInteger($data, $no_quotes = false) {return (int)$data;}
	public function escapeFloat($data, $no_quotes = false) {return (float)$data;}
	/*
		Escape Array Types:
		- 0 :	Nondelimited
		- 1 :	Delimited
		- 2 :	Comparison
	*/
	public function escapeArray($data, $type = 0, $no_quotes = false) {
		if(!is_array($data))
			return $this->escape($data, $no_quotes);
		
		switch($type) {
			case 0:
				$delimiter = ' ';
				break;
			case 1:
				$delimiter = ', ';
				break;
			case 2:
				$delimiter = ' AND ';
				break;
		}
		
		$final = array();
		foreach($data as $key => $value) {
			$build = '';
			$orig_type = gettype($value);
			
			if(is_string($value))
				$value = trim($value);
			
			$value = $this->escape($value, $no_quotes);
			
			switch($type) {
				case 1:
				case 0:
					
					if(is_string($key))
						$build = "{$this->prepareSimpleToken($key)} = $value";
					else
						$build = $value;
					break;
				case 2:
					switch($orig_type) {
						case 'integer':
						case 'string':
						case 'double':
						case 'float':
						case 'boolean':
							if(is_string($key)) {
								if(!empty($this->primary_key) && $key == '_primary_key')
									$key = $this->primary_key;
								$build = "{$this->prepareSimpleToken($key)} = $value";
							} else
								$build = 'TRUE';
							break;
						case 'array':
						case 'object':
							$build = $value;
					}
					break;
			}
			
			$final[] = $build;
			
		}
		
		return implode($delimiter, $final);
		
	}
	
	public function prepareSimpleToken($token, $no_quotes = false) {
		if(!is_string($token))
			$tokentext = $token->getToken();
		else
			$tokentext = $token;
		if(!empty($this->primary_key) && $tokentext == '_primary_key')
			$tokentext = $this->primary_key;
		$tokentext = str_replace("\n", '', $tokentext);
		$tokentext = str_replace("\r", '', $tokentext);
		$tokentext = str_replace("\t", '', $tokentext);
		if(!$no_quotes)
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
					if($caller == 'prepareCombinator' || $caller == 'escapeArray')
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


class cassandra_driver_table extends cloud_base implements cloud_driver_table {
	
	public function __construct($connection, $driver, $name) {
		self::secure('connection', $connection);
		self::secure('driver', $driver);
		self::secure('name', $name);
	}
	
	public function destroy() {
		$connection = self::secure('connection');
		$driver = self::secure('driver');
		$name = self::secure('name');
		
		$connection->query('DROP TABLE ' . $driver->prepareSimpleToken($name) . ';');
		
		self::secure('connection', false, true);
		self::secure('driver', false, true);
		self::secure('name', false, true);
		
	}
	
	public function get_driver() {return self::secure('driver');}
	
	public function get_columns() {
		
		$columns = self::secure('column_cache');
		if($columns) return $columns;
		
		$connection = self::secure('connection');
		$driver = self::secure('driver');
		$name = self::secure('name');
		
		$query = $connection->query('DESCRIBE ' . $driver->prepareSimpleToken($name) . ';');
		
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
		
		self::secure('column_cache', $columns);
		return $columns;
		
	}
	public function get_primary_column() {
		$pcache = self::secure('primary_cache');
		if($pcache) return $pcache;
		
		$columns = $this->get_columns();
		foreach($columns as $column) {
			if($column->key == 'PRI') {
				self::secure('primary_cache', $column);
				return $column;
			}
		}
	}
	public function create_column($position, $column) {
		$columns = self::secure('column_cache', false, true);
		$columns = self::secure('primary_cache', false, true);
		$connection = self::secure('connection');
		$driver = self::secure('driver');
		$name = self::secure('name');
		
		if($position == 0)
			$position = 'FIRST';
		elseif(!empty($position))
			$position = 'AFTER ' . $driver->prepareSimpleToken($position);
		
		$length = $column->length;
		if($length <= 0)
			$length = '';
		else
			$length = "($length)";
		$query = "ALTER TABLE {$driver->prepareSimpleToken($name)} ADD COLUMN {$column->name} {$column->type}$length $position;";
		
		$connection->query($query);
		
	}
	public function delete_column($name) {
		$columns = self::secure('column_cache', false, true);
		$columns = self::secure('primary_cache', false, true);
		$connection = self::secure('connection');
		$driver = self::secure('driver');
		$tname = self::secure('name');
		
		$connection->query("ALTER TABLE {$driver->prepareSimpleToken($tname)} DROP COLUMN {$driver->prepareSimpleToken($name)};");
	}
	
	public function get_length() {
		$connection = self::secure('connection');
		$driver = self::secure('driver');
		$tname = self::secure('name');
		
		$pcol = $this->get_primary_column();
		
		$result = $connection->query("SELECT {$pcol->name} FROM {$driver->prepareSimpleToken($tname)};");
		
		$count = $result->num_rows;
		return $count;
	}
	
	public function insert_row($id, $values) {
		$connection = self::secure('connection');
		$driver = self::secure('driver');
		$name = self::secure('name');
		
		$cols = $this->get_columns();
		$pcol = $this->get_primary_column();
		$driver->primary_key = $pcol->name;
		
		$values[$pcol->name] = $id;
		
		$keys = array_keys($values);
		$values = array_values($values);
		
		$query = "INSERT INTO {$driver->prepareSimpleToken($name)} ({$driver->escapeArray($keys, 1)}) VALUES ({$driver->escapeArray($values, 1)});";
		
		$query = $connection->query($query);
		
		$driver->primary_key = '';
		
	}
	public function upsert_row($id, $values) {
		$connection = self::secure('connection');
		$driver = self::secure('driver');
		$name = self::secure('name');
		
		$cols = $this->get_columns();
		$pcol = $this->get_primary_column();
		$driver->primary_key = $pcol->name;
		
		$values[$pcol] = $id;
		
		$upsertvalues = $values;
		unset($upsertvalues['pcol']);
		
		$query = "INSERT INTO {$driver->prepareSimpleToken($name)} VALUES ({$driver->escapeArray($values, 1)}) ON DUPLICATE KEY UPDATE {$driver->escapeArray($upsertvalues, 1)};";
		
		$connection->query($query);
		
		$driver->primary_key = '';
		
	}
	public function update_row($id, $values) {
		return $this->update(
			array(
				'_primary_key' => $id
			),
			$values,
			1
		);
	}
	
	public function update($conditions = false, $values = '', $limit = -1, $order = '') {
		
		if(empty($conditions))
			return false;
		
		$connection = self::secure('connection');
		$driver = self::secure('driver');
		$name = self::secure('name');
		
		$pcol = $this->get_primary_column();
		$driver->primary_key = $pcol->name;
		
		$query = "UPDATE {$driver->prepareSimpleToken($name)} SET " . $driver->escapeArray($values, 1);
		if($conditions !== true)
			$query .= " WHERE " . $driver->escapeArray($conditions, 2);
		
		if(!empty($order))
			$query .= " ORDER BY {$driver->escapeArray($order, 1)}";
		
		if($limit > -1) {
			$limit = (int)$limit;
			$query .= " LIMIT $limit";
		}
		
		$query .= ';';
		$connection->query($query);
		
		$driver->primary_key = '';
		
		
	}
	public function delete($conditions = false, $limit = -1, $order = '') {
		$connection = self::secure('connection');
		$driver = self::secure('driver');
		$name = self::secure('name');
		
		$pcol = $this->get_primary_column();
		$driver->primary_key = $pcol->name;
		
		$query = "DELETE FROM {$driver->prepareSimpleToken($name)} WHERE " . $driver->escapeArray($conditions, 2);
		
		if(!empty($order))
			$query .= " ORDER BY {$driver->escapeArray($order, 1)}";
		
		if($limit > -1) {
			$limit = (int)$limit;
			$query .= " LIMIT $limit";
		}
		
		$query .= ';';
		$connection->query($query);
		
		$driver->primary_key = '';
		
	}
	public function delete_row($id) {
		$connection = self::secure('connection');
		$driver = self::secure('driver');
		$name = self::secure('name');
		
		$pcol = $this->get_primary_column();
		
		$query = "DELETE FROM {$driver->prepareSimpleToken($name)} WHERE {$driver->prepareSimpleToken($pcol->name)} = {$driver->escape($id)};";
		
		$connection->query($query);
		
	}
	
	// The pseudocolumn "_primary_key" should be used to denote the primary key
	/*
	Params
		- Columns
		- Limit
		- Offset
		- Order
		- Array ID (Expects column name)
	*/
	public function fetch($conditions = '', $return = 0, $params = '') {
		$connection = self::secure('connection');
		$driver = self::secure('driver');
		$name = self::secure('name');
		
		// Tell the driver what the primary key is so we can escape it out
		$pcol = $this->get_primary_column();
		$driver->primary_key = $pcol->name;
		
		if(!is_array($params))
			$params = array();
		$columns = isset($params['columns']) ? $params['columns'] : '*';
		$limit = isset($params['limit']) ? $params['limit'] : -1;
		$offset = isset($params['offset']) ? $params['offset'] : 0;
		$order = isset($params['order']) ? $params['order'] : '';
		$arrid = isset($params['arrayid']) ? $params['arrayid'] : $pcol->name;
		
		if($return == 6 || $return == 7) { // Unloaded tokens don't need any values.
			$columns = new simpleToken('_primary_key');
			$columns = $driver->prepareSimpleToken($columns);
		} else {
			if(is_array($columns)) {
				if($return == 8) {
					if(count($columns) > 8)
						$columns = $columns[0];
					if(!($columns instanceof simpleToken))
						$columns = cloud::_st($columns);
					cloud_logging::warning("FETCH_SINGLE command passed with multiple parameters.");
				} else {
					foreach($columns as $key => &$value) {
						if(	$value === '_primary_key' ||
							($value instanceof simpleToken && $value->getToken() == '_primary_key')) {
							$columns[$key] = $pcol->name;
							continue;
						}
						if(is_string($value))
							$value = cloud::_st($value);
					}
					if(!in_array($pcol->name, $columns))
						$columns[] = cloud::_st($pcol->name);
				}
			} elseif(is_string($columns) && $columns != '*')
				$columns = cloud::_st($columns);
			if(!is_string($columns))
				$columns = $driver->escapeArray($columns, 1);
		}
		
		$query = "SELECT $columns FROM {$driver->prepareSimpleToken($name)}";
		if(!empty($conditions))
			$query .= " WHERE {$driver->escapeArray($conditions, 2)}";
		if(!empty($order))
			$query .= " ORDER BY {$driver->escapeArray($order, 1)}";
		
		if($return == 3 || $return == 5 || $return == 7 || $return == 8)
			$limit = 1;
		
		if($limit > -1 || $offset > 0) {
			if($limit == -1) // As per the cassandra docs, use a REALLY BIG NUMBER!
				$limit = '18446744073709551615';
			else
				$limit = (int)$limit;
			
			$query .= " LIMIT $limit";
			if($offset > 0) {
				$offset = (int)$offset;
				$query .= " OFFSET {$offset}";
			}
		}
		
		$query .= ';';
		#echo $query;
		
		$driver->primary_key = '';
		
		$result = $connection->query($query);
		echo $connection->error;
		$output = false;
		
		// Nothing is returned
		if($return > 1)
			if($result === false || $result->num_rows == 0)
				return false;
		
		
		
		switch($return) {
			case FETCH_RESULT: // Result object
				$output = new cassandra_return( array(
					'driver' => $driver,
					'table' => $this,
					'query' => $result
				));
				break;
			case FETCH_COUNT: // Row count
				$output = $result->num_rows;
				break;
			case FETCH_ARRAY: // Array
				$output = array();
				while($row = $result->fetch_array(cassandra_ASSOC))
					$output[$row[$arrid]] = $row;
				break;
			case FETCH_SINGLE_ARRAY: // Single Array
				$output = $result->fetch_array(cassandra_ASSOC);
				break;
			case FETCH_TOKENS: // Tokens
			case FETCH_UNLOADED_TOKENS: // Unloaded tokens
				$output = array();
				while($row = $result->fetch_array(cassandra_ASSOC)) {
					$output[$row[$arrid]] = new cloud_token(
						$driver,
						$this,
						$row[$pcol->name],
						$return == 4 ? $row : ''
					);
				}
				break;
			case FETCH_SINGLE_TOKEN: // Single Token
			case FETCH_SINGLE_UNLOADED_TOKEN: // Single Unloaded Token
				$row = $result->fetch_array(cassandra_ASSOC);
				$output = new cloud_token(
					$driver,
					$this,
					$row[$pcol->name],
					$return == 5 ? $row : ''
				);
				break;
			case FETCH_SINGLE: // Single Value
				$output = $result->fetch_array();
				$output = current($output);
				break;
		}
		
		return $output;
		
	}
	
	public function fetch_exists($conditions = '') {
		return $this->fetch(
			$conditions,
			FETCH_COUNT,
			array(
				'columns' => '_primary_key',
				'limit' => 1
			)
		) > 0;
	}
	
	// TODO : Implement this
	public function start_write_transaction() { return false; }
	public function flush_write_transaction() { return false; }
	
}

class cassandra_return extends cloud_return {
	
	// State Functions
	public function init($construct) {
		// Store away the query object
		$query =& $construct['query'];
		$this->length = $query->num_rows;
		self::secure('query', $query);
		
		self::secure('driver', $contruct['driver']);
		self::secure('table', $contruct['table']);
	}
	public function close() {}
	
	// Retrieval Functions
	public function next_array() {
		$query = self::secure('query');
		$this->pointer++;
		return $query->fetch_array(cassandra_ASSOC);
	}
	public function next_token() {
		$query = self::secure('query');
		$driver = self::secure('driver');
		$table = self::secure('table');
		
		$this->pointer++;
		$row_data = $query->fetch_array(cassandra_ASSOC);
		
		// TODO : Cache this?
		$pcol = $table->get_primary_column();
		
		return new cloud_token(
			$driver,
			$table,
			$row_data[$pcol->name],
			$row_data
		);
	}
	
	// TODO : Remove code duplication
	public function peek_array() {
		$query = self::secure('query');
		$data = $query->fetch_array(cassandra_ASSOC);
		$query->data_seek($this->pointer);
		return $data;
	}
	public function peek_token() {
		$query = self::secure('query');
		$driver = self::secure('driver');
		$table = self::secure('table');
		
		$row_data = $query->fetch_array(cassandra_ASSOC);
		$query->data_seek($this->pointer);
		
		// TODO : Cache this?
		$pcol = $table->get_primary_column();
		
		return new cloud_token(
			$driver,
			$table,
			$row_data[$pcol->name],
			$row_data
		);
	}
	
	// Result Set Functions
	public function rewind() {
		$query = self::secue('query');
		$query->data_seek(0);
		$this->pointer = 0;
	}
	public function skip($count = 1) {
		$new_pointer = $this->pointer + $count;
		if($new_pointer < 0 || $new_pointer >= $this->length)
			return false; // TODO : Logging?
		
		$this->pointer = $new_pointer;
		
		$query = self::secue('query');
		$query->data_seek($this->pointer);
	}
	public function seek($index) {
		if($index < 0 || $index >= $this->length)
			return false; // TODO : Logging?
		
		$this->pointer = $index;
		
		$query = self::secue('query');
		$query->data_seek($index);
		
	}
	public function slide($until) {
		$query = self::secure('query');
		do {
			$matches = true;
			$row = $query->fetch_array(cassandra_ASSOC);
			
			foreach($until as $key=>$value) {
				if($row[$key] != $value) {
					$matches = false;
					break;
				}
				// TODO : Virtualization for the comparison objects
			}
			
			$this->pointer++;
			if($this->pointer == $this->length)
				return false;
		} while(!$matches);
		
		$this->pointer--;
		$query->data_seek($this->pointer);
		return true;
	}
	public function remaining() {
		return $this->length - $this->pointer;
	}
	public function size() {
		return $this->length;
	}
	
}

