<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\SalesController;
use App\Http\Controllers\Api\StockCorrectionController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\UserManagementController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Role:
|   owner — akses penuh (laporan, koreksi stok, CRUD master data, retur)
|   kasir — POS (buat penjualan), riwayat, lihat master data (read-only)
|
| Semua route di bawah butuh token valid (auth:sanctum).
*/

// Sanctum CSRF cookie
Route::get('/sanctum/csrf-cookie', function () {
    return response()->json(['message' => 'CSRF cookie set']);
});

// Auth routes
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::get('/user', [AuthController::class, 'user'])->middleware('auth:sanctum');

// ============================================================
// Owner only — Dashboard, Koreksi Stok, Laporan
// ============================================================
Route::middleware('auth:sanctum', 'role:owner')->group(function () {
    Route::put('/account/credentials', [UserManagementController::class, 'updateOwnCredentials']);
    Route::get('/cashiers', [UserManagementController::class, 'cashiers']);
    Route::put('/cashiers/{cashier}', [UserManagementController::class, 'updateCashierCredentials']);

    Route::get('/dashboard', [DashboardController::class, 'index']);

    Route::get('/stock-corrections', [StockCorrectionController::class, 'index']);
    Route::post('/stock-corrections', [StockCorrectionController::class, 'store']);

    Route::get('/reports/sales', [ReportController::class, 'sales']);
    Route::get('/reports/sales/export', [ReportController::class, 'exportSales']);
    Route::get('/reports/profit-loss', [ReportController::class, 'profitLoss']);
    Route::get('/reports/profit-loss/export', [ReportController::class, 'exportProfitLoss']);
});

// ============================================================
// Owner + Kasir — baca data & transaksi POS
// ============================================================
Route::middleware('auth:sanctum')->group(function () {
    // Master data: hanya index (list) & show (detail)
    Route::apiResource('/categories', CategoryController::class)
        ->only(['index', 'show']);
    Route::apiResource('/products', ProductController::class)
        ->only(['index', 'show']);
    Route::apiResource('/customers', CustomerController::class)
        ->only(['index', 'show']);

    // Penjualan: kasir boleh list, lihat detail, dan buat transaksi
    Route::apiResource('/sales', SalesController::class)
        ->only(['index', 'show', 'store']);
});

// ============================================================
// Owner only — tulis master data & proses retur/status
// ============================================================
Route::middleware('auth:sanctum', 'role:owner')->group(function () {
    Route::apiResource('/categories', CategoryController::class)
        ->only(['store', 'update', 'destroy']);
    Route::apiResource('/products', ProductController::class)
        ->only(['store', 'update', 'destroy']);
    Route::apiResource('/customers', CustomerController::class)
        ->only(['store', 'update', 'destroy']);

    // Retur / ubah status transaksi
    Route::put('/sales/{sale}/status', [SalesController::class, 'update']);
    Route::apiResource('/sales', SalesController::class)
        ->only(['update', 'destroy']);
});
