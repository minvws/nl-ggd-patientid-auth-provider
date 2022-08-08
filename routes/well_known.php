<?php

declare(strict_types=1);

use App\Http\Controllers\WellKnownController;
use Illuminate\Support\Facades\Route;

Route::get('.well-known/openid-configuration', [WellKnownController::class, 'configuration']);
Route::get('.well-known/jwks.json', [WellKnownController::class, 'jwks']);
