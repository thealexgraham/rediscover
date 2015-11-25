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

	function getSuccess() {
		return $this->success;
	}

	function getStatusCode() {
		return $this->statusCode;
	}

	function getData() {
		return $this->data;
	}
}