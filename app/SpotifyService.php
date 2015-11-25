<?php
namespace App;

class SpotifyService {

	protected $client;
	protected $redirectUri; 
	protected $clientId = '1e6e709c8b8b4936b0a22a1dd83f3f7a';
	protected $clientSecret = 'df6db89e1faa470db9a510754486c31f';

	function __construct(\Illuminate\Session\Store $session, \GuzzleHttp\Client $client) {
		$this->client = $client;
		$this->session = $session;
		$this->redirectUri = env('SPOTIFY_CALLBACK', 'http://localhost:8000/spotify/callback');
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
				dd($e->getResponse()->getBody(true));
			}

			return true;
	}

	function doSpotifyGet($uri, $query = []) {

		try {
			$res = $this->client->request('GET', $uri, [
				'headers' => [
					'Authorization' => 'Bearer ' . $this->session->get('access_token')
				]
			]);
			
			if ($res->getStatusCode() != 200) {
				return $res->getStatusCode();
			}

			$info = json_decode($res->getBody(), true);

			return $info;

		} catch (\GuzzleHttp\Exception\RequestException $e) {
			if($e->getResponse()->getStatusCode() == 401) {
				\Log::error("Receiving a 401");

				// Authorization error
				if ($this->session->has('access_token')) {
					\Log::error("Trying to refresh");
					//$this->refresh();
					if ($this->refresh()) 
						$this->doSpotifyGet($uri, $query);
				} else {
					return redirect('spotify/login');
				}

				// Redirect to login
			} else {
				echo $e->getResponse()->getStatusCode() . "\n";
				echo $e->getResponse()->getBody();

			}
		}
	}
}
