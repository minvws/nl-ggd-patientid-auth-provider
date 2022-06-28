<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Oidc\JsonClientResolver;
use Closure;
use Illuminate\Http\Request;

class Cors
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

            $client_id = $request->get('client_id');
            $client = $this->clientResolver->resolve($client_id);
            if (!$client) {
                return $response;
            }

            foreach ($client->getRedirectUris() as $uri) {
                if (str_starts_with($uri, $origin)) {
                    $response->headers->set('Access-Control-Allow-Origin', $origin);
                    break;
                }
            }
        }

        return $response;
    }
}
