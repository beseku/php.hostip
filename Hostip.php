<?php
	/**
	*	@package		Host IP
	*	@author			Ben Sekulowicz-Barclay
	*	@copyright		Copyright 2009, Ben Sekulowicz-Barclay
	*	@version		0.01
	*
	********************************************************************************************************************************************* **/
	
	class HostIp {			
		
		private $api_uri 				= 'http://api.hostip.info/?ip=';
		                            	
		private $set_cache_duration 	= 3600;
		private $set_cache_enabled 		= TRUE;
		private $set_cache_location 	= './cache/hostip/';
		
		
		/** 
		*	@access	public
		*	@param	string
		*	@return	void
		*	@author Ben Sekulowicz-Barclay
		*
		***************************************************************************************************************************************** **/		
		
		public function get($i = '') {
			// Assign the IP address
			$this->set_ip = $i;
			
			// If we can find a valid cached result
			if (!($this->result = $this->cache_read())) {
			
				// If we are querying the API, but via FILE_GET_CONTENTS
				if (function_exists('file_get_contents')) {
					$xml = file_get_contents($this->api_uri . $this->set_ip);
            	
				// If we are querying the API, but via CURL
				} else if (function_exists('curl_init')) {
					$curl = curl_init($this->api_uri . $this->set_ip) ;
					curl_setopt ($curl, CURLOPT_RETURNTRANSFER, 1);
					$xml = curl_exec($curl);
					curl_close($curl);
				}
				
				// Fix the SimpleXml namespace issues
				$xml = str_replace('<gml:', '<', $xml);
				$xml = str_replace('</gml:', '</', $xml);			
				
				// Get the root XML
				$xml = new SimpleXMLElement($xml);
				
				// get the elements we need
				$this->result = array(
					'city'		=> (string) ucfirst(strtolower(@$xml->featureMember->Hostip->name)),
					'country'	=> (string) ucfirst(strtolower(@$xml->featureMember->Hostip->countryName)),
					'latlong'	=> (string) @$xml->featureMember->Hostip->ipLocation->PointProperty->Point->coordinates,
				);
				
				// Create our cache file
				$this->cache_write();				
			}
			
			// If we got a recognised response ...
			// @todo: Test the array for recognized keys before returning?
			return (is_array($this->result))? $this->result: FALSE;
		}
		
		/** 
		*	@access	public
		*	@param	string
		*	@return	void
		*	@author Ben Sekulowicz-Barclay
		*
		***************************************************************************************************************************************** **/		
		
		public function getLatLong($i) {
			// Get the result array
			$result = $this->get($i);
			
			// Return the Lat/Long value
			return ('' != $result['latlong'])? $result['latlong']: 0;	
		}
		
		/** 
		*	@access	public
		*	@param	string
		*	@return	void
		*	@author Ben Sekulowicz-Barclay
		*
		***************************************************************************************************************************************** **/		
		
		public function getLocation($i) {
			// Get the result array
			$result = $this->get($i);
			
			// Return the location values
			return $result['city'] . ', ' . $result['country'];	
		}
		
		/** 
		*	@access	public
		*	@return	mixed
		*	@author Ben Sekulowicz-Barclay
		*
		***************************************************************************************************************************************** **/		
		
		private function cache_read() {
			// Create our cache handle/filename
			$cached = $this->set_cache_location . md5($this->set_ip);
			
			// If caching is disabled or the file does not exist, stop here
			if (($this->set_cache_enabled == FALSE) || (!(file_exists($cached)))) {
				return FALSE;
			}			
			
			// If the file is too old ...
			if ($this->set_cache_duration < (date("U") - date ("U", filemtime($cached)))) {				
				// Remove the old file
				unlink($cached);
				
				// Refresh our Flickr call
				return FALSE;
			}
			
			// Read from our file
			$handle = @fopen($cached, "r");
			$result = @fread($handle, filesize($cached));
			@fclose($handle);
			
			// Return the cached result
			return unserialize($result);
		}
		
		/** 
		*	@access	public
		*	@return	boolean
		*	@author Ben Sekulowicz-Barclay
		*
		***************************************************************************************************************************************** **/		
		
		private function cache_write() {
			// If caching is disabled, don't do anything
			if ($this->set_cache_enabled == FALSE) {
				return FALSE;
			}
			
			// Create our cache handle/filename
			$cached = $this->set_cache_location . md5($this->set_ip);
			
			// Write our file
			$handle = @fopen($cached, "w");
			@fwrite($handle, serialize($this->result));
			@fclose($handle);	
			
			// For completeness
			return;		
		}
		
		/** 
		*	@access	public
		*	@param	string
		*	@return	void
		*	@author Ben Sekulowicz-Barclay
		*
		***************************************************************************************************************************************** **/		
		
		public function set_cache_duration($value = 3600) {
			$this->set_cache_duration = $value;
		}
		
		/** 
		*	@access	public
		*	@param	string
		*	@return	void
		*	@author Ben Sekulowicz-Barclay
		*
		***************************************************************************************************************************************** **/		
		
		public function set_cache_enabled($value = TRUE) {
			$this->set_cache_enabled = $value;			
		}
		
		/** 
		*	@access	public
		*	@param	string
		*	@return	void
		*	@author Ben Sekulowicz-Barclay
		*
		***************************************************************************************************************************************** **/		
		
		public function set_cache_location($value = './cache') {
			$this->set_cache_location = $value;			
		}

	}
	
	/** ***************************************************************************************************************************************** **/
?>