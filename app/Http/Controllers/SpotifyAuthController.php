<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

/**
 * Contains functinos that authenticates the user with the Spotify API
 */
class SpotifyAuthController extends Controller
{
    protected $redirectUri;
    protected $clientId;
    protected $clientSecret;
    protected $spotifyService;

    function __construct(\Illuminate\Session\Store $session, \App\SpotifyService $spotifyService) {
        $this->session = $session;
        $this->redirectUri = env('SPOTIFY_CALLBACK', 'http://localhost:8000/spotify/callback');
        $this->clientId = env('SPOTIFY_CLIENT_ID');
        $this->clientSecret = env('SPOTIFY_CLIENT_SECRET');
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
            // If there is no authentication token, we need to get one
            return view('login');
        } else {
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
            $res = $this->spotifyService->post("https://accounts.spotify.com/api/token", [
                    'form_params' => [
                        'grant_type' => 'authorization_code',
                        'code' => $code,
                        'redirect_uri' => $this->redirectUri,
                        'client_id' => $this->clientId,
                        'client_secret' => $this->clientSecret
                    ],
                ]);


            // Store the access data
            if ($res->getSuccess() == false) {
                return "There was an error, please try again.";
            }
            $data = $res->getData();
            $this->session->put('access_token', $data['access_token']);
            $this->session->put('refresh_token', $data['refresh_token']);
            $this->session->put('token_expires_in', $data['expires_in']);

            // Get and store the user data 
            $res = $this->spotifyService->get('https://api.spotify.com/v1/me');
            $userInfo = $res->getData();

            // If a user isn't signed up through Facebook they may not have a display name, so use their ID
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
