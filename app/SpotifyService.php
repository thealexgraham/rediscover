<?php
namespace App;

class SpotifyService {

	protected $client;
	protected $retries = 0;
	protected $maxRetries = 5;

	function __construct(\Illuminate\Session\Store $session, \GuzzleHttp\Client $client) {
		$this->client = $client;
		$this->session = $session;
	}

	/**
	 * Used to refresh the authentication token given by Spotify
	 * @return true if authentication worked
	 */
	function refresh() {

			// Now we need to get the Auth tokens
			try {
				// Get a new access token
				$res = $this->client->request('POST', "https://accounts.spotify.com/api/token", [
					'form_params' => [
						'grant_type' => 'refresh_token',
						'refresh_token' => $this->session->get('refresh_token'),
						'client_id' => $this->clientId,
						'client_secret' => $this->clientSecret
					],
				]);
				$responseData = json_decode($res->getBody(), true);
				$this->session->put('access_token', $responseData['access_token']);
			} catch (\GuzzleHttp\Exception\RequestException $e) {
				return false;
			}

			return true;
	}

	function post($uri, $params) {
		try {
			$res = $this->client->request('POST', $uri, $params);
		} catch (\GuzzleHttp\Exception\RequestException $e) {
			if($e->getResponse()->getStatusCode() == 401) {
				\Log::error("Receiving a 401");

				// Authorization error, need to refresh
				if ($this->session->has('access_token')) {
					// If the refresh was successful, try again, otherwise return an error 
					if ($this->refresh())
						$this->post($uri, $params);
					else
						return 401;
				} else {
					return 401;
				}
			} else {
				return $e->getResponse()->getStatusCode();
			}
		}
		
		return $res;
	}

	/**
	 * Perform a get to the spotify service and return the information
	 * @param  [type] $uri   [description]
	 * @param  array  $query [description]
	 * @return [type]        [description]
	 */
	function get($uri, $query = []) {

		try {
			// Do the get with the user's access token
			$res = $this->client->request('GET', $uri, [
				'headers' => [
					'Authorization' => 'Bearer ' . $this->session->get('access_token')
				]
			]);
			
			// If there was a problem, return the status code instead
			if ($res->getStatusCode() != 200) {
				return $res->getStatusCode();
			}

			$info = json_decode($res->getBody(), true);

			return $info;

		} catch (\GuzzleHttp\Exception\RequestException $e) {
			if($e->getResponse()->getStatusCode() == 401) {
				\Log::error("Receiving a 401");

				// Authorization error, need to refresh
				if ($this->session->has('access_token')) {
					if ($this->refresh())
						// If the refresh was successful, try again, otherwise return an error
						$this->get($uri, $query);
					else
						return 401;
				} else {
					return 401;
				}

			// Redirect to login
			} else {
				echo $e->getResponse()->getStatusCode() . "\n";
				echo $e->getResponse()->getBody();

			}
		}
	}
}
