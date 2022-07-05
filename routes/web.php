<?php

declare(strict_types=1);

use App\Http\Controllers\AuthController;
use App\Services\OidcService;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

Route::get('/oidc/authorize', function (Request $request, OidcService $oidcService) {
    return $oidcService->authorize($request);
});

Route::middleware('cors')->group(function () {
    Route::post('/oidc/accesstoken', function (Request $request, OidcService $oidcService) {
        return $oidcService->accessToken($request);
    });
});

Route::get('/', function () {
    return 'GGD PatientId Auth Provider';
});

Route::get('.well-known/openid-configuration', [AuthController::class, 'configuration']);

Route::middleware('oidc.session')->group(function () {
    Route::get('/login', [AuthController::class, 'login'])->name('start_auth');
    Route::get('/verify', [AuthController::class, 'verify'])->name('verify');
    Route::get('/resend', [AuthController::class, 'resend'])->name('resend');

    Route::middleware('throttle:' . config('throttle.requests') . ',' . config('throttle.period'))->group(function () {
        Route::post('/login', [AuthController::class, 'loginSubmit'])->name('login.submit');
        Route::post('/verify', [AuthController::class, 'verifySubmit'])->name('verify.submit');
        Route::post('/resend', [AuthController::class, 'resendSubmit'])->name('resend.submit');
    });
});

Route::get('/unauthenticated', function () {
    return view('unauthenticated');
})->name('login');
