<?php

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

use App\Http\Controllers\Api\PaymentWebhookController;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Automated Payment Webhooks & Status Check
Route::post('/webhooks/payment', [PaymentWebhookController::class, 'handleWebhook']);
Route::post('/webhooks/simulate-payment', [PaymentWebhookController::class, 'simulatePayment']);
Route::get('/invoices/{id}/status', [PaymentWebhookController::class, 'checkStatus']);
