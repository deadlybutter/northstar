<?php

namespace Northstar\Auth;

use OAuth2\Storage\ClientCredentialsInterface;
use OAuth2\Storage\Mongo;
use OAuth2\Storage\UserCredentialsInterface;

use Northstar\Models\ApiKey;

class OAuthStorage extends Mongo implements UserCredentialsInterface, ClientCredentialsInterface
{
    /**
     * The registrar.
     * @var \Northstar\Auth\Registrar $registrar
     */
    protected $registrar;

    public function __construct($connection)
    {
        $this->registrar = app('\Northstar\Auth\Registrar');

        parent::__construct($connection, []);
    }

    /**
     * ...
     *
     * @param $username
     * @param $password
     * @return bool
     */
    public function checkUserCredentials($username, $password)
    {
        // @TODO Need to find user by "magic username" here. Forcing email for now.
        if ($user = $this->registrar->resolve(['email' => $username])) {
            return $this->registrar->verify($user, ['password' => $password]);
        }

        return false;
    }

    /**
     * ...
     *
     * @param $username
     * @return array|null
     */
    public function getUserDetails($username)
    {
        $user = $this->registrar->resolve(['email' => $username]);

        if(! $user) {
            return null;
        }

        $array = $user->toArray();
        $array['user_id'] = $user->id;

        return $array;
    }

    /**
     * Make sure that the client credentials are valid.
     *
     * @param $client_id - Client identifier to be check with.
     * @param $client_secret (optional) - If a secret is required, check that they've given the right one.
     *
     * @return bool
     */
    public function checkClientCredentials($client_id, $client_secret = null)
    {
        if ($result = ApiKey::where(['app_id' => $client_id])->first()) {
            return $result->api_key === $client_secret;
        }

        return false;
    }

    /**
     * Determine if the client is a "public" client, and therefore does not
     * require passing credentials for certain grant types
     *
     * @param $client_id - Client identifier to be check with.
     * @return bool
     */
    public function isPublicClient($client_id)
    {
        if (! $result = ApiKey::where(['app_id' => $client_id])->first()) {
            return false;
        }

        return empty($result->api_key);
    }

    /* ClientInterface */
    public function getClientDetails($client_id)
    {
        $result = ApiKey::where(['app_id' => $client_id])->first();

        return is_null($result) ? false : $result;
    }

}
