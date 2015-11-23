<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

class SpotifyAuthController extends Controller
{
	protected $client;
	protected $redirectUri = 'http://localhost:8000/spotify/callback';
	protected $clientId = '1e6e709c8b8b4936b0a22a1dd83f3f7a';
	protected $clientSecret = 'df6db89e1faa470db9a510754486c31f';

	function __construct(\Illuminate\Session\Store $session) {
		$client = new \GuzzleHttp\Client();
		$this->session = $session;
	}

	function index() {
		if (!$this->session->has('access_token')) {
			return view('index');
		} else {
			return redirect('me');
		}
	}

    function login() {
    	// Create a query with our information
    	$query = http_build_query([
    			'client_id' => $this->clientId,
				'response_type' => 'code',
				'redirect_uri' => $this->redirectUri,
				'scope' => 'playlist-read-private user-read-email user-read-private user-library-read',
				'show_dialog' => false
		]);
   		return redirect('https://accounts.spotify.com/authorize?' . $query);
    
    }

    function callback(Request $request) {

    	// The code given by the Spotify login page
    	$code = $request->input('code');


    	if ($request->error) {
    		return "There was an error";
    	} else {

    		// Now we need to get the Auth tokens
    		$client = new \GuzzleHttp\Client();

    		try {
    		    $res = $client->request('POST', "https://accounts.spotify.com/api/token", [
	    			'form_params' => [
	    				'grant_type' => 'authorization_code',
	    				'code' => $code,
	    				'redirect_uri' => $this->redirectUri,
	    				'client_id' => $this->clientId,
	    				'client_secret' => $this->clientSecret
	    			],
    			]);
    		} catch (\GuzzleHttp\Exception\RequestException $e) {
    			dd($e->getResponse()->getBody(true));
    		}

    		// Store the access data
    		$data = json_decode($res->getBody(), true);

    		$this->session->put('access_token', $data['access_token']);
    		$this->session->put('refresh_token', $data['refresh_token']);
    		$this->session->put('token_expires_in', $data['expires_in']);

    		// Get and store the user data 
    		$userInfo = $this->doSpotifyGet('https://api.spotify.com/v1/me');

    		$this->session->put('spotify_id', $userInfo['id']);
    		$this->session->put('display_name', $userInfo['display_name']);

    		return redirect('/');
    	}
    }

    function refresh() {

    		// Now we need to get the Auth tokens
    		$client = new \GuzzleHttp\Client();

    		try {

				// Get a new access token
				$res = $client->request('POST', "https://accounts.spotify.com/api/token", [
	    			'form_params' => [
	    				'grant_type' => 'refresh_token',
	    				'refresh_token' => $this->session->get('refresh_token'),
	    				'client_id' => $this->clientId,
	    				'client_secret' => $this->clientSecret
	    			],
    			]);

				$responseData = json_decode($res->getBody(), true);
				$this->session->set('access_token', $responseData['access_token']);

    		} catch (\GuzzleHttp\Exception\RequestException $e) {
    			dd($e->getResponse()->getBody(true));
    		}
    }

    function logout() {
    	$this->session->forget('access_token');
    	return redirect('/');
    }

    function tentracks() {
    	for($i = 0; $i < 10; $i++) {
    		$res = $this->doSpotifyGet('https://api.spotify.com/v1/me/tracks' . '?limit=1');
    		var_dump($res);
    	}
    }

    function tracks() {

    	$res = $this->doSpotifyGet('https://api.spotify.com/v1/me/tracks' . '?limit=1');

    	echo $res['total'];

    	$more = true;
    	$uri = 'https://api.spotify.com/v1/me/tracks';
    	$limit = 20;
    	$offset = 0;
    	$tracks = [];

    	while ($more) {
    		$data = $this->doSpotifyGet($uri);

    		foreach ($data['items'] as $item) {
    			$track = $item['track'];
    			$name = $track['name'];
    			$trackUrl = $track['external_urls']['spotify'];
    			$album = $track['album']['name'];
    			$artist = $track['artists'][0]['name'];

    			$tracks[] = $name;
    		}

    		if ($data['next'] == null) {
    			$more = false;
    		} else {
    			$uri = $data['next'];
    		}
    	}
    	var_dump($tracks);
    }

    function doSpotifyGet($uri, $query = []) {
    	   $client = new \GuzzleHttp\Client();

    	    try {
    			$res = $client->request('GET', $uri, [
    				'headers' => [
    					'Authorization' => 'Bearer ' . session('access_token')
    				]
    			]);
    			
	    		$info = json_decode($res->getBody(), true);

	    		return $info;

    		} catch (\GuzzleHttp\Exception\RequestException $e) {
    			if($e->getResponse()->getStatusCode() == 401) {

    				// Authorization error
    				if ($this->session->has('access_token')) {
    					$this->refresh();
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

    function showMeInfo(Request $request) {
    	$data = $this->doSpotifyGet("https://api.spotify.com/v1/me", []);
    	
    	var_dump($data);
    }


}
