<?php

/**
 * Cloud Simple Objects
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

// Simple comparison (two terms)

class comparison {
	
	private $obj1;
	private $obj2;
	private $comparison;
	
	public function __construct($obj1, $comparison, $obj2) {
		
		// Make sure the comparison is a valid comparison operator
		$supported_comparisons = array(
			'='=>true, // May be interpreted as ==
			'>'=>true,
			'<'=>true,
			'>='=>true,
			'<='=>true,
			'!='=>true,
			'LIKE'=>true,
			'NOT LIKE'=>true,
			'STARTS WITH'=>true,
			'IN'=>true,
			'IS NOT NULL'=>true
		);
		
		$comparison2 = strtoupper($comparison);
		if(!isset($supported_comparisons[$comparison2]))
			throw new Exception("Unsupported comparison operator used: " . $comparison);
		
		
		// Store the objects securely
		$this->obj1 = $obj1;
		$this->obj2 = $obj2;
		$this->comparison = $comparison;
	}
	
	public function getObject1() {return $this->obj1;}
	public function getObject2() {return $this->obj2;}
	public function getOperation() {return $this->comparison;}
}


class logicCombinator {

	private $logic;
	private $terms;
	
	public function __construct($terms, $logic) {
		
		if(!is_array($terms) || count($terms) < 1)
			throw new Exception("Empty logic combinator was supplied.");
		
		// Make sure the comparison is a valid comparison operator
		$supported_logic = array(
			'XOR'=>'XOR',
			'AND'=>'AND',
			'OR'=>'OR',
			'NOT'=>'NOT'
		);
		
		$logic = strtoupper($logic);
		if(!isset($supported_logic[$logic]))
			throw new Exception("Unsupported boolean operator supplied: " . $logic);
		
		$this->logic;
		$this->terms;
	}
	
	public function getLogic() {return $this->logic;}
	public function getTerms() {return $this->terms;}
	
}

// Order

class listOrder {
	
	private $variable;
	private $order;
	
	public function __construct($variable, $order) {
		
		$supported_orders = array(
			'DESC',
			'ASC'
		);
		
		$order = strtoupper($order);
		if(!in_array($order, $supported_orders))
			throw new Exception("Unsupported directionality supplied for order: " . $order);
		
		$this->variable = $variable;
		$this->order = $order;
	}
	
	public function getVariable() {return $this->variable;}
	public function getOrder() {return $this->order;}
	
}

// Simple tokens (i.e.: table names, column names, etc.)

class simpleToken {
	
	private $token;
	
	public function __construct($token) {
		$this->token = $token;
	}
	
	public function getToken() {return $this->token;}
	
}

class simpleFunction {
	
	private $name;
	private $values;
	
	public function __construct($name, $values) {
		$this->name = $name;
		$this->values = $values;
	}
	
	public function getName() {return $this->name;}
	public function getValues() {return $this->values;}
	
}


class cloud_unescaped {
	
	private $value;
	
	public function __construct($value) {
		$this->value = (string) $value;
	}
	public function __toString() {return $this->value;}
	public function getValue() {return $this->value;}
	
}
