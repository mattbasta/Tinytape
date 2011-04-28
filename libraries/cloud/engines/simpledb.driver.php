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
define('SIMPLEDB_ROWID', "row_id");

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
	private $key;
	private $secret;
	private $endpoint = 'sdb.amazonaws.com';
	
	public function init($credentials) {
		
		if(empty($credentials['key']) || empty($credentials['secret']))
			return false; // TODO : Log this
		
		// Connect
		$this->key = $credentials["key"];
		$this->secret = $credentials["secret"];
		
		if(!empty($credentials['endpoint']))
			$this->endpoint = $credentials["endpoint"];
	}
	public function close() { return true; }
	
	// Table Functions
	public function create_table($name, $columns) {
		// TODO : Perhaps this function should be removed?
		
		// We can ignore $columns because columns are dynamically added.
		$params = array(
			'Action' => 'CreateDomain',
			'DomainName' => $name
		);
		$response = cloud_simpledb_get(
			$params,
			$this->key,
			$this->secret,
			$this->endpoint,
			true // No response
		);
	}
	
	public function get_table_list() {
		$params = array( 'Action' => 'ListDomains' );
		$response = cloud_simpledb_get(
			$params,
			$this->key,
			$this->secret,
			$this->endpoint
		);
		
		if($response->ListDomainsResult) {
			$tab_out = array();
			foreach($response->ListDomainsResult->DomainName as $domain)
				$tab_out[] = (string)$domain;
			
			return $tab_out;
		} else
			throw new Exception("Could not fetch the domain list from Amazon.");
	}
	
	public function get_table($name) {
		return new simpledb_driver_table(
			array(
				'key' => $this->key,
				'secret' => $this->secret,
				'endpoint' => $this->endpoint
			),
			$this,
			$name
		);
	}
	
	public function escape_attribute($data) {
		// Duplicate code makes me sad :(
		switch(true) {
			case is_integer($data):
				return $this->escapeInteger($data);
			case is_float($data):
				return $this->escapeFloat($data);
			case is_string($data):
				return $this->escapeStringAttribute($data);
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
					case $data instanceof cloud_unescaped:
						return $data->getValue();
				}
		}
	}
	
	public function escapeBool($data) {return $data ? 1 : 0;}
	public function escapeString($data) {
		$data = $this->escapeStringAttribute($data);
		return "'$data'";
	}
	public function escapeStringAttribute($data) {
		if(strlen($data) > 1024)
			$data = substr($data, 0, 1024);
		$data = str_replace("'", "''", $data);
		return $data;
	}
	public function escapeInteger($data) {
		$data = (int)$data;
		$data = str_pad($data, SIMPLEDB_INT_LENGTH, '0', STR_PAD_LEFT);
		return $data;
	}
	public function escapeFloat($data) {
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
	
	public function escapeList($array, $commas = true, $escape = true) {
		if(!is_array($array))
			return $escape ? $this->escape($array) : $array;
		
		$final = array();
		for($array as $key=>$item) {
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
		for($array as $key=>$item) {
			if(is_object($item)) {
				$final[] = $this->escape($item);
				continue;
			}
			
			if(is_string($key))
				$final[] = $this->prepareSimpleToken($key) . " = " . $this->escape($item);
			elseif(is_array($item))
				$final[] = $this->escapeConditions($item);
		}
		return implode(" and ", $final);
	}
	
	public function prepareSimpleToken($token) {
		if($tokentext == SIMPLEDB_ROWID)
			return 'itemName()';
		
		$tokentext = '`' . $this->prepareSimpleTokenAttribute($token) . '`';
		return $tokentext;
	}
	
	public function prepareSimpleTokenAttribute($token) {
		if(is_string($token))
			$tokentext = $token;
		elseif($token instanceof simpleToken)
			$tokentext = $token->getToken();
		
		if(strlen($tokentext) > 1024)
			$tokentext = substr($tokentext, 0, 1024);
		
		if($tokentext == SIMPLEDB_ROWID)
			return 'itemName()';
		
		$tokentext = str_replace("\n", '', $tokentext);
		$tokentext = str_replace("\r", '', $tokentext);
		$tokentext = str_replace("\t", '', $tokentext);
		$tokentext = str_replace('`', '``', $tokentext);
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
					if($caller == 'prepareCombinator' || $caller == 'escapeConditions')
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
	
	private $key;
	private $secret;
	private $endpoint;
	
	private $driver;
	private $name;
	
	private $transaction = 0;
	private $transaction_data = array();
	
	private $column_cache = array();
	
	public function __construct($connection, $driver, $name) {
		$this->key = $connection["key"];
		$this->secret = $connection["secret"];
		$this->endpoint = $connection["endpoint"];
		
		$this->driver = $driver;
		$this->name = $name;
	}
	
	public function __destruct() {
		while($this->transaction > 0)
			$this->flush_write_transaction();
	}
	
	public function drop() {
		$params = array(
			'Action' => 'DeleteDomain',
			'DomainName' => $this->name
		);
		$response = cloud_simpledb_get(
			$params,
			$this->key,
			$this->secret,
			$this->endpoint,
			true // No response
		);
		
		$this->key = null;
		$this->secret = null;
		$this->endpoint = null;
		
		$this->driver = null;
		$this->name = null;
	}
	
	public function get_driver() { return $this->driver; }
	
	public function get_columns() {
		
		if(!empty($this->column_cache))
			return $this->column_cache;
		$columns = array();
		
		$params = array(
			'Action' => 'Select',
			'DomainName' => $this->name,
			'SelectExpression' => "select * from {$this->driver->prepareSimpleToken($this->name)} limit 100"
		);
		$response = cloud_simpledb_get(
			$params,
			$this->key,
			$this->secret,
			$this->endpoint
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
			
			$this->column_cache = $columns;
			
			return $columns;
		} else
			throw new Exception("Could not fetch a list of sample objects from Amazon on which to base a column listing.");
	}
	
	public function get_primary_column() {
		return new cloud_column(
			SIMPLEDB_ROWID,
			'text',
			1024,
			'PRI'
		);
	}
	
	public function get_length() {
		$expression = "select count(*) from {$this->driver->prepareSimpleToken($this->name)} limit 1000000000";
		
		return $this->do_count($expression);
	}
	
	private function do_count($expression) {
		$total = 0;
		$continue = true;
		$token = '';
		
		while($continue) {
			$continue = false;
			
			$params = array(
				'Action' => 'Select',
				'DomainName' => $this->name,
				'SelectExpression' => $expression
			);
			if(!empty($token))
				$params['NextToken'] = $token;
			
			$result = cloud_simpledb_get(
				$params,
				$this->key,
				$this->secret,
				$this->endpoint
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
	
	public function insert_row($values) {
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
				'DomainName' => $this->name,
				'ItemName' => $this->driver->prepareSimpleToken($id, true)
			);
		}
		
		$vcount = 0;
		foreach($values as $k=>$v) {
			
			// If user passes a primary key, we should still honor it.
			if($k == 'id') {
				// Ignore if there's no change
				if($v == $id)
					continue;
				
				if($transaction)
					$id = $v; // We're just updating the end result.
					// TODO : Work in a seperate version for Update/Upsert so this is less generic
				else
					$params['ItemName'] = $this->driver->prepareSimpleTokenAttribute($v, true);
			}
			
			if($transaction)
				$params[$k] = $v;
			else {
				$params["Attribute.$vcount.Name"] = $this->driver->escape_attribute($k);
				$params["Attribute.$vcount.Value"] = $this->driver->escape_attribute($v);
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
				$this->key,
				$this->secret,
				$this->endpoint,
				true // No response
			);
		
	}
	
	public function update($conditions, $values, $limit = -1, $order = '') {
		
		if(!$conditions)
			return true;
		
		// You can't set the primary key
		foreach($values as $k=>$v)
			if($k == SIMPLEDB_ROWID)
				throw new Exception("Setting the primary key on a SimpleDB through the Update command is not currently supported.");
		
		// Build out the expression to detect items to update
		// Can't have more than a billion attributes per domain, might as well not even request that many items.
		$expression = "select itemName() from {$this->driver->prepareSimpleToken($this->name)} where {$this->driver->escapeConditions($conditions)}";
		if(!empty($order))
			$expression .= ' order by ' . $this->driver->escape($order);
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
				'DomainName' => $this->name,
				'SelectExpression' => $expression
			);
			if(!empty($token))
				$params['NextToken'] = $token;
			
			$result = cloud_simpledb_get(
				$params,
				$this->key,
				$this->secret,
				$this->endpoint
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
		
		$params = array(
			'Action' => 'DeleteAttributes',
			'DomainName' => $this->name,
			'ItenName' => $this->driver->prepareSimpleToken($id, true)
		);
		
		$result = cloud_simpledb_get(
			$params,
			$this->key,
			$this->secret,
			$this->endpoint,
			true
		);
	}
	
	public function delete($conditions, $limit = -1, $order = '') {
		
		if(!$conditions)
			return true;
		
		// Ony one can be deleted if the primary key is set
		foreach($conditions as $k=>$v) {
			if($k == SIMPLEDB_ROWID || (is_object($v) && get_class($v) == 'comparison' && ($v->getObject1() == SIMPLEDB_ROWID || $v->getObject2() == SIMPLEDB_ROWID))) {
				$limit = 1;
				break;
			}
		}
		
		// Build out the expression to detect items to update
		$expression = "select itemName() from {$this->driver->prepareSimpleToken($this->name)} WHERE {$this->driver->escapeConditions($conditions)}";
		if(!empty($order))
			$expression .= ' order by ' . $this->driver->escape($order);
		if($limit > -1)
			$expression .= ' limit ' . $limit;
		
		$continue = true;
		$token = '';
		while($continue) {
			$continue = false;
			$params = array(
				'Action' => 'Select',
				'DomainName' => $this->name,
				'SelectExpression' => $expression
			);
			if(!empty($token))
				$params['NextToken'] = $token;
			
			$result = cloud_simpledb_get(
				$params,
				$this->key,
				$this->secret,
				$this->endpoint
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
	
	// The pseudocolumn SIMPLEDB_ROWID should be used to denote the primary key
	/*
	Params
		- Columns
		- Limit
		- Offset
		- Order
		- Array ID (Expects column name)
	*/
	public function fetch($conditions, $return, $params = '') {
		if(!is_array($params))
			$params = array();
		$columns = isset($params['columns']) ? $params['columns'] : '*';
		$limit = isset($params['limit']) ? intval($params['limit']) : -1;
		$offset = isset($params['offset']) ? $params['offset'] : 0;
		$order = isset($params['order']) ? $params['order'] : '';
		$arrid = isset($params['arrayid']) ? $params['arrayid'] : SIMPLEDB_ROWID;
		
		$is_array_col = is_array($columns);
		
		if($return == 8 && $is_array_col)
			throw new Exception("FETCH_SINGLE command passed mutiple parameters.");
		
		// Limit of 25 columns per query :(
		if($is_array_col && count($columns) > 25 && !($return = 6 || $return == 7))
			$columns = '*';
		
		if($return == 6 || $return == 7) { // Unloaded tokens don't need any values.
			$columns = 'itemName()';
			$is_array_col = false;
		} elseif($return != 1 && $columns != "*") { // Just exclude counts altogether
			if($is_array_col) {
				foreach($columns as $key => &$value) {
					if(	$value === SIMPLEDB_ROWID ||
						(	($value instanceof simpleToken) &&
							$value->getToken() == SIMPLEDB_ROWID ) ) {
						unset($columns[$key]);
						continue;
					}
					if(is_string($value))
						$value = cloud::_st($value);
				}
			}
			$columns = $this->driver->escapeList($columns);
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
						if($o1 == SIMPLEDB_ROWID) continue;
						if(!isset($ordervars[$o1]))
							$ordervars[$o1] = $o1;
					} elseif(is_string($sort)) {
						if($sort == SIMPLEDB_ROWID) continue;
						if(!isset($ordervars[$sort]))
							$ordervars[$sort] = $sort;
					}
				}
			} elseif($order instanceof listOrder) {
				$o1 = $order->getVariable();
				if($o1 != SIMPLEDB_ROWID && !isset($ordervars[$o1]))
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
			$suffix = " where {$this->driver->escapeConditions($conditions)}";
		if(!empty($order))
			$suffix .= " order by {$this->driver->escapeList($order)}";
		
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
			
			$subquery = "select count(*) from {$this->driver->prepareSimpleToken($this->name)}$suffix limit $offset";
			
			while(true) {
				$subparams = array(
					'Action' => 'Select',
					'DomainName' => $this->name,
					'SelectExpression' => $subquery
				);
				if(!empty($mandatory_next_token))
					$subparams['NextToken'] = $mandatory_next_token;
				
				$subresult = cloud_simpledb_get(
					$subparams,
					$this->key,
					$this->secret,
					$this->endpoint
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
			'DomainName' => $this->name
		);
		
		if(!empty($mandatory_next_token))
			$params['NextToken'] = $mandatory_next_token;
		
		
		if($return == 1) { // Just a simple count
			$query = "select count(*) from {$this->driver->prepareSimpleToken($this->name)}$suffix";
			return $this->do_count($query);
		}
		
		$query = "select $columns from {$this->driver->prepareSimpleToken($this->name)}$suffix";
		$params["SelectExpression"] = $query;
		
		if(	$return == FETCH_RESULT ||
			$return == FETCH_SINGLE_ARRAY ||
			$return == FETCH_SINGLE_TOKEN ||
			$return == FETCH_SINGLE_UNLOADED_TOKEN ||
			$return == FETCH_SINGLE) {
			$result = cloud_simpledb_get(
				$params,
				$this->key,
				$this->secret,
				$this->endpoint
			);
			if(!$result->SelectResult)
				return false;
		}
		
		$output = false;
		
		switch($return) {
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
						$this,
						SIMPLEDB_ROWID,
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
					$this,
					SIMPLEDB_ROWID,
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
		$token = '';
		$rows = array();
		
		$params = array(
			'Action' => 'Select',
			'DomainName' => $this->name,
			'SelectExpression' => $expression
		);
		if(!empty($start_token))
			$params['NextToken'] = $start_token;
		
		$result = cloud_simpledb_get(
			$params,
			$this->key,
			$this->secret,
			$this->endpoint
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
		$output[SIMPLEDB_ROWID] = $id;
		return $output;
	}
	
	public function fetch_exists($conditions) {
		$query = "select count(*) from {$this->driver->prepareSimpleToken($this->name)} where {$this->driver->escapeConditions($conditions)} limit 1";
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
		$params = array(
			'Action' => 'BatchPutAttributes',
			'DomainName' => $this->name
		);
		
		$transdata = $this->transaction_data;
		
		$icount = 0;
		$vcount_total = 0; // 256 pair limit
		$data_total = 1024; // 1MB data limit (assumed 1kb overhead)
		
		$dead = false; // Kills loop if limit is reached
		
		foreach($transdata as $iname=>&$iitem) { // We'll be updating iitem, so we reference it
			$vcount = 0;
			$params["Item.$icount.ItemName"] = $this->driver->prepareSimpleToken($iname, true);
			foreach($iitem as $k=>$v) {
				// If user passes a primary key, we should still honor it.
				if($k == SIMPLEDB_ROWID) {
					$params["Item.$icount.ItemName"] = $this->driver->prepareSimpleToken($v, true);
					unset($iitem[$k]);
					continue;
				}
				$params["Item.$icount.Attribute.$vcount.Name"] = $this->driver->escape($k, true);
				if(is_array($v)) {
					$params["Item.$icount.Attribute.$vcount.Value"] = $this->driver->escape($v['value'], true);
					if($v['replace']) {
						$params["Item.$icount.Attribute.$vcount.Replace"] = "true";
						$data_total += 35;
					}
					
					$data_total += strlen($v['value']) + 36;
				} else {
					$params["Item.$icount.Attribute.$vcount.Value"] = $this->driver->escape($v, true);
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
			$this->key,
			$this->secret,
			$this->endpoint,
			true // No response
		);
	}
	
}
