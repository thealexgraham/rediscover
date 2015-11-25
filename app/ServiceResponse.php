<?php

namespace App;

/**
 * This is a class that stores response information from an access to the Spotify API
 */
class ServiceResponse {
	private $success;
	private $statusCode;
	private $data;

	function __construct($success, $statusCode, $data) {
		$this->success = $success;
		$this->statusCode = $statusCode;
		$this->data = $data;
	}

	/**
	 * Whether or not the request succeeded
	 * @return bool 
	 */
	function getSuccess() {
		return $this->success;
	}

	/**
	 * The HTTP status code
	 * @return int
	 */
	function getStatusCode() {
		return $this->statusCode;
	}

	/**
	 * The body of the response
	 * @return array or other
	 */
	function getData() {
		return $this->data;
	}
}