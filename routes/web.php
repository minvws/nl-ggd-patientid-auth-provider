<?php

declare(strict_types=1);

use App\Http\Controllers\AuthController;
use App\Http\Controllers\OidcController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return 'GGD PatientId Auth Provider';
});

Route::get('/oidc/authorize', [OidcController::class, 'authorize']);
Route::post('/oidc/accesstoken', [OidcController::class, 'accessToken'])
    ->middleware('cors');

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
