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

Route::get('/', function () {
    return redirect()->to('https://github.com/DoSomething/api');
});

// https://api.dosomething.org/v1/
Route::group(['prefix' => 'v1', 'middleware' => 'auth.api'], function () {
    // Campaigns.
    Route::group(['middleware' => 'auth.token'], function () {
        Route::get('user/campaigns/{campaign_id}', 'CampaignController@show');
        Route::post('user/campaigns/{campaign_id}/signup', 'CampaignController@signup');
        Route::post('user/campaigns/{campaign_id}/reportback', 'CampaignController@reportback');
        Route::put('user/campaigns/{campaign_id}/reportback', 'CampaignController@reportback');
        Route::post('kudos', 'KudosController@store');
        Route::delete('kudos', 'KudosController@delete');
    });

    // Sessions.
    Route::post('login', 'AuthController@login');
    Route::post('logout', 'AuthController@logout');

    // Users.
    Route::resource('users', 'UserController');
    Route::get('users/{term}/{id}/campaigns', 'CampaignController@index');
    Route::get('users/{term}/{id}', 'UserController@show');
    Route::post('users/{id}/avatar', 'AvatarController@store');

    // Avatar pre-signed URL
    Route::get('avatar-presigned-url/{id}', 'AvatarController@showPresignedUrl');

    // Signup Groups.
    Route::get('signup-group/{id}', 'SignupGroupController@show');

    // Api Keys.
    Route::resource('keys', 'KeyController');

});

