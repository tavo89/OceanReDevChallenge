<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Sales\InvoiceController;
use App\Http\Controllers\Sales\ReceiptController;
use App\Http\Controllers\Accounting\AccountingPeriodBalanceController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Sales Domain Routes
Route::prefix('sales')->group(function () {
    Route::post('/invoices', [InvoiceController::class, 'store']);
    Route::post('/invoices/{id}/cancel', [InvoiceController::class, 'cancel']);
    Route::post('/receipts', [ReceiptController::class, 'store']);
});

// Accounting Domain Routes
Route::prefix('accounting')->group(function () {
    Route::get('/period-balances', [AccountingPeriodBalanceController::class, 'index']);
});
