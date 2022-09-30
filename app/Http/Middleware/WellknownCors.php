<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Oidc\JsonClientResolver;
use Closure;
use Illuminate\Http\Request;

class WellknownCors
{
    protected JsonClientResolver $clientResolver;

    public function __construct(JsonClientResolver $clientResolver)
    {
        $this->clientResolver = $clientResolver;
    }

    public function handle(Request $request, Closure $next): mixed
    {
        $response = $next($request);

        if ($request->headers->has('Origin')) {
            $origin = $request->headers->get('Origin');
            if (empty($origin)) {
                return $response;
            }

            foreach ($this->clientResolver->getClients() as $client) {
                foreach ($client->getRedirectUris() as $uri) {
                    if (str_starts_with(strtolower($uri), strtolower($origin))) {
                        $response->headers->set('Access-Control-Allow-Origin', $origin);
                        break;
                    }
                }
            }
        }

        return $response;
    }
}
