<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use App\Models\Part;
use App\Models\Seller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\ImageFile;

class PartController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {

        $query = Part::with(['seller', 'category', 'brand']);

        $query->when($request->search, function ($q, $search) {
            $q->where('part_name', 'like', "%{$search}%");
        });
        $query->when($request->brand_id, function ($q, $brand_id) {
            $q->where('brand_id', $brand_id);
        });
        $query->when($request->category_id, function ($q, $category) {
            $q->where('category_id', $category);
        });
        $query->when($request->condition, function ($q, $cond) {
            $q->where('condition', $cond);
        });
        $query->when($request->oem_compatibility, function ($q, $oem) {
            $q->where(function ($subQuery) use ($oem) {
                $subQuery->where('oem_compatibility', 'like', "%{$oem}%")
                    ->orWhere('is_universal', true);
            });
        });
        $query->when($request->min_price, function ($q, $min) {
            $q->where('price', '>=', $min);
        });
        $query->when($request->max_price, function ($q, $max) {
            $q->where('price', '<=', $max);
        });
        $parts = $query->latest()->paginate(12);

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
            'category_id' => 'required|exists:categories,id',
            'brand_id' => 'required|exists:brands,id',
            'part_name' => 'required|string|max:255',
            'part_number' => 'nullable|string',
            'type' => 'required|in:original,replacement,aftermarket',
            'condition' => 'required|in:new,used',
            'price' => 'required|numeric|min:0',
            'is_negotiable' => 'required|boolean',
            'stock_quantity' => 'required|integer|min:1',
            'oem_compatibility' => 'nullable|string',
            'is_universal' => 'required|boolean',
            'dimensions' => 'nullable|string',
            'is_open_for_swap' => 'required|boolean',
            'swap_preferences' => 'nullable|required_if:is_open_for_swap,true,1|string',
            'description' => 'nullable|string',
            'location' => 'nullable|string',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048'
        ]);

        $fields['seller_id'] = $seller->id;

        DB::beginTransaction();

        try {
            $part = Part::create($fields);

            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $index => $imageFile) {
                    $path = $imageFile->store('images/parts', 'public');
                    $part->images()->create([
                        'path' => $path,
                        'is_primary' => $index === 0,
                    ]);
                }
            }
            DB::commit();

            return response()->json(['message' => 'Parts Saved', 'data' => $part->load('images', 'brand', 'category', 'seller')], 201);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Error Listing Parts',
                'error' => $e->getMessage()
            ], 500);
        }

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

        $seller = Seller::where('user_id', auth()->id())->first();

        if (!$seller || $part->seller_id !== $seller->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $fields = $request->validate([
            'category_id' => 'sometimes|required|exists:categories,id',
            'brand_id' => 'sometimes|required|exists:brands,id',
            'part_name' => 'sometimes|required|string|max:255',
            'part_number' => 'nullable|string',
            'type' => 'sometimes|required|in:original,replacement,aftermarket',
            'condition' => 'sometimes|required|in:new,used',
            'price' => 'sometimes|required|numeric|min:0',
            'is_negotiable' => 'sometimes|required|boolean',
            'stock_quantity' => 'sometimes|required|integer|min:0',
            'oem_compatibility' => 'nullable|string',
            'is_universal' => 'sometimes|required|boolean',
            'dimensions' => 'nullable|string',
            'is_open_for_swap' => 'sometimes|required|boolean',
            'swap_preferences' => 'nullable|required_if:is_open_for_swap,true,1|string',
            'description' => 'nullable|string',
            'location' => 'nullable|string',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'remove_images' => 'nullable|array',
            'primary_image_id' => 'nullable|integer'
        ]);

        DB::beginTransaction();
        try {
            // Update Text Fields
            $part->update($fields);

            // 1. Delete Selected Images
            if ($request->has('remove_images')) {
                $imagesToDelete = $part->images()->whereIn('id', $request->remove_images)->get();
                foreach ($imagesToDelete as $img) {
                    Storage::disk('public')->delete($img->path);
                    $img->delete();
                }
            }

            // 2. Upload New Images
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $imageFile) { // Gitangtang ang ";" diri
                    $path = $imageFile->store('images/parts', 'public');
                    $part->images()->create([
                        'path' => $path,
                        'is_primary' => false,
                    ]);
                }
            }

            // 3. Set Primary Image
            if ($request->has('primary_image_id')) {
                $part->images()->update(['is_primary' => false]); // Correct column name
                $part->images()->where('id', $request->primary_image_id)->update(['is_primary' => true]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Parts Updated Successfully',
                'data' => $part->load('images', 'brand', 'category')
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error Updating Parts',
                'error' => $e->getMessage()
            ], 500);
        }
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
