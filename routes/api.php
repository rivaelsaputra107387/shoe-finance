<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PurchaseRequestController;
use App\Http\Middleware\VerifyWorkshopApiToken;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('v1')->middleware(VerifyWorkshopApiToken::class)->group(function () {
    Route::post('/purchase-requests', [PurchaseRequestController::class, 'receive']);
});
