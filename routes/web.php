<?php

declare(strict_types=1);

use App\Http\Controllers\AuthController;
use App\Services\OidcService;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

Route::get('/oidc/authorize', function (Request $request, OidcService $oidcService) {
    return $oidcService->authorize($request);
});

Route::post('/oidc/accesstoken', function (Request $request, OidcService $oidcService) {
    return $oidcService->accessToken($request);
});

Route::middleware('oidc.session')->group(function () {
    Route::get('/login', [AuthController::class, 'login'])->name('login');
    Route::get('/confirm', [AuthController::class, 'confirm'])->name('confirm');
    Route::get('/resend', [AuthController::class, 'resend'])->name('resend');

    Route::middleware('throttle:'.config('throttle.requests').','.config('throttle.period'))->group(function () {
        Route::post('/login', [AuthController::class, 'loginSubmit'])->name('login.submit');
        Route::post('/confirm', [AuthController::class, 'confirmationSubmit'])->name('confirmation.submit');
        Route::post('/resend', [AuthController::class, 'resendSubmit'])->name('resend.submit');
    });
});

Route::get('/unauthenticated', function () {
    return view('unauthenticated');
})->name('unauthenticated');
