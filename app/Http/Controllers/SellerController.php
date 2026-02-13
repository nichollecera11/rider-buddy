<?php

namespace App\Http\Controllers;

use Exception;
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
        $fields = $request->validate([
            'image' => 'nullable|string',
            'shop_name' => 'nullable|string',
            'address' => 'required|string',
            'contact_number' => 'required|string|min:11|max:255',
            'business_permit_no' => 'nullable|string',
            'has_delivery' => 'boolean',
        ]);

        $userId = auth()->id();

        try {
            $seller = Seller::create([
                'user_id' => $userId,
                'image' => $fields['image'] ?? null,
                'shop_name' => $fields['shop_name'] ?? null,
                'address' => $fields['address'],
                'contact_number' => $fields['contact_number'],
                'business_permit_no' => $fields['business_permit_no'] ?? null,
                'has_delivery' => $fields['has_delivery'] ?? false,
            ]);

            return response()->json(['message' => 'Seller profile created', 'data' => $seller], 201);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'You already have a seller profile',
                'error' => $e->getMessage()
            ], 400);
        }
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
