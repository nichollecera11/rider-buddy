<?php

namespace App\Http\Controllers;

use App\Http\Requests\PartRequest;
use Exception;
use Illuminate\Http\Request;
use App\Models\Part;
use App\Models\Seller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\ImageFile;
use Illuminate\Support\Facades\Log;
use App\Http\Resources\PartResource;

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

        // return response()->json($parts);
        return PartResource::collection($parts);

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PartRequest $request)
    {

        abort_if(!in_array(auth()->user()->role, ['admin', 'seller']), 403, 'Unauthorized');

        //kwaon sa nato una ang seller para dili nata mag butang ug seller sa seeding
        $seller = Seller::where('user_id', auth()->id())->first();

        if (!$seller && auth()->user()->role === 'seller') {
            return response()->json(['message' => 'You need to create Seller Profile']);
        }
        //PartRequest ga handle ani
        $fields = $request->validated();

        $fields['seller_id'] = $seller ? $seller->id : 1;

        try {
            //Naa ni sa PartModel
            $part = Part::storeWithImages($fields, $request->file('images'));
           
            return response()->json(['message' => 'Parts Saved', 'data' => $part->load('images', 'brand', 'category', 'seller.user')], 201);
        } catch (Exception $e) {
            Log::error("Error Listing Failed: " . $e->getMessage());
            return response()->json([
                'message' => 'Error Listing Parts',
                'error' => config('app.debug')? $e->getMessage() : 'Server Error'
            ], 500);
        }

    }

    /**
     * Display the specified resource.
     */
    public function show(Part $part)
    {
        $part->load(['seller', 'category', 'brand', 'images']);
        return new PartResource($part);

    }

    /**
     * Update the specified resource in storage.
     */
    public function update(PartRequest $request, Part $part)
    {

        $user = auth()->user();
        $seller = Seller::where('user_id', $user->id)->first();

        if($user->role !== 'admin'){
            if (!$seller || $part->seller_id !== $seller->id){
                return response()->json(['message' => 'Unauthorized: You do not own this part'], 403);
            }
        }
        // abort_if(!in_array(auth()->user()->role, ['admin','seller']), 403, 'Unauthorized');

        // if (!$part) {
        //     return response()->json(['message' => 'Parts not found'], 404);
        // }

        // $seller = Seller::where('user_id', auth()->id())->first();

        // if (!$seller || $part->seller_id !== $seller->id) {
        //     return response()->json(['message' => 'Unauthorized'], 403);
        // }

        $fields = $request->validated();

        DB::beginTransaction();
        try {
            $part->updateWithImages(
                $request->validated(),
                $request->file('images'),
                $request->remove_images,
                $request->primary_image_id
            );

            return response()->json([
                'message' => 'Parts Updated Successfully',
                'data' => $part->load('images', 'brand', 'category')
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Updating Parts Failed: " . $e->getMessage());
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


        $part = Part::with('images')->find($id);

        if (!$part) {
            return response()->json(['message' => "Parts not found"], 404);
        }

        $seller = Seller::where('user_id', auth()->id())->first();

        if (!$seller || $part->seller_id !== $seller->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        DB::beginTransaction();

        try {

            foreach ($part->images as $image) {
                if (Storage::disk('public')->exists($image->path)) {
                    Storage::disk('public')->delete($image->path);
                }
            }

            $part->images()->delete();
            $part->delete();
            DB::commit();
            return response()->json(['message' => ' Part and associated images deleted successfully', 'data' => $part, 'id' => $id], 200);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Parts and Associated Media Files Delete Failed: " . $e->getMessage());
            return response()->json(['message' => ' Deleting Parts Failed', 'error' => $e->getMessage()], 500);
        }
    }
}
