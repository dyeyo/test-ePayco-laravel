<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PersonController;
use App\Http\Controllers\WalletController;

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

Route::post('register-person', [PersonController::class, 'store']);
Route::post('recharge-wallet', [WalletController::class, 'rechargeWallet']);
Route::post('generate-token', [WalletController::class, 'generatePaymentToken']);
Route::post('confirm-payment', [WalletController::class, 'confirmPayment']);
Route::post('wallet/check-balance', [WalletController::class, 'checkBalance']);