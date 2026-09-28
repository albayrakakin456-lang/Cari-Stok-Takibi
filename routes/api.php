<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\WebhookDeliveryController;
use App\Http\Controllers\Api\WebhookEndpointController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Dış Servis API v1
|--------------------------------------------------------------------------
| Tokenlar yalnızca oturum açılmış web panelindeki API Tokenları ekranından
| üretilir. Tüm API rotalarında Sanctum kimlik doğrulaması zorunludur.
*/
Route::prefix('v1')->group(function () {
    Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);

        // Destek verileri yalnızca okunabilir olarak dışarı açılır.
        Route::middleware('abilities:api:read')->group(function () {
            Route::get('/products', [ProductController::class, 'index']);
            Route::get('/contacts', [ContactController::class, 'index']);
            Route::get('/invoices', [InvoiceController::class, 'index']);
            Route::get('/invoices/{id}', [InvoiceController::class, 'show'])->whereNumber('id');
            Route::get('/webhook-endpoints', [WebhookEndpointController::class, 'index']);
            Route::get('/webhook-deliveries', [WebhookDeliveryController::class, 'index']);
        });

        // Veri oluşturan veya değiştiren işlemler ayrıca yazma yetkisi gerektirir.
        Route::middleware('abilities:api:write')->group(function () {
            Route::post('/contacts', [ContactController::class, 'store']);
            Route::post('/products', [ProductController::class, 'store']);
            Route::post('/invoices', [InvoiceController::class, 'store']);
            Route::patch('/invoices/{id}', [InvoiceController::class, 'update'])->whereNumber('id');
            Route::delete('/invoices/{id}', [InvoiceController::class, 'destroy'])->whereNumber('id');
            Route::post('/webhook-endpoints', [WebhookEndpointController::class, 'store']);
            Route::patch('/webhook-endpoints/{id}', [WebhookEndpointController::class, 'update'])->whereNumber('id');
            Route::delete('/webhook-endpoints/{id}', [WebhookEndpointController::class, 'destroy'])->whereNumber('id');
            Route::post('/webhook-endpoints/{id}/test', [WebhookEndpointController::class, 'test'])->whereNumber('id');
        });
    });
});
