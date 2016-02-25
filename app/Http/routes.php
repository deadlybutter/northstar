<?php

/**
 * Set routes for the application.
 *
 * @var \Illuminate\Routing\Router $router
 * @see \Northstar\Providers\RouteServiceProvider
 */

// Redirect to some useful documentation on the homepage.
$router->get('/', function () {
    return redirect()->to('https://github.com/DoSomething/api');
});

// Simple health check endpoint
$router->get('/status', function () {
    return ['status' => 'good'];
});

// https://northstar.dosomething.org/v1/
$router->group(['prefix' => 'v1'], function () use ($router) {
    // Authentication
    $router->post('auth/token', 'AuthController@createToken');
    $router->post('auth/invalidate', 'AuthController@invalidateToken');
    $router->post('auth/verify', 'AuthController@verify');
    $router->post('auth/register', 'AuthController@register');

    // OAuth2
    $router->post('oauth/token', function(Illuminate\Http\Request $request)
    {
        // @TODO We can "build" a response from legacy endpoints
        if(! $request->has('grant_type')) {
            $request->merge(['grant_type' => 'user_credentials']);
        }

        $bridgedRequest  = \OAuth2\HttpFoundationBridge\Request::createFromRequest($request);
        $bridgedResponse = new \OAuth2\HttpFoundationBridge\Response();

        $bridgedResponse = app('oauth2')->handleTokenRequest($bridgedRequest, $bridgedResponse);

        return $bridgedResponse;
    });

    // Example protected route
    $router->get('private', function(Illuminate\Http\Request $request)
    {
        $bridgedRequest  = OAuth2\HttpFoundationBridge\Request::createFromRequest($request);
        $bridgedResponse = new OAuth2\HttpFoundationBridge\Response();

        if (app('oauth2')->verifyResourceRequest($bridgedRequest, $bridgedResponse)) {

            $token = app('oauth2')->getAccessTokenData($bridgedRequest);

            return response()->json([
                'private' => 'stuff',
                'user_id' => $token['user_id'],
                'client'  => $token['client_id'],
            ]);
        }
        else {
            return response()->json([
                'error' => 'Unauthorized'
            ], $bridgedResponse->getStatusCode());
        }
    });

    // Users
    $router->resource('users', 'UserController', ['except' => ['show', 'update']]);
    $router->get('users/{term}/{id}', 'UserController@show');
    $router->put('users/{term}/{id}', 'UserController@update');
    $router->post('users/{id}/avatar', 'AvatarController@store');

    // Profile (the currently authenticated user)
    $router->get('profile', 'ProfileController@show');
    $router->post('profile', 'ProfileController@update');
    $router->get('profile/signups', 'SignupController@profile');
    $router->get('profile/reportbacks', 'ReportbackController@profile');

    // Signups & Reportbacks (Phoenix)
    $router->resource('signups', 'SignupController', ['only' => ['index', 'show', 'store']]);
    $router->resource('reportbacks', 'ReportbackController', ['only' => ['index', 'show', 'store']]);

    // API Keys
    $router->resource('keys', 'KeyController');
    $router->get('scopes', function () {
        return \Northstar\Models\ApiKey::scopes();
    });
});
