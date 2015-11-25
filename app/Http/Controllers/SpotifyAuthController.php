<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use GuzzleHttp;

class SpotifyAuthController extends Controller
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
     * The main index, checks if there is a user logged in and routes it accordingly
     * 
     * @return view
     */
    function index() {
        if (!$this->session->has('access_token')) {
            return view('login');
        } else {
            //\JavaScript::put(['username' => 'Alex Graham']);
            return view('index')->with('username', $this->getUser()->display_name);
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
                'scope' => 'playlist-read-private user-library-read playlist-modify-private',
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

        if ($request->error) {
            return "There was an error";
        } else {

            // Now we need to get the Auth tokens
            try {
                $res = $this->client->request('POST', "https://accounts.spotify.com/api/token", [
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
            $userInfo = $this->spotifyService->doSpotifyGet('https://api.spotify.com/v1/me');

            if($userInfo['display_name'] == null) {
                $userInfo['display_name'] = $userInfo['id'];
            }

            // Create the User from the database, or get it
            $user = \App\SpotifyUser::firstOrCreate(['spotify_id' => $userInfo['id'], 'display_name' => $userInfo['display_name']]);

            // Log the user into our session
            $this->session->put('user', $user);

            return redirect('/');
        }
    }
}
