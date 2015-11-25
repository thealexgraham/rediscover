<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

Route::group(['prefix' => 'spotify'], function () {

	// Authentication
	Route::get('login', 'SpotifyController@login');
	Route::get('logout', 'SpotifyController@logout');
	Route::get('refresh', 'SpotifyController@refresh');
	Route::get('callback', 'SpotifyController@callback');

	// Tracks
	Route::get('tracks/random', 'SpotifyController@randomTracks');
	Route::get('tracks', 'SpotifyController@tracks');
	
	// Playlists
	Route::post('playlists', 'SpotifyController@createPlaylist');

	// Other
	Route::get('me', 'SpotifyController@getMeInfo');

});

// Route everything else to the index (which will route to either the Angular page, or the login page)
Route::any('{url?}', 'SpotifyController@index')->where(['url' => '[-a-z0-9/]+']);