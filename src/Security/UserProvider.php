<?php

namespace App\Security;

use Auth0\JWTAuthBundle\Security\Auth0Service;
use Auth0\JWTAuthBundle\Security\Core\JWTUserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserProvider implements JWTUserProviderInterface
{
    protected $auth0Service;

    public function __construct(Auth0Service $auth0Service)
    {
        $this->auth0Service = $auth0Service;
    }

    public function loadUserByJWT($jwt)
    {
        // $data = $this->auth0Service->getUserProfileByA0UID($jwt->token,$jwt->sub);
        $roles = ['ROLE_OAUTH_AUTHENTICATED', 'ROLE_USER'];
        /*
        if (isset($jwt->scope)) {
            $scopes = explode(' ', $jwt->scope);
            if (array_search('read:messages', $scopes) !== false) {
                $roles[] = 'ROLE_OAUTH_READER';
            }
        }
        */
        return new User($jwt, $roles);
    }

    public function getAnonymousUser()
    {
        return new AnonymousUser();
    }

    /**
     * Symfony calls this method if you use features like switch_user
     * or remember_me.
     *
     * @var $username
     *
     * @throws \Exception
     */
    public function loadUserByUsername($username)
    {
        // Load a User object from your data source or throw UsernameNotFoundException.
        // The $username argument may not actually be a username:
        // it is whatever value is being returned by the getUsername()
        // method in your User class.
        throw new \Exception('Unsupported method');
    }

    /**
     * Refreshes the user after being reloaded from the session.
     *
     * When a user is logged in, at the beginning of each request, the
     * User object is loaded from the session and then this method is
     * called. Your job is to make sure the user's data is still fresh by,
     * for example, re-querying for fresh User data.
     *
     * If your firewall is "stateless: false" (for a pure API), this
     * method is not called.
     *
     * @var $user
     *
     * @throws \Exception
     */
    public function refreshUser(UserInterface $user)
    {
        throw new \Exception('Unsupported method');
    }

    /**
     * Tells Symfony to use this provider for this User class.
     *
     * @var $class
     * @return bool
     */
    public function supportsClass($class)
    {
        return User::class === $class;
    }
}
