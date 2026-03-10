<?php

namespace App\Http\Controllers;

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
    public function store(Request $request)
    {
        $fields = $request->validate([
            'brand_id' => 'required|exists:brands,id',
            'model' => 'required|string|max:255',
            'year_model' => 'nullable|string',
            'plate_number' => 'required|string|unique:user_motorcycles,plate_number',

            // Legal & Technical (Nullable para dili kapoyan si Nichol)
            'engine_number' => 'nullable|string|unique:user_motorcycles,engine_number',
            'chassis_number' => 'nullable|string|unique:user_motorcycles,chassis_number',
            'engine_capacity' => 'nullable|integer',

            // 🚀 THE SYNC: Gamit tag underscore para match sa standard
            'transmission' => 'nullable|in:manual,automatic,semi_automatic,none_electric',
            'fuel_type' => 'nullable|in:gasoline,electric',
            'color' => 'nullable|string',

            // 🚀 THE MISSING BUTLER FIELDS:
            'last_registration_date' => 'nullable|date',
            'insurance_expiry' => 'nullable|date',

            // Metrics & Status
            'current_odometer' => 'nullable|integer',
            'is_main' => 'boolean',
            'is_active' => 'boolean'
        ]);

        DB::beginTransaction();
        try {
            $fields['user_id'] = auth()->id();
            if ($request->is_main) {
                UserMotorcycle::where('user_id', auth()->id())->update(['is_main' => false]);

                $motorcycle = UserMotorcycle::create($fields);
                DB::commit();

                return response()->json([
                    'message' => 'Motorcycle added to garage successfully',
                    'data' => $motorcycle->load('brand')
                ], 201);
            }
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("UserMotorcycle Store Error: " . $e->getMessage());
            return response()->json([
                'message' => 'Failed to add Motorcycle',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Server Error'
            ]);
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
    public function update(Request $request, UserMotorcycle $userMotorcycle)
    {
        if ($userMotorcycle->user_id !== auth()->id()) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }
        $fields = $request->validate([
            'brand_id' => 'required|exists:brands,id',
            'model' => 'required|string|max:255',
            'year_model' => 'nullable|digits:4',
            'plate_number' => 'required|string|unique:user_motorcycles,plate_number,' . $userMotorcycle->id,
            'engine_number' => 'nullable|string|unique:user_motorcycles,engine_number,' . $userMotorcycle->id,
            'chassis_number' => 'nullable|string|unique:user_motorcycles,chassis_number,' . $userMotorcycle->id,
            'transmission' => 'nullable|in:manual,automatic,semi_automatic,none_electric',
            'fuel_type' => 'nullable|in:gasoline,electric',
            'color' => 'nullable|string',
            'last_registration_date' => 'nullable|date',
            'insurance_expiry' => 'nullable|date',
            'current_odometer' => 'nullable|integer',
            'is_main' => 'boolean',
            'is_active' => 'boolean'
        ]);
        if ($request->hasAny(['current_odometer', 'last_registration_date'])) {
            $request->validate([
                'verification_photo' => 'required|image|max:2048'
            ]);
        }

        DB::beginTransaction();
        try {
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
        try {
            if ($userMotorcycle->user_id !== auth()->id()) {
                return response()->json([
                    'message' => 'Unauthorized'
                ], 403);
            }
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
