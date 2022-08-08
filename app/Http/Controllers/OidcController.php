<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\OidcService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class OidcController extends Controller
{
    public function __construct(
        protected OidcService $oidcService,
    ) {
    }

    public function authorize(Request $request): RedirectResponse
    {
        return $this->oidcService->authorize($request);
    }

    public function accessToken(Request $request): JsonResponse
    {
        return $this->oidcService->accessToken($request);
    }
}
