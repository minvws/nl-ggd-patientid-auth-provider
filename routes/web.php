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
    Route::post('/login', [AuthController::class, 'loginSubmit'])
        ->middleware('throttle:' . config('throttle.requests') . ',' . config('throttle.period'))
        ->name('login.submit');

    Route::get('/verify', [AuthController::class, 'verify'])->name('verify');
    Route::post('/verify', [AuthController::class, 'verifySubmit'])
        ->middleware('throttle:' . config('throttle.requests') . ',' . config('throttle.period'))
        ->name('verify.submit');

    Route::get('/resend', [AuthController::class, 'resend'])->name('resend');
    Route::post('/resend', [AuthController::class, 'resendSubmit'])
        ->name('resend.submit');
});

Route::get('/unauthenticated', function () {
    return view('unauthenticated');
})->name('login');
