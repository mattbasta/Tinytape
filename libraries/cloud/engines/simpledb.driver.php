<?php

/**
 * Cloud SimpleDB Driver
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


/**
 * SimpleDB has a unique nomenclature for certain objects:
 * 		Domain		:	Table
 *	 	Attribute	:	Columns
 * 		Item		:	Row
 * 		Values		:	Cell
 */

$intsize = (string)PHP_INT_SIZE;
define('SIMPLEDB_INT_LENGTH', strlen($intsize));

$cloud_simpledb_multi = null;

function cloud_simpledb_get($parameters, $key, $secret, $endpoint, $light = false) {
	global $cloud_simpledb_multi;
	
	
	// Build out the variables
	$domain = "https://$endpoint/";
	$parameters['AWSAccessKeyId'] = $key;
	$parameters['Timestamp'] = date('c');
	$parameters['Version'] = '2007-11-07';
	$parameters['SignatureMethod'] = 'HmacSHA256';
	$parameters['SignatureVersion'] = 2;
	
	
	// Write the signature
	$signature = "GET\n";
	$signature .= $endpoint . "\n";
	$signature .= "/\n";
	
	$sigparams = $parameters;
	
	ksort($sigparams);
	
	$first = true;
	foreach($sigparams as $key=>$param) {
		$signature .= (!$first ? '&' : '') . rawurlencode($key) . '=' . rawurlencode($param);
		if($first)
			$first = false;
	}
	
	//echo $signature;
	
	$signature = hash_hmac('sha256', $signature, $secret, true);
	$signature = base64_encode($signature);
	
	// Build out the query url
	$parameters['Signature'] = $signature;
	
	$url = $domain . '?';
	$first = true;
	foreach($parameters as $key=>$param) {
		$url .= (!$first ? '&' : '') . rawurlencode($key) . '=' . rawurlencode($param);
		$first = false;
	}
	
	// Make the final request
	$ch = curl_init(trim($url));
	#echo $url;
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	if(!$light) {
		$output = curl_exec($ch);
		
		$xml = new SimpleXMLElement($output);
		
		return $xml;
	} else {
		// Run it asynchronously
		if(!isset($cloud_simpledb_multi))
			$cloud_simpledb_multi = curl_multi_init();
		curl_multi_add_handle($cloud_simpledb_multi, $ch);
		curl_multi_exec($cloud_simpledb_multi, $active);
	}
	
	return true;
}

// Close out any async connections before terminating the script
function cloud_simpledb_close() {
	global $cloud_simpledb_multi;
	if(isset($cloud_simpledb_multi)) {
		do {
			curl_multi_exec($cloud_simpledb_multi, $active);
		} while($active > 0);
	}
}
register_shutdown_function('cloud_simpledb_close');

class simpledb_driver extends cloud_driver {
	
	public function init($credentials) {
		
		if(empty($credentials['key']) || empty($credentials['secret']))
			return false; // TODO : Log this
		
		// Connect
		self::secure('aws_key', $credentials['key']);
		self::secure('aws_secret', $credentials['secret']);
		
		if(!empty($credentials['endpoint']))
			self::secure('aws_endpoint', $credentials['endpoint']);
		else
			self::secure('aws_endpoint', 'sdb.amazonaws.com');
		
	}
	public function close() { return true; }
	
	// Table Functions
	
