<?php

use App\Http\Controllers\api\v1\admin\CategoryController;
use App\Http\Controllers\Api\V1\Admin\DashboardController;
use App\Http\Controllers\Api\V1\Admin\ProductController;
use App\Http\Controllers\Api\V1\Admin\UserController;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use \Illuminate\Auth\Middleware\Authorize;


// Auth api routes
Route::post('/register', [AuthController::class, 'register'])->name('register');
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

// dashboard routes
Route::get('/v1/admin/dashboard', [DashboardController::class, 'index'])->middleware('auth:sanctum, role:super_admin|product_manager|user_manager');


// product routes
Route::apiResource('products', ProductController::class)->middleware('auth:sanctum');
Route::post('/products/{product}/restore', [ProductController::class, 'restore'])->middleware('auth:sanctum');
Route::delete('/products/{product}/hard-delete', [ProductController::class, 'forceDelete'])->middleware('auth:sanctum');

// users and categories routes
Route::prefix('/v1/admin')->middleware('auth:sanctum')->group(function(){
    /*
        *categories routes
    */
    Route::middleware('role:super_admin')->group( function(){
        Route::get('/categories', [CategoryController::class, 'index']);
        Route::post('/categories', [CategoryController::class, 'store']);
        Route::put('/categories/{category}', [CategoryController::class,'update']);
        Route::delete('/categories/{category}', [CategoryController::class,'destroy']);
    });
    /*
        *users routes
    */
    Route::middleware('role:super_admin|user_manager')->group( function (){
        Route::get('/users', [UserController::class, 'index']);
        Route::post('/users', [userController::class, 'store']);
        Route::put('/users/{user}', [userController::class, 'update']);
        Route::delete('/users/{user}', [userController::class, 'destroy']);
        Route::delete('/users/{user}/delete', [userController::class, 'physicDelete']);
    });
});

