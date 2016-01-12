<?php

namespace Northstar\Auth;

use Illuminate\Support\Facades\Auth;
use Northstar\Services\Registrar;

class PasswordGrantVerifier
{
    /**
     * @var Registrar
     */
    protected $registrar;

    public function __construct(Registrar $registrar)
    {
        $this->registrar = $registrar;
    }

    public function verify($username, $password)
    {
        $credentials = [
            'email'    => $username,
            'password' => $password,
        ];

        if (Auth::once($credentials)) {
            return Auth::user()->id;
        }

        return false;
    }

}