	public function create_table($name, $columns) {
		
		// We can ignore $columns because columns are dynamically added
		
		$params = array(
			'Action' => 'CreateDomain',
			'DomainName' => $name
		);
		$response = cloud_simpledb_get(
			$params,
			self::secure('aws_key'),
			self::secure('aws_secret'),
			self::secure('aws_endpoint'),
			true // No response
		);
		
	}
	public function get_table_list() {
		$params = array( 'Action' => 'ListDomains' );
		$response = cloud_simpledb_get(
			$params,
			self::secure('aws_key'),
			self::secure('aws_secret'),
			self::secure('aws_endpoint')
		);
		
		if($response->ListDomainsResult) {
			$tab_out = array();
			foreach($response->ListDomainsResult->DomainName as $domain) {
				$tab_out[] = (string)$domain;
			}
			return $tab_out;
		} else
			return cloud_logging::error("Could not fetch the domain list from Amazon.");
		
	}
	public function get_table($name) {
		// TODO : Check for existance.
		return new simpledb_driver_table(
			array(
				'key' => self::secure('aws_key'),
				'secret' => self::secure('aws_secret'),
				'endpoint' => self::secure('aws_endpoint')
			),
			$this,
			$name
		);
	}
	
	
	public function escapeBool($data) {return $data ? 1 : 0;}
	public function escapeString($data, $no_quotes = false) {
		if(strlen($data) > 1024)
			$data = substr($data, 0, 1024);
		if(!$no_quotes)
			$data = "'" . str_replace("'", "''", $data) . "'";
		return $data;
	}
	public function escapeInteger($data, $no_quotes = false) {
		$data = (int)$data;
		$data = str_pad($data, SIMPLEDB_INT_LENGTH, '0', STR_PAD_LEFT);
		return $data;
	}
	public function escapeFloat($data, $no_quotes = false) {
		$rounded = round($data);
		$length = strlen((string)$rounded);
		$length = $length + 6 - ($length % 6);
		$output = str_pad($round, $length, '0', STR_PAD_LEFT);
		if($data != $rounded) {
			$output .= '.';
			$decimal = substr((string)($data - $rounded), 2);
			$decimallength = strlen($decimal);
			$decimallength = $decimallength + 3 - ($decimallength % 3);
			$output .= str_pad($decimal, $decimallength, '0', STR_PAD_RIGHT);
		}
		return $output;
	}
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
				$delimiter = ' and ';
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
					$build = $value;
					break;
				case 2:
					switch($orig_type) {
						case 'integer':
						case 'string':
						case 'double':
						case 'float':
						case 'boolean':
							if(is_string($key))
								$build = "{$this->prepareSimpleToken($key)} = $value";
							else
								$build = 'true';
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
		if(is_string($token))
			$tokentext = $token;
		elseif($token instanceof simpleToken)
			$tokentext = $token->getToken();
		
		if(strlen($tokentext) > 1024)
			$tokentext = substr($tokentext, 0, 1024);
		
		if($tokentext == '_primary_key')
			return 'itemName()';
		
		$tokentext = str_replace("\n", '', $tokentext);
		$tokentext = str_replace("\r", '', $tokentext);
		$tokentext = str_replace("\t", '', $tokentext);
		if(!$no_quotes)
			$tokentext = '`' . str_replace('`', '``', $tokentext) . '`';
		return $tokentext;
	}
	public function prepareCombinator($combinator) {
		$logic = strtolower($combinator->getLogic());
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
				case 'not':
					foreach($terms as $term)
						$build[] = 'not ' . $this->escape($term);
					return implode(' and ', $build);
				case 'xor':
					// TODO : Test this!
					$first = cloud::_or( $terms );
					$last = cloud::_not(cloud::_and( $terms ));
					return $this->prepareCombinator(cloud::_and($first, $last));
					
				case 'or':
				case 'and':
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
		$operation = strtolower($comparison->getOperation());
		$output = "{$this->prepareSimpleToken($comparison->getObject1())} {$operation}";
		if(($ob2 = $comparison->getObject2()) != null)
			$output .= " {$this->prepareSimpleToken($ob2)}";
		return $output;
	}
	public function prepareListOrder($listorder) {
		return $this->prepareSimpleToken($listorder->getVariable()) . (($listorder->getOrder() != '') ? ( ' ' . strtolower($listorder->getOrder()) ) : '');
	}
	
}


class simpledb_driver_table extends cloud_base implements cloud_driver_table {
	
	private $transaction = 0;
	private $transaction_data = array();
	
	public function __construct($connection, $driver, $name) {
		
		self::secure('aws_key', $connection['key']);
		self::secure('aws_secret', $connection['secret']);
		self::secure('aws_endpoint', $connection['endpoint']);
		
		self::secure('driver', $driver);
		self::secure('name', $name);
	}
	
