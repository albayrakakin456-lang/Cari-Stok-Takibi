<?php

use App\Http\Controllers\ContactController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\CashTransactionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AuthController;

/*
|--------------------------------------------------------------------------
| Misafir (Giriş Yapmamış) Kullanıcı Rotaları
|--------------------------------------------------------------------------
| Zaten giriş yapmış bir kullanıcı bu sayfalara erişmeye çalışırsa 
| guest middleware'i onu otomatik olarak ana sayfaya (dashboard) yönlendirir.
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

/*
|--------------------------------------------------------------------------
| Korumalı Sistem Rotaları (Sadece Giriş Yapmış Kullanıcılar)
|--------------------------------------------------------------------------
| auth middleware'i: Giriş yapmamış bir istek gelirse işlemi durdurur 
| ve kullanıcıyı doğrudan /login sayfasına fırlatır.
*/
Route::middleware('auth')->group(function () {
    // Ana Sayfa: Yönetici Gösterge Paneli
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Modül Rotaları (Sadece Controller'da tanımlı olan metotlar açık)
    Route::resource('contacts', ContactController::class)->only(['index', 'create', 'store', 'show']);
    Route::resource('products', ProductController::class)->only(['index', 'create', 'store', 'show']);
    Route::resource('sales', SaleController::class)->only(['index', 'create', 'store', 'show', 'destroy']);
    Route::resource('purchases', PurchaseController::class)->only(['index', 'create', 'store', 'show', 'destroy']);
    Route::resource('cash', CashTransactionController::class)->only(['index', 'create', 'store']);

    // Güvenli Çıkış (Logout)
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});

/*
|--------------------------------------------------------------------------
| Çoklu Dil Değiştirme Rotası (TR / EN)
|--------------------------------------------------------------------------
*/
Route::get('/lang/{locale}', function (string $locale) {
    if (in_array($locale, ['tr', 'en'])) {
        session(['locale' => $locale]);
    }
    return redirect()->back();
})->name('lang.switch');


