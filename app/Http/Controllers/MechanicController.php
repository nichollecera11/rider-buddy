<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMechanicRequest;
use App\Http\Requests\UpdateMechanicRequest;
use App\Models\Consultation;
use App\Models\Mechanic;
use Exception;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class MechanicController extends Controller
{
    // Public route: no login, so never call auth()->user()->something here
    public function index(Request $request)
    {
        try {
            $query = Mechanic::with(['user:id,name', 'media']);

            if ($request->lat && $request->lng) {
                $query->withDistance($request->lat, $request->lng)
                    ->having('distance', '<', 50)
                    ->orderBy('distance', 'asc');
            }

            $query->when($request->search, function ($q, $search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%")
                        ->orWhere('specialization', 'like', "%{$search}%")
                        ->orWhere('shop_name', 'like', "%{$search}%");
                });
            })
                ->when($request->min_exp, fn ($q, $exp) => $q->where('years_experience', '>=', $exp))
                ->when($request->service_fee, fn ($q, $fee) => $q->where('service_fee_starts_at', '<=', $fee))
                ->when($request->available, fn ($q) => $q->where('is_available', true))
                ->when($request->is_24_7, fn ($q) => $q->where('is_24_7', true))
                ->when($request->offers_towing, fn ($q) => $q->where('offers_towing', true))
                ->when($request->emergency_vulcanizing, function ($q) {
                    $q->where('specialization', 'like', '%Vulcanizing%')
                        ->where('is_24_7', true)
                        ->where('offers_towing', true);
                });

            $mechanics = $query->latest('id')->paginate(10)
                ->through(fn ($m) => $m->makeHidden('emergency_contact'));

            return response()->json([
                'message' => 'Mechanics retrieved successfully',
                'data' => $mechanics
            ], 200);
        } catch (Exception $e) {
            Log::error("Mechanic Index Error: " . $e->getMessage());
            return response()->json([
                'message' => 'Failed to retrieve mechanics',
                'error' => config('app.debug') ? $e->getMessage() : 'Server Error'
            ], 500);
        }
    }

    public function store(StoreMechanicRequest $request)
    {
        $this->authorize('create', Mechanic::class);

        $path = null;

        DB::beginTransaction();
        try {
            // Explicit keys come AFTER the spread so they always win
            $mechanic = Mechanic::create([
                ...$request->safe()->except('image'),
                'user_id' => auth()->id(),
                'name' => auth()->user()->name,
                'is_available' => $request->boolean('is_available', true),
                'is_24_7' => $request->boolean('is_24_7'),
                'offers_towing' => $request->boolean('offers_towing'),
            ]);

            if ($request->hasFile('image')) {
                $path = $request->file('image')->store('mechanics/profiles', 'public');
                $mechanic->media()->create([
                    'file_path' => $path,
                    'file_type' => 'image',
                    'collection' => 'profile',
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Mechanic profile created successfully',
                'data' => $mechanic->load('media')
            ], 201);

        } catch (UniqueConstraintViolationException $e) {
            // mechanics.user_id is unique, so a second profile is rejected by the database itself
            DB::rollBack();
            $this->deleteFiles($path ? [$path] : []);
            return response()->json([
                'message' => 'You already have a mechanic profile.'
            ], 422);
        } catch (Exception $e) {
            DB::rollBack();
            $this->deleteFiles($path ? [$path] : []);
            Log::error("Mechanic Store Error: " . $e->getMessage());
            return response()->json([
                'message' => 'Error creating mechanic profile',
                'error' => config('app.debug') ? $e->getMessage() : 'Server Error'
            ], 500);
        }
    }

    // Public route: route-model binding returns the 404 for us
    public function show(Mechanic $mechanic)
    {
        try {
            $mechanic->load(['user:id,name', 'media'])->makeHidden('emergency_contact');

            return response()->json([
                'message' => 'Mechanic retrieved successfully',
                'data' => $mechanic
            ], 200);
        } catch (Exception $e) {
            Log::error("Mechanic Show Error [ID: {$mechanic->id}]: " . $e->getMessage());
            return response()->json([
                'message' => 'Failed to retrieve mechanic',
                'error' => config('app.debug') ? $e->getMessage() : 'Server Error'
            ], 500);
        }
    }

    public function update(UpdateMechanicRequest $request, Mechanic $mechanic)
    {
        $this->authorize('update', $mechanic);

        $newPath = null;
        $oldPath = null;

        DB::beginTransaction();
        try {
            // safe() returns ONLY the validated fields, so is_verified / rating / user_id can never be set here
            $mechanic->update($request->safe()->except('image'));

            if ($request->hasFile('image')) {
                $newPath = $request->file('image')->store('mechanics/profiles', 'public');

                $profile = $mechanic->media()->where('collection', 'profile')->first();
                if ($profile) {
                    $oldPath = $profile->file_path;
                    $profile->update(['file_path' => $newPath]);
                } else {
                    $mechanic->media()->create([
                        'file_path' => $newPath,
                        'file_type' => 'image',
                        'collection' => 'profile',
                    ]);
                }
            }

            DB::commit();

            // The old file is deleted only AFTER the commit succeeds
            $this->deleteFiles($oldPath ? [$oldPath] : []);

            return response()->json([
                'message' => 'Mechanic profile updated successfully',
                'data' => $mechanic->load('media')
            ], 200);

        } catch (Exception $e) {
            DB::rollBack();
            $this->deleteFiles($newPath ? [$newPath] : []);
            Log::error("Mechanic Update Error: " . $e->getMessage());
            return response()->json([
                'message' => 'Failed to update mechanic profile',
                'error' => config('app.debug') ? $e->getMessage() : 'Server Error'
            ], 500);
        }
    }

    public function destroy(Mechanic $mechanic)
    {
        $this->authorize('delete', $mechanic);

        // Consultations (including cancelled ones, which are soft-deleted) point at this mechanic.
        // Deleting would erase the trust history, so we refuse.
        if (Consultation::withTrashed()->where('mechanic_id', $mechanic->id)->exists()) {
            return response()->json([
                'message' => 'This mechanic has consultation history and cannot be deleted'
            ], 409);
        }

        DB::beginTransaction();
        try {
            $paths = $mechanic->media()->pluck('file_path')->all();

            $mechanic->media()->delete();
            $mechanic->delete();

            DB::commit();
            $this->deleteFiles($paths);

            return response()->json([
                'message' => 'Mechanic profile deleted successfully'
            ], 200);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Mechanic Delete Error [ID: {$mechanic->id}]: " . $e->getMessage());
            return response()->json([
                'message' => 'Delete mechanic profile failed',
                'error' => config('app.debug') ? $e->getMessage() : 'Server Error'
            ], 500);
        }
    }

    // Admin only: the route sits inside the admin group, and the Policy is the second lock
    public function verify(Mechanic $mechanic)
    {
        $this->authorize('verify', $mechanic);

        try {
            $mechanic->is_verified = true;
            $mechanic->save();

            return response()->json([
                'message' => "Mechanic {$mechanic->name} is now verified",
                'data' => $mechanic
            ], 200);
        } catch (Exception $e) {
            Log::error("Mechanic Verify Error [ID: {$mechanic->id}]: " . $e->getMessage());
            return response()->json([
                'message' => 'Failed to verify mechanic',
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