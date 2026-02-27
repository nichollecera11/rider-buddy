<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use App\Models\Review;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\Seller;
use App\Models\Mechanic;

class ReviewController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // 1. I-load ang relasyon lakip ang images
        $query = Review::with(['user:id,name', 'reviewable', 'images']);

        // 2. Filter para sa Type (App\Models\Seller o App\Models\Mechanic)
        $query->when($request->type, function ($q, $type) {
            $q->where('reviewable_type', $type);
        });

        // 3. Filter para sa ID (Kani ang naay sayop sa una)
        $query->when($request->reviewable_id, function ($q, $id) {
            // Direkta lang i-filter ang ID, ayaw na i-assign ang Class name diri
            $q->where('reviewable_id', $id);
        });

        $reviews = $query->paginate(12);
        return response()->json($reviews);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $fields = $request->validate([
            'reviewable_id' => 'required|integer',
            'reviewable_type' => 'required|string|in:App\Models\Mechanic,App\Models\Seller',
            'rating' => 'required|integer|min:1|max:5',
            'headline' => 'nullable|string|max:255',
            'comment' => 'nullable|string',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        DB::beginTransaction();
        try {
            $fields['user_id'] = auth()->id();

            // I-exclude ang images gikan sa main review creation
            $reviewData = collect($fields)->except(['images'])->toArray();
            $review = Review::create($reviewData);

            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $path = $image->store('images/reviews', 'public');
                    $review->images()->create([
                        'path' => $path,
                        'is_primary' => false
                    ]);
                }
            }
            // i-compute nato ang bag-ong average rating sa Mechanic o Seller ug i-save ni nato diretso sa ilang table
            $reviewable = $fields ['reviewable_type']::find($fields['reviewable_id']);
            if ($reviewable){
                // I-calculate ang bag-ong average rating
                $averageRating = Review::where('reviewable_id', $fields['reviewable_id'])
                ->where('reviewable_type', $fields['reviewable_id'])
                ->avg('rating');
                // i save ang average rating sa table sa Mechanic/Seller
                // make sure na naay ratings sa table
                $reviewable->update([
                    'rating'=> round($averageRating, 1)
                ]);
            }

            DB::commit();
            // Naay typo sa imong original response (status code was inside the array)
            return response()->json([
                'message' => 'Review Posted Successfully',
                'data' => $review->load('user:id,name', 'images')
            ], 201);

        } catch (Exception $e) {
            DB::rollBack(); // Importante!
            return response()->json([
                'message' => 'Failed to post review',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show(string $id)
    {
        // Gidugangan og find($id)
        $review = Review::with(['user:id,name', 'reviewable', 'images'])->find($id);

        if (!$review) {
            return response()->json(['message' => 'Review not Found'], 404); // Mas maayo 404
        }
        return response()->json($review);
    }

    public function update(Request $request, string $id)
    {
        $review = Review::find($id);

        if (!$review) {
            return response()->json(['message' => 'Review Not Found'], 404);
        }

        if ($review->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $fields = $request->validate([
            'rating' => 'sometimes|integer|min:1|max:5',
            'headline' => 'sometimes|nullable|string|max:255',
            'comment' => 'sometimes|nullable|string',
            'images' => 'sometimes|nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,webp|max:2048',
            'remove_images' => 'sometimes|array',
            'remove_images.*' => 'integer|exists:images,id',
        ]);

        DB::beginTransaction();
        try {
            // FIX: I-filter ang fields para dili mangita og 'remove_images' sa DB
            $updateData = collect($fields)->except(['images', 'remove_images'])->toArray();
            $review->update($updateData);

            if ($request->has('remove_images')) {
                $imagesToDelete = $review->images()->whereIn('id', $request->remove_images)->get();
                foreach ($imagesToDelete as $img) {
                    if (Storage::disk('public')->exists($img->path)) {
                        Storage::disk('public')->delete($img->path);
                    }
                    $img->delete();
                }
            }

            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $imageFile) {
                    $path = $imageFile->store('images/reviews', 'public');
                    $review->images()->create([
                        'path' => $path,
                        'is_primary' => false,
                    ]);
                }
            }

            DB::commit();
            return response()->json(['message' => 'Review Updated Successfully', 'data' => $review->load('images')]);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Review Update Failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $review = Review::with('images')->find($id);
        if (!$review) {
            return response()->json(['message' => 'Review Not Found'], 404);
        }
        if ($review->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        DB::beginTransaction();

        try {
            $images = $review->images;

            foreach ($review->images as $image) {
                if (Storage::disk('public')->exists($image->path)) {
                    Storage::disk('public')->delete($image->path);
                }
            }
            $review->images()->delete();
            $review->delete();
            DB::commit();
            return response()->json(['message' => 'Review and Review Images Deleted'], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Delete Review Failed', 'error' => $e->getMessage()]);
        }
    }
}
