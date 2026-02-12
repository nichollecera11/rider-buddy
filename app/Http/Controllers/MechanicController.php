<?php

namespace App\Http\Controllers;

use App\Models\Mechanic;
use Illuminate\Http\Request;

class MechanicController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $mechanics = Mechanic::with('user')->get();
        return response()->json($mechanics);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $fields = $request->validate([
            'user_id' => 'required|exists:users,id|unique:mechanics,user_id',
            'name' => 'required|string',
            'shop_name' => 'nullable|string',
            'address' => 'required|string',
            'contact_number' => 'required|string|min:11|max:255',
            'years_experience' => 'required|integer|min:0|max:20',
            'service_fee_starts_at' => 'nullable|numeric|min:500|max:10000',
            'image' => 'nullable|string',
        ]);

        $mechanic = Mechanic::create($fields);

        return response()->json([
            'message' => 'Mechanic Profile Created Successfully',
            'data' => $mechanic
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $mechanic = Mechanic::with('user')->findOrFail($id); {
            if (!$mechanic) {
                return response()->json(['message' => 'Mechanic not found']);
            }
            return response()->json($mechanic);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
