<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserMotorcycleRequest;
use App\Http\Requests\UpdateUserMotorcycleRequest;
use Exception;
use App\Models\UserMotorcycle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UserMotorcycleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $motorcycles = UserMotorcycle::where('user_id', auth()->id())
                ->with('brand')->latest()->get();
            return response()->json([
                'message' => 'Garage retrived successfully',
                'count' => $motorcycles->count(),
                'data' => $motorcycles
            ], 200);
        } catch (Exception $e) {
            Log::error("UserMotorcycle Index Error: " . $e->getMessage());
            return response()->json([
                'message' => 'Failed to retrieve garage'
            ], 500);

        }
    }
    public function archived()
    {
        $motorcycles = UserMotorcycle::withTrashed()
            ->where('user_id', auth()->id())
            ->whereNotNull('deleted_at')
            ->with('brand')
            ->latest('deleted_at')
            ->get();

        return response()->json([
            'message' => 'Archived Motorcycles Retrieved Successfully',
            'count' => $motorcycles->count(),
            'data' => $motorcycles,
        ], 200);
    }
    public function showArchived($id)
    {
        $motorcycle = UserMotorcycle::withTrashed()
            ->where('id', $id)
            ->where('user_id', auth()->id())
            ->whereNotNull('deleted_at')
            ->with('brand')
            ->first();

        if (!$motorcycle) {
            return response()->json([
                'message' => 'Archived motorcycle not found.',
            ], 404);
        }

        return response()->json([
            'message' => 'Archived Motorcycle Retrieved Successfully',
            'data' => $motorcycle,
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserMotorcycleRequest $request)
    {

        $this->authorize('create', UserMotorcycle::class);

        DB::beginTransaction();
        try {
            $fields = $request->validated();
            $fields['user_id'] = auth()->id();
            if ($request->is_main) {
                UserMotorcycle::where('user_id', auth()->id())->update(['is_main' => false]);
            }
            $motorcycle = UserMotorcycle::create($fields);
            DB::commit();

            return response()->json([
                'message' => 'Motorcycle added to garage successfully',
                'data' => $motorcycle->load('brand')
            ], 201);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error("UserMotorcycle Store Error: " . $e->getMessage());
            return response()->json([
                'message' => 'Failed to add Motorcycle',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Server Error'
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(UserMotorcycle $userMotorcycle)
    {
        try {
            $userMotorcycle->load('brand');

            if ($userMotorcycle->user_id !== auth()->id()) {
                return response()->json([
                    'message' => 'Unauthorized access to this motorcycle'
                ], 403);
            }
            return response()->json([
                'data' => $userMotorcycle
            ], 200);

        } catch (Exception $e) {
            Log::error("UserMotorcycle Show Error: " . $e->getMessage());
            return response()->json([
                'message' => 'Failed to show Motorcycle',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Server Error'
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserMotorcycleRequest $request, UserMotorcycle $userMotorcycle)
    {
        $this->authorize('update', $userMotorcycle);

        DB::beginTransaction();
        try {
            $fields = $request->validated();

            if ($request->is_main) {
                UserMotorcycle::where('user_id', auth()->id())
                    ->where('id', '!=', $userMotorcycle->id)->update(['is_main' => false]);
            }
            if ($request->hasFile('verification_photo')) {
                $path = $request->file('verification_photo')->store('images/usermotorcycles/verification_photo', 'public');

                $userMotorcycle->images()->create([
                    'path' => $path,
                    'is_primary' => true,
                ]);
            }

            $userMotorcycle->update($fields);
            DB::commit();
            return response()->json([
                'message' => 'Motorcycle Updated Successfully',
                'data' => $userMotorcycle->load('brand')
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("UserMotorcycle Update Error: " . $e->getMessage());
            return response()->json([
                'message' => 'Update Failed',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Server Error'
            ], 500);
        }
    }

    public function destroy(UserMotorcycle $userMotorcycle)
    {
        $this->authorize('delete', $userMotorcycle);

        $userId = auth()->id();

        try {
            $result = DB::transaction(function () use ($userMotorcycle, $userId) {
                // Coordinate with consultation creation for this rider.
                DB::table('users')
                    ->where('id', $userId)
                    ->lockForUpdate()
                    ->first();

                // Recheck motorcycle ownership and availability after locking.
                $motorcycle = UserMotorcycle::query()
                    ->where('id', $userMotorcycle->id)
                    ->where('user_id', $userId)
                    ->lockForUpdate()
                    ->first();

                if (!$motorcycle) {
                    return 'unavailable';
                }

                // Prevent deletion while an unresolved consultation exists.
                $hasUnresolvedConsultation = $motorcycle->consultations()
                    ->whereIn('status', ['pending', 'accepted', 'ongoing'])
                    ->exists();

                if ($hasUnresolvedConsultation) {
                    return 'unresolved';
                }

                $motorcycle->delete();

                return 'deleted';
            });

            if ($result === 'unavailable') {
                return response()->json([
                    'message' => 'Motorcycle not found or no longer available.'
                ], 404);
            }

            if ($result === 'unresolved') {
                return response()->json([
                    'message' => 'Motorcycle cannot be deleted while an unresolved consultation exists.'
                ], 409);
            }

            return response()->json([
                'message' => 'Motorcycle Deleted Successfully',
                'data' => $userMotorcycle->id
            ], 200);

        } catch (Exception $e) {
            Log::error("Motorcycle Delete error: " . $e->getMessage());

            return response()->json([
                'message' => 'Motorcycle Delete Failed',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Server Error'
            ], 500);
        }
    }


}
