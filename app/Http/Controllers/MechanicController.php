<?php

namespace App\Http\Controllers;

use App\Models\Mechanic;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MechanicController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Mechanic::with(['user', 'images']);

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
        })->when($request->available, function($q){
            $q->where('is_available', true);
        });

        $mechanics = $query->latest()->paginate(10);

        return response()->json($mechanics);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        //I-uncomment ni para makita nato kon unsa gyuy sulod sa request
        // return response()->json($request->allFiles());


        $fields = $request->validate([
            'name' => 'required|string',
            'shop_name' => 'nullable|string',
            'address' => 'required|string',
            'contact_number' => 'required|string|min:11|max:255',
            'years_experience' => 'required|integer|min:0|max:20',
            'service_fee_starts_at' => 'nullable|numeric|min:500|max:10000',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'bio' => 'nullable|string',
            'specialization' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'emergency_contact' => 'nullable|string',
        ]);

        $userId = auth()->id();

        try {
            $mechanic = Mechanic::create([
                'user_id' => $userId,
                'name' => $fields['name'],
                'shop_name' => $fields['shop_name'] ?? null,
                'address' => $fields['address'],
                'contact_number' => $fields['contact_number'],
                'emergency_contact' => $fields['emergency_contact'] ?? null,
                'years_experience' => $fields['years_experience'],
                'service_fee_starts_at' => $fields['service_fee_starts_at'] ?? null,
                'bio' => $request->bio,
                'specialization' => $request->specialization,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'is_available' => true, // default available basta bag-ong kumpuni

            ]);

            if ($request->hasFile('image')) {
                $path = $request->file('image')->store('images/mechanics', 'public');
                $mechanic->images()->create([
                    'path' => $path,
                    'is_primary' => true,
                    'imageable_id' => $mechanic->id,
                    'imageable_type' => Mechanic::class,
                ]);
            }

            return response()->json([
                'message' => 'Mechanic Profile Created Successfully',
                'data' => $mechanic->load('images')
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Profile Already Exists',
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
            'service_fee_starts_at' => 'nullable|numeric|',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('images/mechanics', 'public');
            $fields['image'] = $path;
        }

        $mechanic->update($fields);

        return response()->json(['message' => 'Mechanic Profile Updated Successfully', 'data' => $mechanic->load('image'), 201]);
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

        $images = $mechanic->images;

        foreach ($images as $image) {
            if (Storage::disk('public')->exists($image->path)) {
                Storage::disk('public')->delete($image->path);
            }

            $image->delete();
        }

        $mechanic->delete();

        return response()->json(['message' => 'Mechanic Profile Successfully Deleted'], 200);
    }

}
