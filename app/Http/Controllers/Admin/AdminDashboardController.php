<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Mechanic;
use App\Models\Seller;
use App\Models\Review;

class AdminDashboardController extends Controller
{
    public function index() {
        // 1. Mag-ihap ta sa kinatibuk-ang users ug resources
        $totalUsers = User::count();
        $totalMechanics = Mechanic::count();
        $totalSellers = Seller::count();
        $totalReviews = Review::count();

        // 2. Business Logic: Pila ang kinahanglan i-verify sa Admin?
        // (Assuming naa kay 'is_verified' column nga boolean/tinyint)
        $pendingMechanics = Mechanic::where('is_verified', false)->count();
        $pendingSellers = Seller::where('is_verified', false)->count();

        // 3. Performance Metric: Average Rating sa tibuok system
        // round() nato para dili taas kaayo ang decimal sa frontend
        $avgRating = Review::avg('rating') ?? 0;
        $formattedRating = round($avgRating, 1);

        return response()->json([
        'status'=> 'success',
        'message'  => 'Admin Dashboard data retrieved',
        'data' => [
            'overview' => [
                'users' => $totalUsers,
                'mechanics' => $totalMechanics,
                'sellers' => $totalSellers,
                'reviews' => $totalReviews
            ],
            'pending_actions' => [
                'mechanics' => $pendingMechanics,
                'sellers' => $pendingSellers
            ],
            'metrics' => [
                'system_average_rating' => $formattedRating
            ]
        ]
        ], 200);
    }
}
