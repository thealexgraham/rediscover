<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

class SpotifyController extends Controller
{
	protected $client;
	protected $redirectUri; 
	protected $clientId = '1e6e709c8b8b4936b0a22a1dd83f3f7a';
	protected $clientSecret = 'df6db89e1faa470db9a510754486c31f';

	function __construct(\Illuminate\Session\Store $session) {
		$client = new \GuzzleHttp\Client();
		$this->session = $session;
        $this->redirectUri = env('SPOTIFY_CALLBACK', 'http://localhost:8000/spotify/callback');
	}

    /**
     * Helper function that just returns the user
     * @return User
     */
	function getUser() {
		return $this->session->get('user');
	}

    /**
     * The main index, checks if there is a user logged in and routes it accordingly
     * 
     * @return view
     */
	function index() {
		if (!$this->session->has('access_token')) {
			return view('login');
		} else {
			return view('index'); //redirect('/');
		}
	}

    /**
     * Attempts to redirect the user to the Spotify authentication page
     * @return Redirect
     */
    function login() {
    	// Create a query with our information
    	$query = http_build_query([
    			'client_id' => $this->clientId,
				'response_type' => 'code',
				'redirect_uri' => $this->redirectUri,
				'scope' => 'playlist-read-private user-read-email user-read-private user-library-read playlist-modify-private playlist-modify-public',
				'show_dialog' => "true"
		]);

    	// Redirect to the spotify login page
   		return redirect('https://accounts.spotify.com/authorize?' . $query);
    }

    /**
     * Logs the user out of the application
     * @return Redirect login page
     */
    function logout() {
    	$this->session->forget('access_token');
    	$this->session->forget('refresh_token');
    	$this->session->forget('user');
    	return redirect('/');
    }

