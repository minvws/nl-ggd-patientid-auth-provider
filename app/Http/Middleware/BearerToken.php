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
        $token = $this->oidcService->fetchTokenFromRequest($request);
        if (!$token) {
            throw new AuthenticationException('Unauthenticated');
        }

        if ($this->oidcService->tokenExists($token)) {
            return $next($request);
        }

        throw new AuthenticationException('Unauthenticated');
    }
}
