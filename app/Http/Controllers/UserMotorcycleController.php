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

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(UserMotorcycle $userMotorcycle)
    {

        $this->authorize('delete', $userMotorcycle);

        try {
            $userMotorcycle->delete();
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