    /**
     * Called when the user has OK'd the authentication request. Responsible for 
     * then using the access code to request an authentication token and adding the 
     * user to the session. Stores the User in the database for later use
     * @param  Request  $request 
     * @return Redirect to home
     */
    function callback(Request $request) {

    	// The code given by the Spotify login page
    	$code = $request->input('code');
        \Log::error("Trying the callback");

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

            // Create the User from the database, or get it
    		$user = \App\SpotifyUser::firstOrCreate(['spotify_id' => $userInfo['id'], 'display_name' => $userInfo['display_name']]);

            // Log the user into our session
    		$this->session->put('user', $user);

    		return redirect('/');
    	}
    }

    /**
     * Used to refresh the authentication token given by Spotify
     * @return true if authentication worked
     */
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
				$this->session->put('access_token', $responseData['access_token']);
    		} catch (\GuzzleHttp\Exception\RequestException $e) {
    			dd($e->getResponse()->getBody(true));
    		}

            return true;
    }

    /**
     * Returns a number of random tracks from the users saved tracks given by the query string 'count'
     * @param  Request $request 
     * @return json of track data
     */
    function randomTracks(Request $request) {

    	$count = $request->input('count', 5);

    	$maxOffset = 50;
    	$tracks = [];

    	// Get a track to find the number of tracks we currently have
    	$data = $this->doSpotifyGet('https://api.spotify.com/v1/me/tracks' . '?limit=1');
    	$totalTracks = $data['total'];

    	// Get 10 random track numbers
    	$trackNums = [];

    	for ($i=0; $i < $count; $i++) {
    		while(in_array($num = mt_rand(0, $totalTracks), $trackNums)){}
    		$trackNums[] = $num;
    	}

    	// Sort track nums ascending
    	sort($trackNums);

    	// Split the requests into groups to make as few API calls as possible
    	$trackRequests = [];
    	$this->rangeGroup($trackNums, $maxOffset, $trackRequests);

    	// Each track request contains a group of tracks that should fit into one request
    	foreach ($trackRequests as $trackRequest) {

    		// Offest is just the first track #
    		$offset = $trackRequest[0]; 
    		// Limit is the range of the values in the request
    		$limit = $trackRequest[count($trackRequest) - 1] - $trackRequest[0] + 1;

    		// Do the request
    		$data = $this->doSpotifyGet("https://api.spotify.com/v1/me/tracks?offset=$offset&limit=$limit"); //, ['offset' => $offset, 'limit' => $limit]);
            
            if ($data == 502) {
                // If we got a bad gateway, there was a problem, so return that
                return response()->json(['success' => false]);
            } else {
                foreach ($trackRequest as $trackIdx) {
                    $trackInfo = $data['items'][$trackIdx - $offset]['track'];
                    $track = [
                        'name' => $trackInfo['name'],
                        'url' => $trackInfo['external_urls']['spotify'],
                        'album_name' => $trackInfo['album']['name'],
                        'album_url' => $trackInfo['album']['external_urls']['spotify'],
                        'artist_name' => $trackInfo['artists'][0]['name'],
                        'artist_url' => $trackInfo['artists'][0]['external_urls']['spotify'],
                        'preview_url' => $trackInfo['preview_url'],
                        'spotify_id' => $trackInfo['id'],
                        'spotify_uri' => $trackInfo['uri'],
                        'album_img' => $trackInfo['album']['images'][2]['url'],
                    ];
                    $tracks[] = $track;
                }
            }
    	}

    	return response()->json(['success' => true, 'data' => $tracks]);
    }

    /**
     * Helper function that takes in an array of SORTED numbers and groups numbers that are within the range
     * given by $max. $splits should be given as the array to be filled
     * @param  array $array   the array to be grouped
     * @param  int $max     the number of 
     * @param  array &$splits array to be returned, c style (return style was actually slower)
     * @return none     
     */
    function rangeGroup($array, $max, &$splits) {
    	
        if ($splits == null)
            $splits = [];

    	if (empty($array)) {
    		return;
    	}

    	if (count($array) == 1) {
    		// Last array, just add it to the splits
    		$splits[] = [$array[0]];
    		return;
    	}

    	if ($array[1] > $array[0] + $max) {
			// If this number isn't in a group with the number above it, add it to the splits and move on
			$splits[] = [$array[0]];
			$this->rangeGroup(array_slice($array, 1), $max, $splits);
			return;
		}

    	$bestCount = 0;    	

    	for($i=0; $i<count($array); $i++) {

    		$count = 0; // How many numbers we can include
    		$current = $array[$i];

    		for($j=$i; $j<count($array); $j++) {
    			if ($array[$j] > ($current + $max)) {
    				// If it isn't in range, break out
    				 break;
    			}
    			// If it is in the range, incease the count, and save this top idx
    			$count++;
    		}

    		if($count < $bestCount) {
    			// We've had a better grouping before, so use it
    			$bestIdx = $i - 1;
    			$adding = array_slice($array, $bestIdx, $bestCount);
    			$below = array_slice($array, 0, $bestIdx);
    			$above = array_slice($array, $bestIdx + $bestCount);
    			
    			// Add to the current splits
    			$splits[] = $adding;

    			// Add the ranges for the numbers above and below
				$this->rangeGroup($above, $max, $splits);
				$this->rangeGroup($below, $max, $splits);

    			return;
    		}

    		// This is the best count we've had so far, so store it
    		$bestCount = $count;
    		$bestIdx = $i;
    	}
    }

    function createPlaylist(Request $request) {
        $data = $request->json()->all();
        $trackUris = $data['trackUris']; //$request->input('tracks');
        $playlistName = $data['name'];
        $client = new \GuzzleHttp\Client();
        $user = $this->getUser();

        // Send the request to create a new playlist
        $uri = 'https://api.spotify.com/v1/users/' . $user->spotify_id . '/playlists';
        $res = $client->request('POST', $uri, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->session->get('access_token'),
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'name' => $playlistName
            ]
        ]);
        
        // Get the ID for the newly created playlist
        $info = json_decode($res->getBody(), true);
        $playlistId = $info['id'];

        // Send the request to add the tracks to the new playlist
        $uri = 'https://api.spotify.com/v1/users/' . $user->spotify_id . '/playlists/' . $playlistId . '/tracks';
        $res = $client->request('POST', $uri, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->session->get('access_token'),
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'uris' => $trackUris,
            ]
        ]);

        $info = json_decode($res->getBody(), true);
        
        return response()->json(array('success' => true));
    }

    function doSpotifyGet($uri, $query = []) {

    	   $client = new \GuzzleHttp\Client();

    	    try {
    			$res = $client->request('GET', $uri, [
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

    /**
     * Retreive all tracks (not in use) 
     * @return json with tracks as data
     */
    function tracks() {

        $res = $this->doSpotifyGet('https://api.spotify.com/v1/me/tracks');

        echo $res['total'];

        $more = true;
        $uri = 'https://api.spotify.com/v1/me/tracks';
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
        return response()->json(['success' => true, 'data' => $tracks]);
    }

    /**
     * Get all me info
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    function getMeInfo(Request $request) {
    	$data = $this->doSpotifyGet("https://api.spotify.com/v1/me", []);
    	return response()->json(['success' => true, 'data' => $data]);
    }
}
