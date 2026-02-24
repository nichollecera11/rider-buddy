<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use App\Models\Motorcycle;
use App\Models\Seller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class MotorcycleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {

        $query = Motorcycle::with(['brand', 'images', 'seller.user:id,name']);

        $query->when($request->search, function ($q, $search) {
            $q->where('model', 'like', "%{$search}%");
        });
        $query->when($request->brand_id, function ($q, $brand_id) {
            $q->where('brand_id', $brand_id);
        });
        $query->when($request->min_price, function ($q, $min) {
            $q->where('price', '>=', $min);
        });
        $query->when($request->max_price, function ($q, $max) {
            $q->where('price', '<=', $max);
        });
        $query->when($request->condition, function ($q, $cond) {
            $q->where('condition', $cond);
        });
        $query->when($request->transmission, function ($q, $trans) {
            $q->where('transmission', $trans);
        });
        $query->when($request->is_registered, function ($q, $reg) {
            $q->where('is_registered', $reg);
        });
        $query->when($request->is_negotiable, function ($q, $neg) {
            $q->where('is_negotiable', $neg);
        });
        $query->when($request->is_open_for_swap, function ($q, $swap) {
            $q->where('is_open_for_swap', $swap);
        });


        $motorcycles = $query->latest()->paginate(12);
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
            // 'seller_id' => 'required|exists:sellers,id',
            'brand_id' => 'required|exists:brands,id',
            'model' => 'required|string|max:255',
            'year_model' => 'required|integer|min:1900|max:' . (date('Y') + 1),
            'color' => 'required|string',
            'plate_number' => 'required|string|unique:motorcycles,plate_number',
            'mileage' => 'required|numeric|min:0',
            'engine_capacity' => 'nullable|integer',
            'transmission' => 'nullable|in:manual,automatic,semi_automatic',
            'fuel_type' => 'nullable|in:gasoline,electric',
            'current_location' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'is_negotiable' => 'required|boolean',
            'condition' => 'required|in:brand_new,second_hand',
            'document_status' => 'required|in:complete_original,orig_cr_xerox_or,xerox_only,no_papers', // e.g., OR/CR, Open Deed of Sale
            'is_registered' => 'required|boolean', // 1 para sa true, 0 para sa false
            'is_open_for_swap' => 'required|boolean',
            'swap_preferences' => 'nullable|required_if:is_open_for_swap,1|string',
            'description' => 'nullable|string',
            'issues' => 'nullable|string',
            'is_sold' => 'boolean',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,webp|max:2048'
        ]);

        $fields['seller_id'] = $seller->id;

        DB::beginTransaction();
        try {
            $motorcycle = Motorcycle::create($fields);

            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $index => $imageFile) {
                    $path = $imageFile->store('images/motorcycles', 'public');
                    $motorcycle->images()->create([
                        'path' => $path,
                        'is_primary' => $index === 0,
                    ]);
                }

            }
            DB::commit(); //save in database

            return response()->json(['message' => 'Motorcycle Saved', 'data' => $motorcycle->load('images', 'brand')], 201);

        } catch (Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Error listing Motorcycle',
                'error' => $e->getMessage()
            ], 500);
        }

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
            'color' => 'sometimes|required|string',
            'plate_number' => 'sometimes|required|string|unique:motorcycles,plate_number,' . $id,
            'mileage' => 'sometimes|required|numeric|min:0',
            'engine_capacity' => 'nullable|string',
            'transmission' => 'nullable|in:manual,automatic,semi_automatic',
            'fuel_type' => 'nullable|in:gasoline,electric',
            'current_location' => 'nullable|string',
            'price' => 'sometimes|required|numeric|min:0',
            'condition' => 'sometimes|required|in:brand_new, second_hand',
            'document_status' => 'sometimes|required|in:complete_original,orig_cr_xerox_or,xerox_only,no_papers',
            'is_registered' => 'sometimes|required|boolean', // 1 para sa true, 0 para sa false
            'is_open_for_swap' => 'sometimes|required|boolean',
            'swap_preferences' => 'nullable|required_if:is_open_for_swap,1|string',
            'description' => 'nullable|string',
            'issues' => 'nullable|string',
            'is_sold' => 'sometimes|boolean',
        ]);

        DB::beginTransaction();
        try {
            //Update Text Field
            $motorcycle->update($fields);
            //Pang delete sa image
            if ($request->has('remove_images')) {
                $imagesToDelete = $motorcycle->images()->whereIn('id', $request->remove_images)->get();
                foreach ($imagesToDelete as $img) {
                    Storage::disk('public')->delete($img->path);
                    $img->delete();
                }
            }
            //Bag o na mga upload na image file
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $imageFile) {
                    $path = $imageFile->store('images/motorcycles', 'public');
                    $motorcycle->images()->create([
                        'path' => $path,
                        'is_primary' => false,
                    ]);
                }
            }
            //Primary Image Cover Photo
            if ($request->has('primary_image_id')) {
                $motorcycle->images()->update(['is_primary' => true]);
                $motorcycle->images()->where('id', $request->primary_image_id)->update(['is_primary', true]);
            }

            DB::commit();
            return response()->json(['message' => 'Motorcycle updated Successfully', 'data' => $motorcycle]);

        } catch (Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Update Failed',
                'error' => $e->getMessage()
            ], 500);
        }
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

        DB::beginTransaction();

        try {
            $images = $motorcycle->images;
            foreach ($images as $image) {
                if (Storage::disk('public')->exists($image->path)) {
                    Storage::disk('public')->delete($image->path);
                }
            }
            $motorcycle->images()->delete();
            $motorcycle->delete();
            DB::commit();
            return response()->json(['message' => 'Motorcycle files Deleted Successfully'], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Delete Motorcycle Profile Failed', 'error' => $e->getMessage()], 500);
        }
    }
}
