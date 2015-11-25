<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use GuzzleHttp;

class SpotifyController extends Controller
{
	protected $client;
	protected $redirectUri; 
	protected $clientId = '1e6e709c8b8b4936b0a22a1dd83f3f7a';
	protected $clientSecret = 'df6db89e1faa470db9a510754486c31f';
	protected $spotifyService;

	function __construct(\Illuminate\Session\Store $session, \GuzzleHttp\Client $client, \App\SpotifyService $spotifyService) {
		$this->client = $client;
		$this->session = $session;
		$this->redirectUri = env('SPOTIFY_CALLBACK', 'http://localhost:8000/spotify/callback');
		$this->spotifyService = $spotifyService;
	}

	/**
	 * Helper function that just returns the user
	 * @return User
	 */
	function getUser() {
		return $this->session->get('user');
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
		$data = $this->spotifyService->get('https://api.spotify.com/v1/me/tracks' . '?limit=1');
		$totalTracks = $data['total'] - 1; // Minus 1 for index

		// Get random track numbers
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
			$data = $this->spotifyService->get("https://api.spotify.com/v1/me/tracks?offset=$offset&limit=$limit");
			
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


	function createPlaylist(Request $request) {
		$data = $request->json()->all();
		$trackUris = $data['trackUris']; //$request->input('tracks');
		$playlistName = $data['name'];


		$user = $this->getUser();

		// Send the request to create a new playlist
		$uri = 'https://api.spotify.com/v1/users/' . $user->spotify_id . '/playlists';

		$res = $this->spotifyService->post($uri, [
			'headers' => [
				'Authorization' => 'Bearer ' . $this->session->get('access_token'),
				'Content-Type' => 'application/json',
			],
			'json' => [
				'name' => $playlistName
			]
		]);
		
		// Get the ID for the newly created playlist
		$playlistInfo = json_decode($res->getBody(), true);
		$playlistId = $playlistInfo['id'];

		// Send the request to add the tracks to the new playlist
		$uri = 'https://api.spotify.com/v1/users/' . $user->spotify_id . '/playlists/' . $playlistId . '/tracks';
		$res = $this->spotifyService->post($uri, [
			'headers' => [
				'Authorization' => 'Bearer ' . $this->session->get('access_token'),
				'Content-Type' => 'application/json',
			],
			'json' => [
				'uris' => $trackUris,
			]
		]);

		$info = json_decode($res->getBody(), true);
		
		return response()->json(array('success' => true, 'playlist' => $playlistInfo, 'tracks' => $info));
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

	/**
	 * Retreive all tracks (not in use) 
	 * @return json with tracks as data
	 */
	function tracks() {

		$res = $this->spotifyService->get('https://api.spotify.com/v1/me/tracks');

		echo $res['total'];

		$more = true;
		$uri = 'https://api.spotify.com/v1/me/tracks';
		$tracks = [];

		while ($more) {
			$data = $this->spotifyService->get($uri);

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
		$data = $this->spotifyService->get("https://api.spotify.com/v1/me", []);
		return response()->json(['success' => true, 'data' => $data]);
	}
}
