<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSellerRequest;
use App\Http\Requests\UpdateSellerRequest;
use App\Models\Seller;
use Exception;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SellerController extends Controller
{
    // Public route: no login, so never call auth()->user()->something here
    public function index(Request $request)
    {
        try {
            $query = Seller::with(['user:id,name', 'media']);

            if ($request->lat && $request->lng) {
                $query->withDistance($request->lat, $request->lng)
                    ->orderBy('distance', 'asc');
            }

            $sellers = $query
                ->when($request->search, function ($q, $search) {
                    $q->where(function ($inner) use ($search) {
                        $inner->where('shop_name', 'like', "%{$search}%")
                            ->orWhere('address', 'like', "%{$search}%")
                            ->orWhere('description', 'like', "%{$search}%");
                    });
                })
                ->when($request->is_official, fn ($q) => $q->where('is_official_store', true))
                ->when($request->has_delivery, fn ($q) => $q->where('has_delivery', true))
                ->when($request->is_24_7, fn ($q) => $q->where('is_24_7', true))
                ->latest('id')
                ->paginate(10)
                ->through(fn ($s) => $s->makeHidden('business_permit_no'));

            return response()->json([
                'message' => 'Sellers retrieved successfully',
                'data' => $sellers
            ], 200);
        } catch (Exception $e) {
            Log::error("Seller Index Error: " . $e->getMessage());
            return response()->json([
                'message' => 'Failed to retrieve sellers',
                'error' => config('app.debug') ? $e->getMessage() : 'Server Error'
            ], 500);
        }
    }

    public function store(StoreSellerRequest $request)
    {
        $this->authorize('create', Seller::class);

        $path = null;

        DB::beginTransaction();
        try {
            // Explicit keys come AFTER the spread so they always win
            $seller = Seller::create([
                ...$request->safe()->except('image'),
                'user_id' => auth()->id(),
                'has_delivery' => $request->boolean('has_delivery'),
                'is_24_7' => $request->boolean('is_24_7'),
            ]);

            if ($request->hasFile('image')) {
                $path = $request->file('image')->store('sellers/profiles', 'public');
                $seller->media()->create([
                    'file_path' => $path,
                    'file_type' => 'image',
                    'collection' => 'logo',
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Seller profile created successfully',
                'data' => $seller->load('media')
            ], 201);

        } catch (UniqueConstraintViolationException $e) {
            DB::rollBack();
            $this->deleteFiles($path ? [$path] : []);
            return response()->json([
                'message' => 'A seller profile with these details already exists.'
            ], 422);
        } catch (Exception $e) {
            DB::rollBack();
            $this->deleteFiles($path ? [$path] : []);
            Log::error("Seller Store Error: " . $e->getMessage());
            return response()->json([
                'message' => 'Error creating seller profile',
                'error' => config('app.debug') ? $e->getMessage() : 'Server Error'
            ], 500);
        }
    }

    // Public route: route-model binding returns the 404 for us.
    // Listings are NOT loaded here (they would be unbounded); they get their own paginated endpoints.
    public function show(Seller $seller)
    {
        try {
            $seller->load(['user:id,name', 'media'])
                ->loadCount(['motorcycles', 'parts'])
                ->makeHidden('business_permit_no');

            return response()->json([
                'message' => 'Seller retrieved successfully',
                'data' => $seller
            ], 200);
        } catch (Exception $e) {
            Log::error("Seller Show Error [ID: {$seller->id}]: " . $e->getMessage());
            return response()->json([
                'message' => 'Failed to retrieve seller',
                'error' => config('app.debug') ? $e->getMessage() : 'Server Error'
            ], 500);
        }
    }

    public function update(UpdateSellerRequest $request, Seller $seller)
    {
        $this->authorize('update', $seller);

        $newPath = null;
        $oldPath = null;

        DB::beginTransaction();
        try {
            // safe() returns ONLY the validated fields, so is_verified / is_official_store / user_id can never be set here
            $seller->update($request->safe()->except('image'));

            if ($request->hasFile('image')) {
                $newPath = $request->file('image')->store('sellers/profiles', 'public');

                $logo = $seller->media()->where('collection', 'logo')->first();
                if ($logo) {
                    $oldPath = $logo->file_path;
                    $logo->update(['file_path' => $newPath]);
                } else {
                    $seller->media()->create([
                        'file_path' => $newPath,
                        'file_type' => 'image',
                        'collection' => 'logo',
                    ]);
                }
            }

            DB::commit();

            // The old file is deleted only AFTER the commit succeeds
            $this->deleteFiles($oldPath ? [$oldPath] : []);

            return response()->json([
                'message' => 'Seller profile updated successfully',
                'data' => $seller->load('media')
            ], 200);

        } catch (Exception $e) {
            DB::rollBack();
            $this->deleteFiles($newPath ? [$newPath] : []);
            Log::error("Seller Update Error: " . $e->getMessage());
            return response()->json([
                'message' => 'Failed to update seller profile',
                'error' => config('app.debug') ? $e->getMessage() : 'Server Error'
            ], 500);
        }
    }

    public function destroy(Seller $seller)
    {
        $this->authorize('delete', $seller);

        // Whatever the foreign keys do on delete, the result is predictable this way
        if ($seller->hasListings()) {
            return response()->json([
                'message' => 'This shop still has listings. Remove its motorcycles and parts first.'
            ], 409);
        }

        DB::beginTransaction();
        try {
            $paths = $seller->media()->pluck('file_path')->all();

            $seller->media()->delete();
            $seller->delete();

            DB::commit();
            $this->deleteFiles($paths);

            return response()->json([
                'message' => 'Seller profile deleted successfully'
            ], 200);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Seller Delete Error [ID: {$seller->id}]: " . $e->getMessage());
            return response()->json([
                'message' => 'Delete seller profile failed',
                'error' => config('app.debug') ? $e->getMessage() : 'Server Error'
            ], 500);
        }
    }

    // Admin only: the route sits inside the admin group, and the Policy is the second lock
    public function verify(Seller $seller)
    {
        $this->authorize('verify', $seller);

        try {
            $seller->is_verified = true;
            $seller->save();

            return response()->json([
                'message' => 'Seller verified successfully',
                'data' => $seller
            ], 200);
        } catch (Exception $e) {
            Log::error("Seller Verify Error [ID: {$seller->id}]: " . $e->getMessage());
            return response()->json([
                'message' => 'Failed to verify seller',
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
}