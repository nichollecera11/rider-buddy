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
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\ConsultationController;
use App\Http\Controllers\UserMotorcycleController;
use App\Http\Controllers\ConsultationMediaController;
use App\Http\Controllers\LTOComplianceController;



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
    Route::apiResource('consultations', ConsultationController::class);
    Route::apiResource('user-motorcycles', UserMotorcycleController::class);
    Route::get('mechanic/consultations', [ConsultationController::class, 'mechanicRequests']);
    // Para sa pag-delete o pag-manage og tagsa-tagsa nga file
    Route::apiResource('consultation-media', ConsultationMediaController::class)->only(['destroy']);
    // --- RIDER ROUTES ---
    // Mag-submit og documents para sa iyang motor
    Route::post('/lto-compliance/{motorcycle_id}', [LTOComplianceController::class, 'store']);
    // I-check ang status sa iyang compliance
    Route::get('/lto-compliance/{motorcycle_id}', [LTOComplianceController::class, 'showByMotorcycle']);

    Route::post('/logout', [AuthController::class, 'logout']);
});


//SUPER DUPER ADMIN
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {

    // 1. Dashboard Stats (Kani ang una para dili ma-intercept sa resource)
    Route::get('/stats', [AdminDashboardController::class, 'index']);

    Route::apiResource('brands', BrandController::class);
    Route::apiResource('categories', CategoryController::class);
    Route::apiResource('parts', PartController::class);
    
    // 2. Admin Reviews (Kini mo-generate og /api/admin/reviews)
    // Direkta na ni tanan: index, show, store, update, destroy para sa Admin
    Route::apiResource('reviews', ReviewController::class);

    // Verification Routes
    Route::patch('/mechanics/{mechanic}/verify', [MechanicController::class, 'verify']);
    Route::patch('/sellers/{seller}/verify', [SellerController::class, 'verify']);
    
    // User Management CRUD
    Route::apiResource('users', UserController::class)->only(['index', 'show', 'update', 'destroy']);

    // 4. Verification Actions (Real-life Rider Buddy features)
    // Route::patch('/mechanics/{id}/verify', [MechanicController::class, 'verify']);

    // Listahan sa tanang pending para ma-review sa admin
        Route::get('/admin/lto-compliance/pending', [LTOComplianceController::class, 'listpending']);
    // Proxy route para sa private images
    Route::get('admin/lto-compliance/image/{id}', [LTOComplianceController::class, 'showImage']);
    //Admin Verification (Approve/Reject)
    Route::patch('admin/lto-compliance/{id}/verify', [LTOComplianceController::class, 'verify']);
});



