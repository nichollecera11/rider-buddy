<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Motorcycle;

class MotorcycleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $motorcycles = Motorcycle::with(['seller', 'brand'])->get();
        return response()->json($motorcycles);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $motorcycle = Motorcycle::with(['seller', 'brand'])->findOrFail($id); {
            if (!$motorcycle) {
                return response()->json(['message' => 'Motorcycle not found']);
            }
        }
        return response()->json($motorcycle);

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
