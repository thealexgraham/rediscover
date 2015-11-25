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

Route::get('/login', 'SpotifyAuthController@index');
Route::get('/logout', 'SpotifyAuthController@logout');


Route::group(['prefix' => 'spotify'], function () {
	Route::get('login', 'SpotifyAuthController@login');
	Route::get('callback', 'SpotifyAuthController@callback');
	Route::get('tracks/random', 'SpotifyAuthController@randomTracks');
	Route::get('tracks', 'SpotifyAuthController@tracks');
	Route::post('playlists', 'SpotifyAuthController@createPlaylist');
	Route::get('playlists/create', 'SpotifyAuthController@createPlaylist');
	Route::get('refresh', 'SpotifyAuthController@refresh');
});

Route::post('test', function (Request $request) {
	dd($request);
});

Route::get('me', 'SpotifyAuthController@showMeInfo');


Route::get('tentracks', 'SpotifyAuthController@tentracks');

Route::any('{url?}', 'SpotifyAuthController@index')->where(['url' => '[-a-z0-9/]+']);

// Route::any('{url?}', function($url) { 
// 	return view('index');
// })->where(['url' => '[-a-z0-9/]+']);