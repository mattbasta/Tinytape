<?php

/**
 * Base Class with Security
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

class cloud_base {
	
	// Data Storage
	
	/**
	 * This function stores data in a static variable for quick, secure retrieval.
	 */
	protected function secure($key, $value = null, $unset = false) {
		static $safe; // Static data store
		
		if(CLOUD_INTERNAL_SECURITY) {
			// This junk is necessary because outside classes that extend base can access
			// this on classes that need secure storage...which kind of defeats the purpose
			
			// It can be turned off in the root file.
			$backtrace = debug_backtrace(true);
			$caller = $backtrace[ min(count($backtrace), 1) ]['object'];
			$caller_hash = spl_object_hash($caller);
			
			if(!isset($safe[$caller_hash]))
				$safe[$caller_hash] = array();
			
			$ref =& $safe[$caller_hash];
		} else
			$ref =& $safe;
		
		if($value == null) {
			if($unset) {
				$value = $ref[$key];
				unset($ref[$key]);
				return $value;
			} else {
				if(!isset($ref[$key]))
					return null;
				return $ref[$key];
			}
		} else
			$ref[$key] = $value;
	}
	
	/**
	 * This function stores data in a the secure data, though the data is not writable once it is set.
	 */
	protected function readOnly($key, $value = null) {
		static $safe; // Static data store
		
		if(CLOUD_INTERNAL_SECURITY) {
			// This junk is necessary because outside classes that extend base can access
			// this on classes that need secure storage...which kind of defeats the purpose
			
			// It can be turned off in the root file.
			$backtrace = debug_backtrace(true);
			$caller = $backtrace[ min(count($backtrace), 1) ]['object'];
			$caller_hash = spl_object_hash($caller);
			
			if(!isset($safe[$caller_hash]))
				$safe[$caller_hash] = array();
			
			$ref =& $safe[$caller_hash];
		} else
			$ref =& $safe;
		
		$isthere = isset($ref[$key]);
		if($value == null || $isthere) {
			if($isthere)
				return $ref[$key];
			else
				return false;
		} else
			$ref[$key] = $value;
	}
	
	/**
	 * This function is read-only, but it deletes data after it is read
	 */
	protected function readOnce($key) {
		return $this->secure($key, null, true);
	}
}