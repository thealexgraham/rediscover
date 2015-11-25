<?php
namespace App;

/**
 * Class responseible for making any calls to the Spotify API
 */
class SpotifyService {

	protected $client;

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

	/**
	 * Does a POST to the given URI. Since the POSTs require different ways of doing credentials,
	 * the user of the function should pass them in correctly. Will refresh access token if a 401 is returned.
	 * @param  string $uri   The URI to GET
	 * @param  array  $params All of the parameters
	 * @return ServiceResponse Object containing the response
	 */
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
						return $this->createResponse(false, 401);
				} else {
					return $this->createResponse(false, 401);
				}
			} else {
				return $e->getResponse()->getStatusCode();
			}
		}

		return $this->createResponse(true, $res->getStatusCode(), json_decode($res->getBody(), true));
	}

	/**
	 * Perform a get to the spotify service and return the information. This will use the access code
	 * already stored. Will refresh the access token if necessar	
	 * @param  string $uri   The URI to GET
	 * @param  array  $query extra queries to do (not implemented)
	 * @return ServiceResponse Object containing the response
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
				return $this->createResponse(false, $res->getStatusCode());
			}

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
			} else {
				// Other access code problem, return false and send the access code
				return $this->createResponse(false, $e->getResponse()->getStatusCode());
			}
		}

		return $this->createResponse(true, $res->getStatusCode(), json_decode($res->getBody(), true));
	}

	/**
	 * Creates a response object to send back to the Controllers
	 * @param  Boolean $success Whether or not the call was successful
	 * @param  integer $code    The status code of the response
	 * @param  array  $data     The json decoded data the spotify service sent
	 * @return ServiceResponse  An object containing all of these
	 */
	function createResponse($success, $code, $data = []) {
		return new \App\ServiceResponse($success, $code, $data);
	}
}
