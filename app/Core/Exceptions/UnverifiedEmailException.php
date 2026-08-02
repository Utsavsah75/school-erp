<?php

namespace App\Core\Exceptions;

/** Thrown by Auth::attempt() when credentials are valid but the account's email is not yet verified. */
class UnverifiedEmailException extends \RuntimeException
{
    public function __construct(public readonly array $user)
    {
        parent::__construct('Email address is not verified.');
    }
}
