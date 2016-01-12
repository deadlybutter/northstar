<?php

namespace Northstar\Providers;

use Illuminate\Support\ServiceProvider;
use League\OAuth2\Server\ResourceServer;
use Northstar\OAuth\AccessTokenStorage;
use Northstar\OAuth\ClientStorage;
use Northstar\OAuth\ScopeStorage;
use Northstar\OAuth\SessionStorage;

class OAuthServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        // ...
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('oauth2', function () {
            // Configure the OAuth 2 resource server
            $sessionStorage = new SessionStorage();
            $accessTokenStorage = new AccessTokenStorage();
            $clientStorage = new ClientStorage();
            $scopeStorage = new ScopeStorage();

            return new ResourceServer($sessionStorage, $accessTokenStorage, $clientStorage, $scopeStorage);
        });
    }
}

