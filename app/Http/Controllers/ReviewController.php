<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReviewRequest;
use App\Http\Requests\UpdateReviewRequest;
use App\Models\Consultation;
use App\Models\Mechanic;
use App\Models\Review;
use Exception;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ReviewController extends Controller
{
    // Public: no login, so never call auth()->user()->something here
    public function index(Request $request)
    {
        try {
            $reviews = Review::visible()
                ->with(['user:id,name', 'images'])
                ->when($request->type, fn ($q, $type) => $q->where('reviewable_type', $type))
                ->when($request->reviewable_id, fn ($q, $id) => $q->where('reviewable_id', $id))
                ->latest('id')
                ->paginate(12);

            return response()->json([
                'message' => 'Reviews retrieved successfully',
                'data' => $reviews
            ], 200);
        } catch (Exception $e) {
            Log::error("Review Index Error: " . $e->getMessage());
            return response()->json([
                'message' => 'Failed to retrieve reviews',
                'error' => config('app.debug') ? $e->getMessage() : 'Server Error'
            ], 500);
        }
    }

    public function store(StoreReviewRequest $request)
    {
        $fields = $request->validated();

        // Lookups and permission checks happen BEFORE the transaction starts
        $consultation = Consultation::findOrFail($fields['consultation_id']);

        $this->authorize('create', [Review::class, $consultation]);

        if (!$consultation->isReviewable()) {
            return response()->json([
                'message' => 'You can only review a completed consultation'
            ], 422);
        }

        if ($consultation->review()->exists()) {
            return response()->json([
                'message' => 'This consultation has already been reviewed'
            ], 422);
        }

        $storedPaths = [];

        DB::beginTransaction();
        try {
            $review = Review::create([
                'user_id' => auth()->id(),
                'consultation_id' => $consultation->id,
                'reviewable_id' => $consultation->mechanic_id,
                'reviewable_type' => Mechanic::class,
                'rating' => $fields['rating'],
                'headline' => $fields['headline'] ?? null,
                'comment' => $fields['comment'] ?? null,
            ]);

            foreach ($request->file('images', []) as $image) {
                $path = $image->store('images/reviews', 'public');
                $storedPaths[] = $path;
                $review->images()->create([
                    'path' => $path,
                    'is_primary' => false,
                ]);
            }

            $this->recomputeRating($review->reviewable_id, $review->reviewable_type);

            DB::commit();

            return response()->json([
                'message' => 'Review Posted Successfully',
                'data' => $review->load('user:id,name', 'images')
            ], 201);

        } catch (UniqueConstraintViolationException $e) {
            // Two requests raced past the exists() check; the database index caught it
            DB::rollBack();
            $this->deleteFiles($storedPaths);
            return response()->json([
                'message' => 'This consultation has already been reviewed'
            ], 422);
        } catch (Exception $e) {
            DB::rollBack();
            $this->deleteFiles($storedPaths);
            Log::error("Review Store Error: " . $e->getMessage());
            return response()->json([
                'message' => 'Failed to post review',
                'error' => config('app.debug') ? $e->getMessage() : 'Server Error'
            ], 500);
        }
    }

    // Public route, but also used by the admin route
    public function show(Review $review)
    {
        if (!$review->is_visible && !auth()->user()?->isAdmin()) {
            return response()->json(['message' => 'Review not found'], 404);
        }

        try {
            $review->load(['user:id,name', 'reviewable', 'images']);

            return response()->json([
                'message' => 'Review retrieved successfully',
                'data' => $review
            ], 200);
        } catch (Exception $e) {
            Log::error("Review Show Error [ID: {$review->id}]: " . $e->getMessage());
            return response()->json([
                'message' => 'Failed to retrieve review',
                'error' => config('app.debug') ? $e->getMessage() : 'Server Error'
            ], 500);
        }
    }

    public function update(UpdateReviewRequest $request, Review $review)
    {
        $this->authorize('update', $review);

        $newPaths = [];
        $oldPaths = [];

        DB::beginTransaction();
        try {
            $review->update($request->safe()->except(['images', 'remove_images']));

            $removeIds = $request->input('remove_images', []);
            if (!empty($removeIds)) {
                // Scoped to THIS review, so nobody can delete another review's photos
                $toDelete = $review->images()->whereIn('id', $removeIds)->get();
                $oldPaths = $toDelete->pluck('path')->all();
                $review->images()->whereIn('id', $removeIds)->delete();
            }

            foreach ($request->file('images', []) as $image) {
                $path = $image->store('images/reviews', 'public');
                $newPaths[] = $path;
                $review->images()->create([
                    'path' => $path,
                    'is_primary' => false,
                ]);
            }

            $this->recomputeRating($review->reviewable_id, $review->reviewable_type);

            DB::commit();

            // Files are deleted only after the database commit succeeds
            $this->deleteFiles($oldPaths);

            return response()->json([
                'message' => 'Review Updated Successfully',
                'data' => $review->load('user:id,name', 'images')
            ], 200);

        } catch (Exception $e) {
            DB::rollBack();
            $this->deleteFiles($newPaths);
            Log::error("Review Update Error: " . $e->getMessage());
            return response()->json([
                'message' => 'Review Update Failed',
                'error' => config('app.debug') ? $e->getMessage() : 'Server Error'
            ], 500);
        }
    }

    public function destroy(Review $review)
    {
        $this->authorize('delete', $review);

        DB::beginTransaction();
        try {
            $paths = $review->images()->pluck('path')->all();
            $reviewableId = $review->reviewable_id;
            $reviewableType = $review->reviewable_type;

            $review->images()->delete();
            $review->delete();

            $this->recomputeRating($reviewableId, $reviewableType);

            DB::commit();
            $this->deleteFiles($paths);

            return response()->json([
                'message' => 'Review and review images deleted'
            ], 200);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Review Delete Error [ID: {$review->id}]: " . $e->getMessage());
            return response()->json([
                'message' => 'Delete review failed',
                'error' => config('app.debug') ? $e->getMessage() : 'Server Error'
            ], 500);
        }
    }

    private function deleteFiles(array $paths): void
    {
        if (!empty($paths)) {
            Storage::disk('public')->delete($paths);
        }
    }

    private function recomputeRating($id, $type): void
    {
        if (!class_exists($type)) {
            return;
        }

        try {
            $average = Review::visible()
                ->where('reviewable_id', $id)
                ->where('reviewable_type', $type)
                ->avg('rating');

            $type::where('id', $id)->update([
                'rating' => $average ? round($average, 1) : 0
            ]);
        } catch (Exception $e) {
            Log::error("Rating computation failed for {$type} ID {$id}: " . $e->getMessage());
        }
    }
}