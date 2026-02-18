<?php


use App\Http\Controllers\BrandController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\MechanicController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MotorcycleController;
use App\Http\Controllers\PartController;
use App\Http\Controllers\SellerController;
use App\Http\Controllers\AuthController;
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


// --- Protected Routes (Kinahanglan Login) ---
Route::group(['middleware' => ['auth:sanctum']], function () {
    
    // Ang apiResource automatic na maghimo sa store, update (PUT/PATCH), ug destroy
    Route::apiResource('motorcycles', MotorcycleController::class)->except(['index', 'show']);
    Route::apiResource('parts', PartController::class)->except(['index', 'show']);
    Route::apiResource('mechanics', MechanicController::class)->except(['index', 'show']);
    Route::apiResource('sellers', SellerController::class)->except(['index', 'show']);

    Route::post('/logout', [AuthController::class, 'logout']);
});