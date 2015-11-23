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

Route::get('/', 'SpotifyAuthController@index');


Route::group(['prefix' => 'spotify'], function () {
	Route::get('login', 'SpotifyAuthController@login');

	Route::get('callback', 'SpotifyAuthController@callback');
});

Route::post('test', function (Request $request) {
	dd($request);
});

Route::get('me', 'SpotifyAuthController@showMeInfo');

Route::get('tracks', 'SpotifyAuthController@tracks');
Route::get('tentracks', 'SpotifyAuthController@tentracks');