	public function __destruct() {
		while($this->transaction > 0)
			$this->flush_write_transaction();
	}
	
	public function destroy() {
		$name = self::secure('name');
		
		$params = array(
			'Action' => 'DeleteDomain',
			'DomainName' => $name
		);
		$response = cloud_simpledb_get(
			$params,
			self::secure('aws_key'),
			self::secure('aws_secret'),
			self::secure('aws_endpoint'),
			true // No response
		);
		
		self::secure('aws_key', false, true);
		self::secure('aws_secret', false, true);
		self::secure('driver', false, true);
		self::secure('name', false, true);
		
	}
	
	public function get_driver() {return self::secure('driver');}
	
	public function get_columns() {
		
		$columns = self::secure('column_cache');
		if(!empty($columns))
			return $columns;
		else
			$columns = array();
		
		$driver = self::secure('driver');
		$name = self::secure('name');
		
		$params = array(
			'Action' => 'Select',
			'DomainName' => $name,
			'SelectExpression' => "select * from {$driver->prepareSimpleToken($name)} limit 100"
		);
		$response = cloud_simpledb_get(
			$params,
			self::secure('aws_key'),
			self::secure('aws_secret'),
			self::secure('aws_endpoint')
		);
		
		if($response->SelectResult) {
			foreach($response->SelectResult->Item as $item) {
				foreach($item->Attribute as $attr) {
					$attr_name = (string)$attr->Name;
					if(!isset($columns[$attr_name]))
						$columns[$attr_name] = new cloud_column(
							$attr_name,
							'text',
							1024
						);
				}
			}
			self::secure('column_cache', $columns);
			return $columns;
		} else
			return cloud_logging::error("Could not fetch a list of sample objects from Amazon on which to base a column listing.");
		
	}
	public function get_primary_column() {
		return new cloud_column(
			'_primary_key',
			'text',
			1024,
			'PRI'
		);
	}
	public function create_column($position, $column) { return true; }
	public function delete_column($name) { return true; } // TODO : Virtualize this out
	
	public function get_length() {
		$driver = self::secure('driver');
		$name = self::secure('name');
		
		$expression = "select count(*) from {$driver->prepareSimpleToken($name)} limit 1000000000";
		
		return $this->do_count($expression);
		
	}
	
	private function do_count($expression) {
		$driver = self::secure('driver');
		$name = self::secure('name');
		
		$total = 0;
		$continue = true;
		$token = '';
		while($continue) {
			$continue = false;
			
			$params = array(
				'Action' => 'Select',
				'DomainName' => $name,
				'SelectExpression' => $expression
			);
			if(!empty($token))
				$params['NextToken'] = $token;
			
			$result = cloud_simpledb_get(
				$params,
				self::secure('aws_key'),
				self::secure('aws_secret'),
				self::secure('aws_endpoint')
			);
			if($result->SelectResult){
				$total += (int)($result->SelectResult->Item->Attribute->Value);
				if($result->SelectResult->NextToken) {
					$token = (string) ($result->SelectResult->NextToken);
					$continue = true;
				}
			} else {
				// TODO : Test if there's an error
				return $total;
			}
		}
		
		return $total;
	}
	
	
	public function insert_row($id, $values) {
		$driver = self::secure('driver');
		$name = self::secure('name');
		
		// Keep the original ID around in case the user wants to change it
		$orig_id = $id;
		
		// Are we write buffering?
		if($this->transaction > 0)
			$transaction = true;
		
		// Write buffer support
		if($transaction) {
			// Use a version if it's already in existance
			if(isset($this->transaction_data[$id]))
				$params = $this->transaction_data[$id];
			else
				$params = array();
		} else {
			// URL parameters
			$params = array(
				'Action' => 'PutAttributes',
				'DomainName' => $name,
				'ItemName' => $driver->prepareSimpleToken($id, true)
			);
		}
		
		
		$vcount = 0;
		foreach($values as $k=>$v) {
			
			// If user passes a primary key, we should still honor it.
			if($k == '_primary_key') {
				// Ignore if there's no change
				if($v == $id)
					continue;
				
				if($transaction)
					$id = $v; // We're just updating the end result.
					// TODO : Work in a seperate version for Update/Upsert so this is less generic
				else
					$params['ItemName'] = $driver->prepareSimpleToken($v, true);
			}
			
			if($transaction)
				$params[$k] = $v;
			else {
				$params["Attribute.$vcount.Name"] = $driver->escape($k, true);
				$params["Attribute.$vcount.Value"] = $driver->escape($v, true);
				$params["Attribute.$vcount.Replace"] = "true";
			}
			$vcount++;
		}
		
		// Save the data
		if($transaction) {
			if($id == $orig_id || !isset($this->transaction_data[$id]))
				$this->transaction_data[$id] = $params;
			else {
				$existing_transaction =& $this->transaction_data[$id];
				foreach($params as $k=>$v)
					$existing_transaction[$k] = $v;
			}
		} else
			cloud_simpledb_get(
				$params,
				self::secure('aws_key'),
				self::secure('aws_secret'),
				self::secure('aws_endpoint'),
				true // No response
			);
		
	}
	public function upsert_row($id, $values) {
		// SimpleDB automatically upserts
		return $this->insert_row($id, $values);
	}
	public function update_row($id, $values) {
		// SimpleDB updates are also inserts
		return $this->insert_row($id, $values);
	}
	
