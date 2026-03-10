<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use App\Models\Seller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SellerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        //DEBUG
        // return Seller::where('user_id', auth()->id())->get();

        // $sellers = Seller::with(['user:id,name', 'images '])->withCount(['motorcycles', 'parts'])->get();
        // return response()->json($sellers);

        $query = Seller::with(['user:id,name', 'images']);

        if ($request->lat && $request->lng) {
            $query->withDistance($request->lat, $request->lng)
                ->orderBy('distance', 'asc');
        } else {
            $query->latest();
        }

        $query->when($request->search, function ($q, $search) {
            $q->where(function ($inner) use ($search) {
                $inner->where('shop_name', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        })->when($request->is_official, function ($q) {
            $q->where('is_official_store', true);
        })->when($request->has_delivery, function ($q) {
            $q->where('has_delivery', true);
        })->when($request->is_24_7, function ($q) {
            $q->where('is_24_7', true);
        });

        $sellers = $query->latest()->paginate(10);
        return response()->json($sellers);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $fields = $request->validate([
            'user_id' => auth()->id(),
            'shop_name' => 'nullable|string',
            'address' => 'required|string',
            'contact_number' => 'required|string|min:11|max:255',
            'business_permit_no' => 'nullable|string',
            'has_delivery' => 'boolean',
            'description' => 'nullable|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'is_24_7' => 'boolean'
        ]);

        $userId = auth()->id();

        try {
            $seller = Seller::create([
                'user_id' => $userId,
                'shop_name' => $fields['shop_name'] ?? null,
                'address' => $fields['address'],
                'contact_number' => $fields['contact_number'],
                'business_permit_no' => $fields['business_permit_no'] ?? null,
                'has_delivery' => $request->boolean('has_delivery'),
                'description' => $fields['description'] ?? null,
                'latitude' => $fields['latitude'] ?? null,
                'longitude' => $fields['longitude'] ?? null,
                'is_24_7' => $request->boolean('is_24_7')
            ]);

            if ($request->hasFile('image')) {
                $path = $request->file('image')->store('sellers/profiles', 'public');
                $seller->media()->create([
                    'file_path' => $path,
                    'file_type' => 'image',
                    'collection' => 'logo'
                ]);
            }

            return response()->json(['message' => 'Seller profile created', 'data' => $seller->load('images')], 201);
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
        $seller = Seller::findOrFail($id);

        if ($seller->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized User Profile'], 403);
        }

        $fields = $request->validate([
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'shop_name' => 'nullable|string',
            'address' => 'sometimes|required|string',
            'contact_number' => 'sometimes|required|string',
            'business_permit_no' => 'nullable|string',
            'has_delivery' => 'sometimes|boolean',
            'description' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'is_24_7' => 'sometimes|boolean'
        ]);

        // Magsugod na ta sa proteksyon
        DB::beginTransaction();

        try {
            $dbFields = $request->except(['image']);
            $seller->update($dbFields);

            if ($request->hasFile('image')) {
                $path = $request->file('image')->store('sellers/profiles', 'public');
                $seller->media()->updateOrCreate(
                    ['collection' => 'logo'],
                    ['file_path' => $path, 'file_type' => 'image']
                );
            }

            DB::commit();

            return response()->json([
                'message' => 'Seller Profile Updated Successfully',
                'data' => $seller->load('image'),
                201
            ]);

        } catch (Exception $e) {
            DB::rollBack(); // I-undo ang tanan kon naay error (e.g. disk full, DB timeout)

            Log::error("Seller Update Error: " . $e->getMessage());

            return response()->json([
                'message' => 'Failed to Update Seller profile.',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Server Error'
            ], 500);
        }
    }
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $seller = Seller::find($id);

        if (!$seller) {
            return response()->json(['message' => 'Seller not found'], 404);
        }

        if ($seller->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        DB::beginTransaction();
        try {
            $images = $seller->images;

            foreach ($images as $image) {
                if (Storage::disk('public')->exists($image->path)) {
                    Storage::disk('public')->delete($image->path);
                }
            }

            $seller->images()->delete();

            $seller->delete();

            DB::commit();

            return response()->json(['message' => 'Seller Profile Successfully Deleted'], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Delete Failed', 'error' => $e->getMessage()], 500);
        }
    }
    public function verify(Seller $seller)
    {
        $seller->is_verified = true;
        $seller->save();

        return response()->json([
            'status' => 'success',
            'message' => "Seller {$seller->name} is now verified",
            'data' => $seller
        ]);
    }
}