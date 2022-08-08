<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

class OAuthValidationException extends Exception
{
    // Set to true when this exception can redirect the error back to the redirect URI
    protected bool $redirect = false;

    public function __construct(string $message, bool $redirect)
    {
        parent::__construct($message);

        $this->redirect = $redirect;
    }

    public function canRedirect(): bool
    {
        return $this->redirect;
    }

    public static function invalidRedirectUri(bool $redirect = false): self
    {
        return new self('invalid_redirect_uri', $redirect);
    }

    public static function unsupportedResponseType(bool $redirect = false): self
    {
        return new self('unsupported_response_type', $redirect);
    }

    public static function invalidRequest(bool $redirect = false): self
    {
        return new self('invalid_request', $redirect);
    }

    public static function unauthorizedClient(bool $redirect = false): self
    {
        return new self('unauthorized_client', $redirect);
    }
}