	public function update($conditions = false, $values = '', $limit = -1, $order = '') {
		
		if(!$conditions)
			return true;
		
		// You can't set the primary key
		foreach($values as $k=>$v)
			if($k == '_primary_key')
				return cloud_logging::error("Setting the primary key on a SimpleDB through the Update command is not currently supported.");
		
		$driver = self::secure('driver');
		$name = self::secure('name');
		
		// Build out the expression to detect items to update
		// Can't have more than a billion attributes per domain, might as well not even request that many items.
		$expression = "select itemName() from {$driver->prepareSimpleToken($name)} where {$driver->escapeArray($conditions, 2)}";
		if(!empty($order))
			$expression .= ' order by ' . $driver->escape($order);
		if($limit > -1)
			$expression .= ' limit ' . $limit;
		
		// Enable output buffering
		$this->start_write_transaction();
		
		$continue = true;
		$token = '';
		while($continue) {
			$continue = false;
			$params = array(
				'Action' => 'Select',
				'DomainName' => $name,
				'SelectExpression' => $expression
			);
			if(!empty($token))
				$params['NextToken'] = $token;
			
			$result = cloud_simpledb_get(
				$params,
				self::secure('aws_key'),
				self::secure('aws_secret'),
				self::secure('aws_endpoint')
			);
			
			if($result->SelectResult) {
				
				foreach($result->SelectResult->Item as $item) {
					$item_name = (string)($item->Name);
					
					$this->update_row(
						$item_name,
						$values
					);
					
				}
				
				if($result->SelectResult->NextToken) {
					$token = (string)($result->SelectResult->NextToken);
					$continue = true;
				}
			}
			
		}
		
		$this->flush_write_transaction();
		
	}
	
