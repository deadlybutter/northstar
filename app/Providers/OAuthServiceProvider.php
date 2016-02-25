<?php

namespace Northstar\Providers;

use Illuminate\Support\ServiceProvider;
use Northstar\Auth\OAuthStorage;
use OAuth2\GrantType\ClientCredentials as ClientCredentialsGrant;
use OAuth2\GrantType\UserCredentials as UserCredentialsGrant;
use OAuth2\Server as OAuth2Server;

class OAuthServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // ...
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('\OAuth2\Server', function($app) {
            /**
             * @var \Jenssegers\Mongodb\Connection $connection
             */
            $connection = app('db')->connection();
            $storage = new OAuthStorage($connection->getMongoDB());

            // @TODO: Migrate these from existing "keys"...
            // $storage->setClientDetails('TestClient', 'TestSecret', 'http://www.example.com');

            $server = new OAuth2Server($storage, [
                // @TODO: JWT access tokens?!
                // 'use_jwt_access_tokens' => true,

                // For now, we don't want access tokens to expire.
                'access_lifetime' => null,
            ]);

            $server->addGrantType(new ClientCredentialsGrant($storage));
            $server->addGrantType(new UserCredentialsGrant($storage));

            return $server;
        });

        $this->app->alias('\OAuth2\Server', 'oauth2');
    }
}
