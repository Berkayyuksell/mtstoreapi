<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\ModuleController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SyncController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Auth
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/user', fn(Request $request) => $request->user());

    Route::post('/logout', [AuthController::class, 'logout']);

    // Modüller — tüm giriş yapmış kullanıcılar
    Route::get('/modules', [ModuleController::class, 'index']);

    // Ürün arama — tüm giriş yapmış kullanıcılar
    Route::get('/products', [ProductController::class, 'getProductList']);

    // Sync — sadece admin kullanıcılar
    Route::middleware('admin')->group(function () {
        Route::post('/projects/{project}/sync', [SyncController::class, 'syncProject']);
    });

});
