<?php

namespace App\Http\Controllers;

use App\Models\Mechanic;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class MechanicController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Mechanic::with(['user', 'images']);

        if ($request->lat && $request->lng) {
            $query->withDistance($request->lat, $request->lng)->orderBy('distance', 'asc');
        } else {
            $query->latest();
        }

        $query->when($request->search, function ($q, $search) {
            $q->where(function ($inner) use ($search) {
                $inner->where('name', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%")
                    ->orWhere('specialization', 'like', "%{$search}%")
                    ->orWhere('shop_name', 'like', "%{$search}%");
            });
        })->when($request->min_exp, function ($q, $exp) {
            $q->where('years_experience', '>=', $exp);
        })->when($request->service_fee, function ($q, $fee) {
            $q->where('service_fee_starts_at', '<=', $fee);
        })->when($request->available, function ($q) {
            $q->where('is_available', true);
        });

        //24/7
        $query->when($request->is_24_7, function ($q) {
            $q->where('is_24_7', true);
        });

        //Towing
        $query->when($request->offers_towing, function ($q) {
            $q->where('offers_towing', true);
        });

        //Emergency
        $query->when($request->emergency_vulcanizing, function ($q) {
            $q->where('specialization', 'Like', '%Vulcanizing%')
                ->where('is_24_7', true)
                // pwede nato ni tangtangon kay strict kaayo si emergency vulcanizing
                ->where('offers_towing', true);
        });


        // $rescuers = Mechanic::where('is_available', true)
        // ->where(function($query){
        //     $query->where('specialization', 'Like', '%Vulcanizing%')
        //     ->orWhere('offers_towing', true);
        //     })
        //     ->where('is_24_7', true)->get();

        $mechanics = $query->latest()->paginate(10);

        return response()->json($mechanics);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $fields = $request->validate([
            'name' => 'required|string',
            'shop_name' => 'nullable|string',
            'address' => 'required|string',
            'bio' => 'nullable|string',
            'specialization' => 'nullable|string',
            'contact_number' => 'required|string|min:11|max:255',
            'emergency_contact' => 'nullable|string',
            'years_experience' => 'required|integer|min:0|max:50', // Gi-adjust nako gamay basig naay master mechanic           
            'is_available' => 'boolean',
            'diagnostic_fee_base' => 'required|numeric|min:0',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'service_fee_starts_at' => 'nullable|numeric|min:0',
            'is_24_7' => 'boolean',
            'offers_towing' => 'boolean',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $userId = auth()->id();

        try {
            $mechanic = Mechanic::create([
                'user_id' => $userId,
                'name' => $fields['name'],
                'shop_name' => $fields['shop_name'] ?? null,
                'address' => $fields['address'],
                'bio' => $fields['bio'] ?? null,
                'specialization' => $fields['specialization'] ?? null,
                'contact_number' => $fields['contact_number'],
                'emergency_contact' => $fields['emergency_contact'] ?? null,
                'years_experience' => $fields['years_experience'],
                'diagnostic_fee_base' => $fields['diagnostic_fee_base'],
                'latitude' => $fields['latitude'] ?? null,
                'longitude' => $fields['longitude'] ?? null,
                'service_fee_starts_at' => $fields['service_fee_starts_at'] ?? null,
                // --- BOOLEAN HANDLING ---
                // Naggamit tag $request->boolean() para sure nga true/false ang ma-save, dili null
                'is_24_7' => $request->boolean('is_24_7'),
                'offers_towing' => $request->boolean('offers_towing'),
                'is_available' => $request->has('is_available') ? $request->boolean('is_available') : true,
            ]);

            // --- POLYMORPHIC IMAGE LOGIC ---
            // I-awat nato sa imong Seller/Mechanic image format
            if ($request->hasFile('image')) {
                $path = $request->file('image')->store('images/mechanics', 'public');
                $mechanic->images()->create([
                    'path' => $path,
                    'is_primary' => true,
                ]);
            }

            return response()->json([
                'message' => 'Mechanic profile created successfully',
                'data' => $mechanic->load('images')
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Error creating mechanic profile',
                'error' => $e->getMessage()
            ], 400);
        }
    }
    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $mechanic = Mechanic::with('user')->find($id); {
            if (!$mechanic) {
                return response()->json(['message' => 'Mechanic not found'], 404);
            }
            return response()->json($mechanic);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $mechanic = Mechanic::find($id);

        /* DEBUG
        return response()->json([
        'mechanic_owner' => $mechanic->user_id,
        'current_user' => auth()->id()
        ]); */

        if (!$mechanic) {
            return response()->json(['message' => 'Mechanic not found'], 404);
        }

        //Security Check for user update only
        if ($mechanic->user_id !== auth()->id()) {
            return response()->json([
                'message' => 'Unauthorized user profile'
            ], 403);
        }

        $fields = request()->validate([
            'name' => 'sometimes|required|string',
            'shop_name' => 'nullable|string',
            'address' => 'sometimes|required|string|',
            'contact_number' => 'sometimes|required|string',
            'emergency_contact' => 'nullable|string',
            'years_experience' => 'sometimes|required|integer',
            'bio' => 'nullable|string',
            'specialization' => 'nullable|string',
            'diagnostic_fee_base' => 'sometimes|required|numeric',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'service_fee_starts_at' => 'nullable|numeric|',
            'is_24_7' => 'sometimes|boolean',
            'offers_towing' => 'sometimes|boolean',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);
        DB::beginTransaction();
        try{
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('images/mechanics', 'public');
            $fields['image'] = $path;
        }

        $mechanic->update($fields);

        DB::commit();

        return response()->json(['message' => 'Mechanic Profile Updated Successfully', 'data' => $mechanic->load('image'), 201]);
        } catch (Exception $e){
            DB::rollBack();
            Log::error("Mechanic Update Error: " . $e->getMessage());

            return response()->json([
                'message' => 'Failed to Update Mechanic Profile',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Server Error'
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $mechanic = Mechanic::find($id);

        if (!$mechanic) {
            return response()->json(['message' => 'Mechanic not found'], 404);
        }
        if ($mechanic->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        DB::beginTransaction();

        try {

            $images = $mechanic->images;

            foreach ($images as $image) {
                if (Storage::disk('public')->exists($image->path)) {
                    Storage::disk('public')->delete($image->path);
                }
            }
            $mechanic->images()->delete();
            $mechanic->delete();
            DB::commit();
            return response()->json(['message' => 'Mechanic Profile Successfully Deleted'], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Delete Mechanic Profile Failed', 'error' => $e->getMessage()], 500);
        }
    }
    public function verify(Mechanic $mechanic)
    {
        $mechanic->is_verified = true;
        $mechanic->save();

        return response()->json([
            'status' => 'success',
            'message' => "Mechanic {$mechanic->name} is now verified",
            'data' => $mechanic
        ]);
    }
}
