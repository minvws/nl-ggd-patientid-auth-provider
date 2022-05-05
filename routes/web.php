<?php

declare(strict_types=1);

use App\Http\Controllers\FormController;
use Illuminate\Support\Facades\Route;

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

Route::get('/', function () {
    return view('form');
})->name('form');

Route::post('/', [FormController::class, 'submit'])->name('form.submit');

Route::post('/confirm', [FormController::class, 'confirmationSubmit'])->name('confirmation.submit');
