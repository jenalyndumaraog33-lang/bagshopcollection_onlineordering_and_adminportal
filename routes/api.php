<?php

use App\Http\Controllers\Api\AuthTokenController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function (): void {
    Route::post('/auth/token', [AuthTokenController::class, 'store'])->middleware('throttle:6,1');
    Route::post('/auth/register', [AuthTokenController::class, 'register'])->middleware('throttle:6,1');
    Route::post('/auth/password/forgot', [AuthTokenController::class, 'forgotPassword'])->middleware('throttle:6,1');
    Route::post('/auth/password/reset', [AuthTokenController::class, 'resetPassword'])->middleware('throttle:6,1');
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/categories/{category}', [CategoryController::class, 'show']);
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/{product:slug}', [ProductController::class, 'show']);

    Route::middleware(['auth:sanctum', 'active'])->group(function (): void {
        Route::get('/user', [AuthTokenController::class, 'user']);
        Route::delete('/auth/token', [AuthTokenController::class, 'destroy']);
        Route::get('/cart', [CartController::class, 'show']);
        Route::post('/cart/items', [CartController::class, 'store']);
        Route::patch('/cart/items/{cartItem}', [CartController::class, 'update']);
        Route::delete('/cart/items/{cartItem}', [CartController::class, 'destroy']);
        Route::apiResource('/orders', OrderController::class)->only(['index', 'store', 'show']);
    });

    Route::middleware(['auth:sanctum', 'admin'])
        ->prefix('admin')
        ->name('admin.')
        ->group(function (): void {
            Route::get('/carts', [CartController::class, 'adminIndex'])->name('carts.index');
            Route::get('/carts/{shoppingCart}', [CartController::class, 'adminShow'])->name('carts.show');
            Route::apiResource('products', ProductController::class);
            Route::apiResource('categories', CategoryController::class)->except(['index', 'show']);
            Route::get('/orders', [OrderController::class, 'adminIndex'])->name('orders.index');
            Route::patch('/orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.status');
            Route::get('/users', [UserController::class, 'index'])->name('users.index');
            Route::patch('/users/{user}', [UserController::class, 'update'])->name('users.update');
            Route::get('/reports', ReportController::class)->name('reports');
        });
});
