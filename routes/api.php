<?php


use App\Http\Controllers\BrandController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\MechanicController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MotorcycleController;
use App\Http\Controllers\PartController;
use App\Http\Controllers\SellerController;
/*
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum'); */

Route::get('/motorcycles', [MotorcycleController::class, 'index']);
Route::get('/motorcycles/{id}', [MotorcycleController::class, 'index']);
Route::get('/parts', [PartController::class, 'index']);
Route::get('/parts/{id}', [PartController::class, 'index']);
Route::get('/mechanics', [MechanicController::class, 'index']);
Route::get('/mechanics/{id}', [MechanicController::class, 'show']);
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/brands', [BrandController::class, 'index']);
Route::get('/sellers',[SellerController::class, 'index']);
Route::get('/sellers/{id}',[SellerController::class, 'show']);