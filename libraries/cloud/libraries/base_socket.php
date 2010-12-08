<?php

/**
 * Base Socket
 * http://cloud.serverboy.net/
 * 
 * This code is loosely based on mjpearson's work on Pandra, specifically
 * the /thrift-php/transport/TSocket.php file
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

class cloud_base_socket extends cloud_base {
	
	private $handle = null;
	
	public function __construct($server, $port) {
		
		self::readonly('server', $server);
		self::readonly('port', $port);
		
		self::secure('send_timeout', 100);
		self::secure('receive_timeout', 750);
		self::secure('send_timeout_set', false);
		
	}
	
	public function setSendTimeout($timeout) {
		self::secure('send_timeout', $timeout);
	}
	
	public function setReceiveTimeout($timeout) {
		self::secure('receive_timeout', $timeout);
	}
	
	public function getServer() { return self::secure('server'); }
	public function getPort() { return self::secure('port'); }
	
	public function isOpen() { return isset($this->handle); }
	
	public function open() {
		
		$send_timeout = self::secure('send_timeout');
		
		try {
			$this->handle = fsockopen(
				self::secure('server'),
				self::secure('port'),
				$error_num,
				$error_txt,
				$send_timeout / 1000
			);
		} catch(Exception $e) {
			cloud_logging::error(
				"Error establishing outbound socket connection.\n" .
				$e->getMessage()
			);
		}
		
		if($this->handle === false) {
			cloud_logging::error(
				'Could not establish outbound connection.'
			);
			return;
		}
		
		stream_set_timeout($this->handle, 0, $send_timeout * 1000);
		self::secure('send_timeout_set', true);
		
	}
	
	public function close() {
		try {
			fclose($this->handle);
			$this->handle = null;
		} catch(Exception $e) {
			cloud_logging::error(
				"Error closing outbound socket connection.\n" .
				$e->getMessage()
			);
		}
	}
	
	public function read($len, $full = false) {
		if(!$this->handle) {
			cloud_logging::error(
				'Attempting to read from a socket that is not open.'
			);
			return;
		}
		
		$output = '';
		try {
			$nomore = false;
			while($full) {
				$buffer = fread($this->handle, $len);
				
				if(empty($buffer)) {
					$metadata = stream_get_meta_data($this->handle);
					
					if($metadata['time_out']) {
						cloud_logging::error('Connection timeout.');
						return '';
					}
					if($nomore) {
						cloud_logging::error('The socket expected more data, but it was not provided.');
						return $output;
					}
				}
				
				$output .= $buffer;
				
				$blen = strlen($buffer);
				if($blen < $len) {
					$len -= $blen;
					$nomore = true;
				}
			}
		} catch(Exception $e) {
			cloud_logging::warning(
				"There was a problem while attempting to read from the socket.\n" .
				$e->getMessage()
			);
		}
		
		return $output;
	}
	
	public function write($data) {
		if(!self::secure('send_timeout_set')) {
			stream_set_timeout($this->handle, 0, self::secure('send_timeout') * 1000);
			self::secure('send_timeout_set', true);
		}
		while(!empty($data)) {
			$result = fwrite($this->handle, $data);
			if($result === false || $result === 0) {
				$metadata = stream_get_meta_data($this->handle);
				
				if($metadata['time_out'])
					cloud_logging::error('Connection timeout.');
				if($nomore)
					cloud_logging::error('The socket expected more data, but it was not provided.');
				
				return;
			}
			
			$data = substr($data, $result);
			
		}
		
	}
	
	public function flush() {
		$result = fflush($this->handle);
		if($result === false)
			cloud_logging::error('The socket could not be flushed.');
	}
	
}