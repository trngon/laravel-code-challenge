<?php

use App\Http\Controllers\DebitCardController;
use App\Http\Controllers\DebitCardTransactionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('users/register', [\App\Http\Controllers\UserController::class, 'register']);
Route::post('users/login', [\App\Http\Controllers\UserController::class, 'login']);

Route::middleware('auth:api')
    ->group(function () {
        // Debit card endpoints
        Route::get('debit-cards', [DebitCardController::class, 'index']);
        Route::post('debit-cards', [DebitCardController::class, 'store']);
        Route::get('debit-cards/{debitCard}', [DebitCardController::class, 'show']);
        Route::put('debit-cards/{debitCard}', [DebitCardController::class, 'update']);
        Route::delete('debit-cards/{debitCard}', [DebitCardController::class, 'destroy']);

        // Debit card transactions endpoints
        Route::get('debit-card-transactions', [DebitCardTransactionController::class, 'index']);
        Route::post('debit-card-transactions', [DebitCardTransactionController::class, 'store']);
        Route::get('debit-card-transactions/{debitCardTransaction}', [DebitCardTransactionController::class, 'show']);

        //test
        Route::get('users/test', [\App\Http\Controllers\UserController::class, 'test']);
    });
