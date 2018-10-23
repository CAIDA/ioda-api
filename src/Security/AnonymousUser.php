<?php


namespace App\Security;


class AnonymousUser extends User
{

    public function __construct()
    {
        parent::__construct(null, ['IS_AUTHENTICATED_ANONYMOUSLY']);
    }

    public function getUsername(): ?string
    {
        return null;
    }
}
