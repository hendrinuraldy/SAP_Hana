<?php

use App\Http\Controllers\Auth\SapLoginController;
use App\Http\Controllers\SapItemController;
use App\Http\Controllers\SapBusinessPartnerController;
use App\Http\Controllers\PickingListController;
use App\Http\Controllers\SalesOrderController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\AdHocQueryController;
use Illuminate\Support\Facades\Route;

Route::get('/login', [SapLoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [SapLoginController::class, 'login']);
Route::post('/logout', [SapLoginController::class, 'logout'])->name('logout');

Route::middleware('sap.auth')->group(function () {
    Route::get('/dashboard', fn () => view('dashboard'));

    Route::get('/items', [SapItemController::class, 'index']);
    Route::get('/business-partners', [SapBusinessPartnerController::class, 'index']);

    Route::get('/reports/picking-list', [PickingListController::class, 'index']);
    Route::get('/reports/sales-order', [SalesOrderController::class, 'index']);
    Route::get('/reports/purchase-order', [PurchaseOrderController::class, 'index']);

    // Fitur SQL manual: SELECT bebas ke database SAP HANA. Ini paling berisiko
    // (bisa baca tabel apapun yang ke-authorize user SAP), jadi dibatasi
    // tambahan pakai middleware 'sap.admin' -- hanya username SAP yang
    // terdaftar di SAP_ADHOC_ADMIN_USERS (.env) yang bisa akses.
    Route::middleware('sap.admin')->group(function () {
        Route::get('/reports/adhoc-query', [AdHocQueryController::class, 'index']);
        Route::post('/reports/adhoc-query', [AdHocQueryController::class, 'run']);
    });
});