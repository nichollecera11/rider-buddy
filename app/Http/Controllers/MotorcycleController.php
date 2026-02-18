<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Motorcycle;
use App\Models\Seller;

class MotorcycleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $motorcycles = Motorcycle::with(['seller', 'brand'])->paginate(10);
        return response()->json($motorcycles);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $seller = Seller::where('user_id', auth()->id())->first();

        if (!$seller) {
            return response()->json(['message' => 'Need to Create Seller Profile'], 403);
        }

        $fields = $request->validate([
            'brand_id' => 'required|exists:brands,id',
            'model' => 'required|string|max:255',
            'year_model' => 'required|integer|min:1900|max:' . (date('Y') + 1),
            'plate_number' => 'required|string|unique:motorcycles,plate_number',
            'mileage' => 'required|numeric|min:0',
            'price' => 'required|numeric|min:0',
            'condition' => 'required|in:new,used',
            'document_status' => 'required|string', // e.g., OR/CR, Open Deed of Sale
            'is_registered' => 'required|boolean', // 1 para sa true, 0 para sa false
            'description' => 'nullable|string',
            'issues' => 'nullable|string',
            'is_sold' => 'boolean',
        ]);

        $fields['seller_id'] = $seller->id;

        $motorcycle = Motorcycle::create($fields);
        return response()->json(['message' => 'Motorcycle Saved', 'data' => $motorcycle], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $motorcycle = Motorcycle::with(['seller', 'brand'])->findOrFail($id); {
            if (!$motorcycle) {
                return response()->json(['message' => 'Motorcycle not found']);
            }
        }
        return response()->json($motorcycle);

    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $motorcycle = Motorcycle::find($id);

        if (!$motorcycle) {
            return response()->json(['message' => 'Motorcycle not found'], 404);
        }

        $seller = Seller::where('user_id', auth()->id())->first();

        if (!$seller || $motorcycle->seller_id !== $seller->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $fields = $request->validate([
            'brand_id' => 'sometimes|required|exists:brands,id',
            'model' => 'sometimes|required|string|max:255',
            'year_model' => 'sometimes|required|integer|min:1900|max:' . (date('Y') + 1),
            'plate_number' => 'sometimes|required|string|unique:motorcycles,plate_number' . $id,
            'mileage' => 'sometimes|required|numeric|min:0',
            'price' => 'sometimes|required|numeric|min:0',
            'condition' => 'sometimes|required|in:new,used',
            'document_status' => 'sometimes|required|string', // e.g., OR/CR, Open Deed of Sale
            'is_registered' => 'sometimes|required|boolean', // 1 para sa true, 0 para sa false
            'description' => 'nullable|string',
            'issues' => 'nullable|string',
            'is_sold' => 'sometimes|boolean',
        ]);
        $motorcycle->update($fields);
        return response()->json(['message' => 'Motorcycle updated Successfully', 'data' => $motorcycle]);
    }



    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $motorcycle = Motorcycle::find($id);
        if (!$motorcycle) {
            return response()->json(['message' => 'Motorcycle not found'], 404);
        }
        $seller = Seller::where('user_id', auth()->id())->first();

        if (!$seller || $motorcycle->seller_id !== $seller->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $motorcycle->delete();
        return response()->json(['message' => 'Motorcycle Deleted Successfully', 'data' => $motorcycle]);
    }
}
