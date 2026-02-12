<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Part;

class PartController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $parts = Part::with(['seller', 'category', 'brand'])->get();
        return response()->json($parts);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $fields = $request->validate([
            'seller_id' => 'required|exists:sellers,id',
            'category_id' => 'required|exists:categories,id',
            'brand_id' => 'required|exists:brands,id',
            'part_name' => 'required|string|max:255',
            'condition' => 'required|in:new,used',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'compatibility' => 'required|string',


        ]);
        $part = Part::create($fields);

        return response()->json(['message' => 'Parts Saved', 'data' => $part], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $parts = Part::with(['seller', 'category', 'brand'])->find($id);
        if (!$parts) {
            return response()->json(['message' => 'Parts not found']);
        }
        return response()->json($parts);

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
