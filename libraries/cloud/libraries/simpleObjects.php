<?php

/**
 * Cloud Simple Objects
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

// Simple comparison (two terms)

class comparison extends cloud_base {
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
			cloud_logging::unsafe_query(
				'Comparisons',
				"Comparison Operator ($comparison)",
				array(
					'This is not a globally supported db comparison operator.',
					'> Possible disintegration of intended data set',
					'> Possible corruption of data',
					'> Possible incorrect reporting of data'
				)
			);
		
		
		// Store the objects securely
		$this->secure('object_1', $obj1);
		$this->secure('comparison', $comparison);
		$this->secure('object_2', $obj2);
	}
	
	public function getObject1() {return $this->secure('object_1');}
	public function getObject2() {return $this->secure('object_2');}
	public function getOperation() {return $this->secure('comparison');}
}



class logicCombinator extends cloud_base {
	
	public function __construct($terms, $logic) {
		
		if(!is_array($terms) || count($terms) < 1)
			cloud_logging::unsafe_query(
				'Comparisons',
				'Comparison Logic',
				array(
					'An empty logic combinator was supplied.',
					'> Your logic may be flawed',
					'> Database errors may occur',
				)
			);
		
		// Make sure the comparison is a valid comparison operator
		$supported_logic = array(
			'XOR'=>'XOR',
			'AND'=>'AND',
			'OR'=>'OR',
			'NOT'=>'NOT'
		);
		
		$logic = strtoupper($logic);
		if(!isset($supported_logic[$logic]))
			cloud_logging::unsafe_query(
				'Comparisons',
				"Comparison Logic ($comparison)",
				array(
					'This is not a globally supported boolean logic combinator.',
					'> Possible disintegration of intended data set',
					'> Possible corruption of data',
					'> Possible incorrect reporting of data'
				)
			);
		
		$this->secure('logic', $logic);
		$this->secure('terms', $terms);
	}
	
	public function getLogic() {return $this->secure('logic');}
	public function getTerms() {return $this->secure('terms');}
	
}

// Order

class listOrder extends cloud_base {
	
	public function __construct($variable, $order) {
		
		$supported_orders = array(
			'DESC',
			'ASC'
		);
		
		$order = strtoupper($order);
		if(!in_array($order, $supported_orders))
			cloud_logging::unsafe_query(
				'Order',
				"Directionality ($order)",
				array(
					'This is not a globally supported directionality for order.',
					'> Possible return of unnecessary information',
					'> Possible disintegration of data output'
				)
			);
		
		$this->secure('variable', $variable);
		$this->secure('order', $order);
	}
	
	public function getVariable() {return $this->secure('variable');}
	public function getOrder() {return $this->secure('order');}
	
}

// Simple tokens (i.e.: table names, column names, etc.)

class simpleToken extends cloud_base {
	
	public function __construct($token) {
		$this->secure('token', $token);
	}
	
	public function getToken() {return $this->secure('token');}
	
}

class simpleFunction extends cloud_base {
	
	public function __construct($name, $values) {
	
		$this->secure('name', $name);
		$this->secure('values', $values);
		
	}
	
	public function getName() {return $this->secure('name');}
	public function getValues() {return $this->secure('values');}
	
}


class cloud_unescaped extends cloud_base {
	
	public function __construct($value) {
		$this->secure('value', (string) $value);
	}
	public function __toString() {return $this->secure('value');}
	public function getValue() {return $this->secure('value');}
	
}
