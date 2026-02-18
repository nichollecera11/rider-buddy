<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Part;
use App\Models\Seller;

class PartController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {

        $query = Part::with(['seller', 'category', 'brand']);
        if ($request->has('search')) {
            $query->where('part_name', 'like', '%' . $request->search . '%');
        }
        if ($request->has('brand_id')) {
            $query->where('brand_id', $request->brand_id);
        }
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }
        if ($request->has('part_name')) {
            $query->where('part_name', $request->part_name);
        }
        if ($request->has('condition')) {
            $query->where('condition', $request->condition);
        }
        if ($request->has('compatibility')) {
            $query->where('compatibility', $request->compatibility);
        }
        if ($request->has('min_price')) {
            $query->where('price', '>=', $request->min_price);
        }
        if ($request->has('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }
        $parts = $query->latest()->paginate(10);

        return response()->json($parts);

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //kwaon sa nato una ang seller para dili nata mag butang ug seller sa seeding
        $seller = Seller::where('user_id', auth()->id())->first();

        if (!$seller) {
            return response()->json(['message' => 'Need to Create Seller Profile'], 403);
        }

        $fields = $request->validate([
            // 'seller_id' => 'required|exists:sellers,id',
            'category_id' => 'required|exists:categories,id',
            'brand_id' => 'required|exists:brands,id',
            'part_name' => 'required|string|max:255',
            'condition' => 'required|in:new,used',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'compatibility' => 'required|string',


        ]);

        $fields['seller_id'] = $seller->id;

        $part = Part::create($fields);

        return response()->json(['message' => 'Parts Saved', 'data' => $part], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $part = Part::with(['seller', 'category', 'brand'])->find($id);
        if (!$part) {
            return response()->json(['message' => 'Parts not found']);
        }
        return response()->json($part);

    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {

        $part = Part::find($id);

        if (!$part) {
            return response()->json(['message' => 'Parts not found'], 404);
        }

        // 2. SECURITY CHECK: Kinahanglan ang Seller sa part mao ang tag-iya sa shop
        // Kay ang User_id naa man sa Seller profile, kailangan nato i-check ang relation
        $seller = Seller::where('user_id', auth()->id())->first();

        if (!$seller || $part->seller_id !== $seller->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $fields = $request->validate([
            'category_id' => 'sometimes|required|exists:categories,id',
            'brand_id' => 'sometimes|required|exists:brands,id',
            'part_name' => 'sometimes|required|string|max:255',
            'condition' => 'sometimes|required|in:new,used',
            'description' => 'sometimes|required|string',
            'price' => 'sometimes|required|numeric|min:0',
            'stock_quantity' => 'sometimes|required|integer|min:0',
            'compatibility' => 'sometimes|required|string',
        ]);

        $part->update($fields);
        return response()->json(['message' => 'Parts Updated Successfully', 'data' => $part]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {

        $part = Part::find($id);

        if (!$part) {
            return response()->json(['message' => "Parts not found"], 404);
        }

        $seller = Seller::where('user_id', auth()->id())->first();

        if (!$seller || $part->seller_id !== $seller->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $part->delete();

        return response()->json(['message' => ' Parts Deleted Successfully', 'data' => $part], 200);
    }
}
