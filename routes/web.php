<?php

declare(strict_types=1);

use App\Http\Controllers\FormController;
use App\Services\OidcService;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
Route::get('/', [FormController::class, 'entrypoint'])->name('entrypoint');

Route::post('/', [FormController::class, 'submit'])->name('form.submit');
Route::post('/confirm', [FormController::class, 'confirmationSubmit'])->name('confirmation.submit');


/*
 * OIDC endpoints
 */
Route::get('/oidc/authorize', function (Request $request, OidcService $oidcService) {
    return $oidcService->authorize($request);
});

Route::post('/oidc/accesstoken', function (Request $request, OidcService $oidcService) {
    return $oidcService->accessToken($request);
});