	public function delete_row($id) {
		$driver = self::secure('driver');
		$name = self::secure('name');
		
		$params = array(
			'Action' => 'DeleteAttributes',
			'DomainName' => $name,
			'ItenName' => $driver->prepareSimpleToken($id, true)
		);
		
		$result = cloud_simpledb_get(
			$params,
			self::secure('aws_key'),
			self::secure('aws_secret'),
			self::secure('aws_endpoint'),
			true
		);
	}
	public function delete($conditions = false, $limit = -1, $order = '') {
		
		if(!$conditions)
			return true;
		
		// Ony one can be deleted if the primary key is set
		foreach($conditions as $k=>$v) {
			if($k == '_primary_key' || (is_object($v) && get_class($v) == 'comparison' && ($v->getObject1() == '_primary_key' || $v->getObject2() == '_primary_key'))) {
				$limit = 1;
				break;
			}
		}
		
		$driver = self::secure('driver');
		$name = self::secure('name');
		
		// Build out the expression to detect items to update
		$expression = "select itemName() from {$driver->prepareSimpleToken($name)} WHERE {$this->escapeArray($conditions, 2)}";
		if(!empty($order))
			$expression .= ' order by ' . $this->escape($order);
		if($limit > -1)
			$expression .= ' limit ' . $limit;
		
		$continue = true;
		$token = '';
		while($continue) {
			$continue = false;
			$params = array(
				'Action' => 'Select',
				'DomainName' => $name,
				'SelectExpression' => $expression
			);
			if(!empty($token))
				$params['NextToken'] = $token;
			
			$result = cloud_simpledb_get(
				$params,
				self::secure('aws_key'),
				self::secure('aws_secret'),
				self::secure('aws_endpoint')
			);
			
			if($result->SelectResult) {
				
				foreach($result->SelectResult->Item as $item) {
					$item_name = (string)($item->Name);
					
					$this->delete_row($item_name);
					
				}
				
				if($result->SelectResult->NextToken) {
					$token = (string)($result->SelectResult->NextToken);
					$continue = true;
				}
			}
			
		}
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
		
		if(!is_array($params))
			$params = array();
		$columns = isset($params['columns']) ? $params['columns'] : '*';
		$limit = isset($params['limit']) ? intval($params['limit']) : -1;
		$offset = isset($params['offset']) ? $params['offset'] : 0;
		$order = isset($params['order']) ? $params['order'] : '';
		$arrid = isset($params['arrayid']) ? $params['arrayid'] : '_primary_key';
		
		$is_array_col = is_array($columns);
		
		if($return == 8 && $is_array_col) {
			// This is a FETCH_SINGLE command. What are you doing, silly dev?
			$columns = $columns[0];
			$is_array_col = false;
			cloud_logging::warning("FETCH_SINGLE command passed mutiple parameters.");
		}
		
		// Limit of 25 columns per query :(
		if($is_array_col && count($columns) > 25 && !($return = 6 || $return == 7)) {
			// TODO : Virtualize this away
			$attempt = cloud_logging::unsafe_query(
				'SimpleDB Fetch',
				'Too many columns selected (25 maximum)',
				array(
					'Missing information',
					'Abstraction automatically adjusts to `*`'
				)
			);
			if($attempt) {
				$columns = '*';
			} else
				return false;
		}
		
		if($return == 6 || $return == 7) { // Unloaded tokens don't need any values.
			$columns = 'itemName()';
			$is_array_col = false;
		} elseif($return != 1 && $columns != "*") { // Just exclude counts altogether
			if($is_array_col) {
				foreach($columns as $key => &$value) {
					if(	$value === '_primary_key' ||
						(	($value instanceof simpleToken) &&
							$value->getToken() == '_primary_key' ) ) {
						unset($columns[$key]);
						continue;
					}
					if(is_string($value))
						$value = cloud::_st($value);
				}
			}
			$columns = $driver->escapeArray($columns, 1);
		}
		
		// Used as a limiter. Must be respected by result objects, as well
		$mandatory_next_token = '';
		
		// Ok, dumb rule: SimpleDB can only sort by fields that we're sorting against.
		// So now we need to do some sort of nonsense test on all columns that aren't
		// already in the condition.
		if(!empty($order)) {
			// We're going to set the key and the value to the column name so we don't
			// waste CPU cycles with in_array()
			$ordervars = array();
			
			if(is_array($order)) {
				foreach($order as $sort) {
					if($sort instanceof listOrder) {
						$o1 = $sort->getVariable();
						if($o1 == '_primary_key') continue;
						if(!isset($ordervars[$o1]))
							$ordervars[$o1] = $o1;
					} elseif(is_string($sort)) {
						if($sort == '_primary_key') continue;
						if(!isset($ordervars[$sort]))
							$ordervars[$sort] = $sort;
					}
				}
			} elseif($order instanceof listOrder) {
				$o1 = $order->getVariable();
				if($o1 != '_primary_key' && !isset($ordervars[$o1]))
					$ordervars[$o1] = $o1;
			}
			
			foreach($ordervars as $ov) {
				
				$found = false;
				foreach($conditions as $key=>$condition) {
					
					if(is_string($key) && $key === $ov) {
						$found = true;
						break;
					} elseif($condition instanceof comparison && ($condition->getObject1() == $ov || $condition->getObject2() == $ov)) {
						$found = true;
						break;
					}
					
				}
				if(!$found) {
					$conditions[] = new comparison($ov, 'is not null', null);
				}
				
			}
			
		}
		
		// We segment off the suffix because we can use them a second time if a sub-query
		// would optimize our efficiency. For instance, we can use it to perform an offset
		// by count(*)-ing the first "offset" values (limited) and then using the NextToken
		// returned to grab the remaining results.
		$suffix = '';
		if(!empty($conditions))
			$suffix = " where {$driver->escapeArray($conditions, 2)}";
		if(!empty($order))
			$suffix .= " order by {$driver->escapeArray($order, 1)}";
		
		if($return == 3 || $return == 5 || $return == 7 || $return == 8)
			$limit = 1;
		
		/*
		To perform an offset, we perform a count on the first $offset values. If
		the count is less than $offset, we return null (offset is greater than
		result set). Otherwise, we get a NextToken. Per SimpleDB docs, this can be
		used to return the next max(n,$limit) values.
		*/
		if($offset > 0 && $return != 1) {
			$offset = (int)$offset;
			
			$subquery = "select count(*) from {$driver->prepareSimpleToken($name)}$suffix limit $offset";
			
			while(true) {
				$subparams = array(
					'Action' => 'Select',
					'DomainName' => $name,
					'SelectExpression' => $subquery
				);
				if(!empty($mandatory_next_token))
					$subparams['NextToken'] = $mandatory_next_token;
				
				$subresult = cloud_simpledb_get(
					$subparams,
					self::secure('aws_key'),
					self::secure('aws_secret'),
					self::secure('aws_endpoint')
				);
				
				if($subresult->SelectResult) {
					$subquery_total = (int)($subresult->SelectResult->Item->Attribute->Value);
					if($subquery_total < $offset) { // Offset > Count?
						if($subresult->SelectResult->NextToken) { // Maybe. Grab the next set
							$mandatory_next_token = (string) ($subresult->SelectResult->NextToken);
							continue;
						} else
							return cloud_logging::warning("SimpleDB fetch offset greater than result count.");
					}
					if($subresult->SelectResult->NextToken) {
						$mandatory_next_token = (string) ($subresult->SelectResult->NextToken);
						break;
					} else {
						// Exactly the right number to not return a result.
						return false;
					}
				} else {
					// Not really sure what would cause this.
					// TODO : Figure out why this would happen. Log?
					return false;
				}
				
				break;
			}
			
		}
		if($limit > -1) {
			$limit = (int)$limit;
			$suffix .= " limit $limit";
		}
		
		
		$params = array(
			'Action' => 'Select',
			'DomainName' => $name
		);
		
		if(!empty($mandatory_next_token))
			$params['NextToken'] = $mandatory_next_token;
		
		
		if($return == 1) { // Just a simple count
			$query = "select count(*) from {$driver->prepareSimpleToken($name)}$suffix";
			return $this->do_count($query);
		}
		
		$query = "select $columns from {$driver->prepareSimpleToken($name)}$suffix";
		$params["SelectExpression"] = $query;
		
		if(	$return == FETCH_RESULT ||
			$return == FETCH_SINGLE_ARRAY ||
			$return == FETCH_SINGLE_TOKEN ||
			$return == FETCH_SINGLE_UNLOADED_TOKEN ||
			$return == FETCH_SINGLE) {
			$result = cloud_simpledb_get(
				$params,
				self::secure('aws_key'),
				self::secure('aws_secret'),
				self::secure('aws_endpoint')
			);
			if(!$result->SelectResult)
				return false;
			
		}
		
		$output = false;
		
		switch($return) {
			case FETCH_RESULT: // Result object
				return false;
				// TODO: Implement this output type.
				/*
				return new simpledb_return( array(
					'driver' => $driver,
					'table' => $this,
					'expression' => $query,
					'query' => $result,
					'mandatory_token' => $mandatory_next_token
				));
				*/
			//case FETCH_COUNT: // Row count is already handled above
			case FETCH_ARRAY: // Array
				$output = array();
				$rows = $this->get_rows($query, $limit, empty($mandatory_next_token)?null:$mandatory_next_token);
				$rows_rows = $rows['rows'];
				foreach($rows_rows as $rname=>$row)
					$output[$row[$arrid]] = $row;
				break;
			case FETCH_SINGLE_ARRAY: // Single Array
				$row = $result->SelectResult->Item;
				return $this->get_array($row, (string)$row->Name);
			case FETCH_TOKENS: // Tokens
			case FETCH_UNLOADED_TOKENS: // Unloaded tokens
				$output = array();
				$rows = $this->get_rows($query, $limit, empty($mandatory_next_token)?null:$mandatory_next_token);
				$rows_rows = $rows['rows'];
				foreach($rows_rows as $rname=>$row) {
					$output[$row[$arrid]] = new cloud_token(
						$driver,
						$this,
						$row[$rname],
						$return == 4 ? $row : ''
					);
				}
				break;
			case FETCH_SINGLE_TOKEN: // Single Token
			case FETCH_SINGLE_UNLOADED_TOKEN: // Single Unloaded Token
				$row = $result->SelectResult->Item;
				$row_name = (string)$row->Name;
				return new cloud_token(
					$driver,
					$this,
					$row_name,
					$this->get_array($row, $row_name)
				);
				break;
			case FETCH_SINGLE: // Single Value
				$row = $result->SelectResult->Item;
				if(!$row->Attribute)
					return (string)$row->Name;
				else
					return (string)$row->Attribute->Value;
		}
		
		return $output;
		
	}
	
