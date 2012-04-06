<?php

namespace Ayamel\ApiBundle;

/**
 * Simple class to wrap curl for testing api routes.  If JSON is returned by the api, the structure will be automatically decoded, returning a PHP object.
 */
class ApiTester {
	
	protected $base_url = false;
	protected $last_code = false;
	protected $last_type = false;
	protected $last_result = false;
	protected $query_time = false;
	
	public function __construct($base_url = null) {
		if($base_url) {
			$this->base_url = $base_url;
		}
	}
	
	public function setBaseUrl($string) {
		$this->base_url = $string;
	}
	
	public function getLastResponseCode() {
		return $this->last_code;
	}

	public function getLastResponseType() {
		return $this->last_type;
	}
	
	public function getLastQueryTime() {
		return $this->query_time;
	}
	
	public function get($uri, $data = null) {
		return $this->call('GET', $uri, $data);
	}
	
	public function put($uri, $data = null) {
		return $this->call('PUT', $uri, $data);
	}
	
	public function post($uri, $data = null) {
		return $this->call('POST', $uri, $data);
	}
	
	public function delete($uri, $data = null) {
		return $this->call('DELETE', $uri, $data);
	}
	
	public function debugLastQuery() {
		return
"<h3>Query Debug</h3>
<pre>
    Query Time: ".$this->getLastQueryTime()." ms
    Response Code: ".$this->getLastResponseCode()."
    Response Type: ".$this->getLastResponseType()."
    Response Body: 

".print_r($this->last_result, true)."
</pre>";
	}
	
	protected function call($method, $uri, $data = null) {
		//if not fully qualified, prepend base_url, strip slashes
		if(0 !== strpos(strtolower($uri), 'http') && $this->base_url) {
			$uri = rtrim($this->base_url."/".ltrim($uri, "/"), "/");
		}
		
		//encode data if any
		if(null !== $data) {
			if(!$data = @json_encode($data)) {
				$data = http_build_query($data);
			}
		}

		//build curl object
        $ch = curl_init($uri);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		
		//set http request method
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
		
		//send encoded data if exists
		if(null !== $data) {
	        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		}
 	   
		//execute, store query data, and return response
		$startTime = microtime(true);
		$content = curl_exec($ch);
		$this->query_time = (microtime(true)-$startTime) * 1000;
		$this->last_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$this->last_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
		$this->last_result = $content;
		
		//return result, json_decoding if possible
		return ($data = @json_decode($content)) ? $data : $content;
	}
}