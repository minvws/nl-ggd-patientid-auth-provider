<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\OidcService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests;

class BearerToken implements AuthenticatesRequests
{
    protected OidcService $oidcService;

    /**
     * @param OidcService $oidcService
     */
    public function __construct(OidcService $oidcService)
    {
        $this->oidcService = $oidcService;
    }


    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    public function handle($request, \Closure $next)
    {
        $token = $this->fetchToken($request);
        if (!$token) {
            throw new AuthenticationException('Unauthenticated');
        }

        if ($this->oidcService->tokenExists($token)) {
            return $next($request);
        }

        throw new AuthenticationException('Unauthenticated');
    }

    protected function fetchToken(\Illuminate\Http\Request $request): ?string
    {
        // Check authorization header first
        $authHeader = strval($request->header('authorization', ''));
        if (str_starts_with($authHeader, "bearer ")) {
            return (string)str_replace("bearer ", "", $authHeader);
        }

        // Check query string or post
        $token = $request->query->get('access_token');
        if ($token) {
            return (string)$token;
        }

        // check post
        return (string)$request->request->get('access_token');
    }
}
