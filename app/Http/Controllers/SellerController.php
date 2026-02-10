<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Seller;

class SellerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $sellers = Seller::with(['user:id,name'])->withCount(['motorcycles', 'parts'])->get();
        return response()->json($sellers);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $seller = Seller::with([
            'user:id,name',
            'motorcycles.brand',
            'motorcycles.category',
            'parts.brand',
            'parts.category'
        ])
            ->withCount(['motorcycles', 'parts'])
            ->find($id);

        if (!$seller) {
            return response()->json(['message' => 'Seller/Shop Not Found'], 404);
        }

        return response()->json($seller);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