	private function get_rows($expression, $recurse = 0, $start_token = '') {
		$name = self::secure('name');
		
		$token = '';
		$rows = array();
		
		$params = array(
			'Action' => 'Select',
			'DomainName' => $name,
			'SelectExpression' => $expression
		);
		if(!empty($start_token))
			$params['NextToken'] = $start_token;
		
		$result = cloud_simpledb_get(
			$params,
			self::secure('aws_key'),
			self::secure('aws_secret'),
			self::secure('aws_endpoint')
		);
		
		if($result->SelectResult) {
			
			foreach($result->SelectResult->Item as $item) {
				$item_name = (string)($item->Name);
				$rows[$item_name] = $this->get_array($item, $item_name);
				
			}
			
			if($result->SelectResult->NextToken) {
				$count = count($rows);
				if($recurse < $count) {
					$subrows = $this->get_rows($expression, $recurse - $count, (string)($result->SelectResult->NextToken));
					$rows = array_merge($rows, $subrows['rows']);
				} else {
					$token = (string)($result->SelectResult->NextToken);
				}
			}
		}
		
		return array(
			'rows' => $rows,
			'token' => $token
		);
		
	}
	
	private function get_array($xml, $id) {
		$output = array();
		foreach($xml->Attribute as $attr) {
			$name = (string)$attr->Name;
			$value = (string)$attr->Value;
			$output[$name] = $value;
		}
		$output['_primary_key'] = $id;
		return $output;
	}
	
