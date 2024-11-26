<?php

use App\Http\Controllers\Api\v1\AuthController;
use App\Http\Controllers\Api\v1\ItemController;
use App\Http\Controllers\Api\v1\PurchaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


Route::middleware('auth:sanctum')->group(function () {
    Route::post('/items/buy', [PurchaseController::class, 'buyItem'])->name('items.buy');
    Route::post('/items/rent', [PurchaseController::class, 'rentItem'])->name('items.rent');
    Route::post('/items/rent/extend', [PurchaseController::class, 'extendRent'])->name('items.rent.extend');

    Route::get('/items/{id}/status', [PurchaseController::class, 'getStatus'])->name('items.get-status');
    Route::get('/items/my-purchases', [PurchaseController::class, 'myPurchases'])->name('items.my-purchases');
});

Route::post('/register', [AuthController::class, 'register'])->name('api.register');
Route::post('/login', [AuthController::class, 'login'])->name('api.login');

Route::get('/items', [ItemController::class, 'index'])->name('api.items.index');
Route::get('/items/{id}', [ItemController::class, 'show'])->name('api.items.show');
