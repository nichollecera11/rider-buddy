<?php


use App\Http\Controllers\BrandController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\MechanicController;
use App\Http\Controllers\ReviewController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MotorcycleController;
use App\Http\Controllers\PartController;
use App\Http\Controllers\SellerController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Admin\AdminDashboardController;
/*
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum'); */
// --- Public Routes (Walay Login) ---
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Mao ni ang "Clean Way" para sa mga listahan nga pwedeng makita sa tanan
Route::apiResource('motorcycles', MotorcycleController::class)->only(['index', 'show']);
Route::apiResource('parts', PartController::class)->only(['index', 'show']);
Route::apiResource('mechanics', MechanicController::class)->only(['index', 'show']);
Route::apiResource('sellers', SellerController::class)->only(['index', 'show']);
Route::apiResource('categories', CategoryController::class)->only(['index']);
Route::apiResource('brands', BrandController::class)->only(['index']);
Route::apiResource('reviews', controller: ReviewController::class)->only(['index', 'show']);

// --- Protected Routes (Kinahanglan Login) ---
Route::group(['middleware' => ['auth:sanctum']], function () {

    // Ang apiResource automatic na maghimo sa store, update (PUT/PATCH), ug destroy
    Route::apiResource('motorcycles', MotorcycleController::class)->except(['index', 'show']);
    Route::apiResource('parts', PartController::class)->except(['index', 'show']);
    Route::apiResource('mechanics', MechanicController::class)->except(['index', 'show']);
    Route::apiResource('sellers', SellerController::class)->except(['index', 'show']);
    Route::apiResource('reviews', ReviewController::class)->except(['index', 'show']);

    Route::post('/logout', [AuthController::class, 'logout']);
});

// routes/api.php

// routes/api.php

// routes/api.php

Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {

    // 1. Dashboard Stats (Kani ang una para dili ma-intercept sa resource)
    Route::get('/stats', [AdminDashboardController::class, 'index']);

    // 2. Admin Reviews (Kini mo-generate og /api/admin/reviews)
    // Direkta na ni tanan: index, show, store, update, destroy para sa Admin
    Route::apiResource('reviews', ReviewController::class);

    // 3. User Management (Puhon kon ready na ka)
    // Route::apiResource('users', UserController::class);

    // 4. Verification Actions (Real-life Rider Buddy features)
    // Route::patch('/mechanics/{id}/verify', [MechanicController::class, 'verify']);
});