	public function fetch_exists($conditions = '') {
		
		$driver = self::secure('driver');
		$query = "select count(*) from {$driver->prepareSimpleToken($name)} where {$driver->escapeArray($conditions, 2)} limit 1";
		return $this->do_count($query) > 0;
	}
	
	public function start_write_transaction() {
		$this->transaction++;
	}
	public function flush_write_transaction() {
		
		$this->transaction--;
		if($this->transaction > 0)
			return false;
		
		$this->do_write_flush();
		
		$this->transaction_data = array();
		
	}
	
	private function do_write_flush() {
		$driver = self::secure('driver');
		$name = self::secure('name');
		
		$params = array(
			'Action' => 'BatchPutAttributes',
			'DomainName' => $name
		);
		
		$transdata = $this->transaction_data;
		
		$icount = 0;
		$vcount_total = 0; // 256 pair limit
		$data_total = 1024; // 1MB data limit (assumed 1kb overhead)
		
		$dead = false; // Kills loop if limit is reached
		
		foreach($transdata as $iname=>&$iitem) { // We'll be updating iitem, so we reference it
			$vcount = 0;
			$params["Item.$icount.ItemName"] = $driver->prepareSimpleToken($iname, true);
			foreach($iitem as $k=>$v) {
				// If user passes a primary key, we should still honor it.
				if($k == '_primary_key') {
					$params["Item.$icount.ItemName"] = $driver->prepareSimpleToken($v, true);
					unset($iitem[$k]);
					continue;
				}
				$params["Item.$icount.Attribute.$vcount.Name"] = $driver->escape($k, true);
				if(is_array($v)) {
					$params["Item.$icount.Attribute.$vcount.Value"] = $driver->escape($v['value'], true);
					if($v['replace']) {
						$params["Item.$icount.Attribute.$vcount.Replace"] = "true";
						$data_total += 35;
					}
					
					$data_total += strlen($v['value']) + 36;
				} else {
					$params["Item.$icount.Attribute.$vcount.Value"] = $driver->escape($v, true);
					$params["Item.$icount.Attribute.$vcount.Replace"] = "true";
					
					$data_total += strlen($v) + 70;
				}
				
				unset($iitem[$k]);
				$vcount++;
				$vcount_total++;
				
				// Attribute pair limit of 256 and data limit of 1MB
				// Note that we cut the limit approx one attribute short of the max
				if($vcount_total == 256 || $data_total > 997000) {
					$dead = true;
					$this->do_write_flush();
					break;
				}
				
			}
			
			if($dead) {
				// Reached the limit
				break;
			}
			
			unset($transdata[$iname]);
			
			$icount++;
			if($icount == 25) { // 25 item limit
				$this->do_write_flush();
			}
		}
		cloud_simpledb_get(
			$params,
			self::secure('aws_key'),
			self::secure('aws_secret'),
			self::secure('aws_endpoint'),
			true // No response
		);
	}
	
}
/*
class simpledb_return extends cloud_return {
	
	// State Functions
	public function init($construct) {
		
		$query = $contruct['query'];
		
		self::secure('driver', $contruct['driver']);
		self::secure('table', $contruct['table']);
		self::secure('expression', $query);
		self::secure('query', $contruct['query']);
		self::secure('mandatory_token', $contruct['mandatory_token']);
		
		$this->length = -1;
		
		if(!($query->SelectResult->NextToken)) {
			$this->length = $query->SelectResult->
		}
		
	}
	public function close() { return; }
	
	// Retrieval Functions
	public function next_array() {
		$query = self::secure('query');
		$this->pointer++;
		return $query->fetch_array(MYSQLI_ASSOC);
	}
	public function next_token() {
		$query = self::secure('query');
		$driver = self::secure('driver');
		$table = self::secure('table');
		
		$this->pointer++;
		$row_data = $query->fetch_array(MYSQLI_ASSOC);
		
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
		$data = $query->fetch_array(MYSQLI_ASSOC);
		$query->data_seek($this->pointer);
		return $data;
	}
	public function peek_token() {
		$query = self::secure('query');
		$driver = self::secure('driver');
		$table = self::secure('table');
		
		$row_data = $query->fetch_array(MYSQLI_ASSOC);
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
		if($new_pointer == $this->length)
			return false;
		if($new_pointer < 0 || $new_pointer >= $this->length)
			return cloud_logging::warning("Skipping to a position out of bounds.");
		
		$this->pointer = $new_pointer;
	}
	public function seek($index) {
		if($index < 0 || $index >= $this->length)
			return cloud_logging::warning("Seeking to a position out of bounds.");
		
		$this->pointer = $index;
		
		$query = self::secue('query');
		$query->data_seek($index);
		
	}
	public function slide($until) {
		$query = self::secure('query');
		do {
			$matches = true;
			$row = $query->fetch_array(MYSQLI_ASSOC);
			
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
*/
