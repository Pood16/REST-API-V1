<?php

use App\Http\Controllers\api\v1\admin\CategoryController;
use App\Http\Controllers\Api\V1\Admin\DashboardController;
use App\Http\Controllers\Api\V1\Admin\OrderController;
use App\Http\Controllers\Api\V1\Admin\PaymentController;
use App\Http\Controllers\Api\V1\Admin\ProductController;
use App\Http\Controllers\Api\V1\Admin\UserController;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\RolesController;
use App\Http\Controllers\Api\V2\CartItemsController;
use Faker\Provider\ar_EG\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use \Illuminate\Auth\Middleware\Authorize;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Auth;

// Auth api routes
Route::post('/register', [AuthController::class, 'register'])->name('register');
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::get('/index', [CartItemsController::class, 'index']);

// dashboard routes
Route::get('/v1/admin/dashboard', [DashboardController::class, 'index'])->middleware('auth:sanctum, role:super_admin');


// products routes
Route::apiResource('products', ProductController::class)->middleware(['auth:sanctum', 'role:super_admin|product_manager']);
Route::post('/products/{product}/restore', [ProductController::class, 'restore'])->middleware('auth:sanctum');
Route::delete('/products/{product}/hard-delete', [ProductController::class, 'forceDelete'])->middleware('auth:sanctum');

// users and categories routes
Route::prefix('/v1/admin')->middleware('auth:sanctum')->group(function () {
    /*
    categories routes
    */
    Route::middleware('role:super_admin')->group(function () {
        Route::get('/categories', [CategoryController::class, 'index']);
        Route::post('/categories', [CategoryController::class, 'store']);
        Route::put('/categories/{category}', [CategoryController::class, 'update']);
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy']);
    });
    /*
    users routes
    */
    Route::middleware('role:super_admin|user_manager')->group(function () {
        Route::get('/users', [UserController::class, 'index']);
        Route::post('/users', [userController::class, 'store']);
        Route::put('/users/{user}', [userController::class, 'update']);
        Route::delete('/users/{user}', [userController::class, 'destroy']);
        Route::delete('/users/{user}/delete', [userController::class, 'physicDelete']);
    });
    /*
    payments routes
    */
    Route::middleware('role:super_admin')->group(function () {
        Route::get('/payments', [PaymentController::class, 'index']);
        Route::get('/orders', [OrderController::class, 'index']);
        Route::delete('/orders/{id}', [OrderController::class, 'delete']);
        Route::put('/orders/{id}/status', [OrderController::class, 'updateStatus']);
        Route::get('/orders/{id}', [OrderController::class, 'show']);
    });
});



// setting routes for RolesManagement

Route::get('roles', [RolesController::class, 'index'])->middleware('auth:sanctum');
Route::post('roles/create', [RolesController::class, 'store'])->middleware('auth:sanctum');


// new routes for roles management

Route::get('roles/{id}', [RolesController::class, 'show'])->middleware('auth:sanctum');
Route::put('roles/edit/{id}', [RolesController::class, 'update'])->middleware('auth:sanctum');
Route::delete('roles/delete/{id}', [RolesController::class, 'destroy'])->middleware('auth:sanctum');


// Routes for assigning roles to users
Route::post('assign-roles', [RolesController::class, 'assignRoleToUser'])->middleware('auth:sanctum');
Route::get('users/{id}/roles', [RolesController::class, 'getUserRoles'])->middleware('auth:sanctum');

Route::prefix('/v2/cart')->group(function () {
    Route::post('/add', [CartItemsController::class, 'add']);
    Route::post('/update', [CartItemsController::class, 'update']);
    Route::delete('/remove/{CartItem}', [CartItemsController::class, 'removeFromCart']);
    Route::post('/clear', [CartItemsController::class, 'clear']);
    Route::get('/items', [CartItemsController::class, 'items']);
});

// testing checkout
Route::post('/checkout', [CartItemsController::class, 'checkout'])->name('checkout')->middleware('auth:sanctum');
Route::get('/success', [CartItemsController::class, 'success'])->name('success');
Route::get('/cancel', [CartItemsController::class, 'cancel'])->name('cancel');
