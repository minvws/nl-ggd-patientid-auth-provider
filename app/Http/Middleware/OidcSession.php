<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\OidcService;
use Illuminate\Http\Request;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests;

class OidcSession implements AuthenticatesRequests
{
    protected OidcService $oidcService;

    public function __construct(OidcService $oidcService)
    {
        $this->oidcService = $oidcService;
    }

    public function handle(Request $request, \Closure $next): mixed
    {
        if ($this->oidcService->hasAuthorizeSession($request)) {
            return $next($request);
        }
        throw new AuthenticationException('Unauthenticated');
    }
}
