<?php

namespace Laravel\Sanctum;

use Illuminate\Http\Request;
use Illuminate\Auth\GuardHelpers;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Auth\Events\Authenticated;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Auth\Authenticatable;

class SanctumGuard implements Guard
{
    use GuardHelpers;

    /**
     * The guard callback.
     *
     * @var callable
     */
    protected $guard;

    /**
     * The request instance.
     *
     * @var Request
     */
    protected $request;

    /**
     * Create a new authentication guard.
     *
     * @param  callable  $callback
     * @param  Request  $request
     * @param  UserProvider|null  $provider
     * @return void
     */
    public function __construct(callable $guard, Request $request, UserProvider $provider = null)
    {
        $this->guard = $guard;
        $this->request = $request;
        $this->provider = $provider;
    }

    public function user()
    {
        if (! is_null($this->user)) {
            return $this->user;
        }

        return $this->user = call_user_func($this->guard, $this->request);
    }


    /**
     * Set the current user.
     *
     * @param  Authenticatable  $user
     * @return $this
     */
    public function setUser(Authenticatable $user)
    {
        $this->user = $user;

        event(new Authenticated('sanctum', $user));

        return $this;
    }

    /**
     * Validate a user's credentials.
     *
     * @param  array  $credentials
     * @return bool
     */
    public function validate(array $credentials = [])
    {
        $user = $this->provider->retrieveByCredentials($credentials);

        // If an implementation of UserInterface was returned, we'll ask the provider
        // to validate the user against the given credentials, and if they are in
        // fact valid we'll log the users into the application and return true.
        if (! is_null($user) && $this->hasValidCredentials($user, $credentials)) {
            $this->setUser($user);

            return true;
        }

        return false;
    }

    /**
     * Determine if the user matches the credentials.
     *
     * @param  mixed  $user
     * @param  array  $credentials
     * @return bool
     */
    protected function hasValidCredentials($user, $credentials)
    {
        return $this->provider->validateCredentials($user, $credentials);
    }

    /**
     * Set the current request instance.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return $this
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;

        return $this;
    }
}
