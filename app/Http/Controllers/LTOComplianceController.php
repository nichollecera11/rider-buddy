<?php

namespace App\Http\Controllers;

use App\Models\LTOCompliance;
use App\Models\UserMotorcycle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class LTOComplianceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, $motorcycle_id)
    {
        $user_motorcycle = UserMotorcycle::where('id', $motorcycle_id)->where('user_id', auth()->id())->first();

        if (!$user_motorcycle) {
            return response()->json([
                'message' => 'Motorcycle not found'
            ]);
        }
        $fields = $request->validate([
            // Kinahanglan unique ni sila sa l_t_o_compliances table para anti-fraud
            'plate_number' => 'required|string|unique:l_t_o_compliances,plate_number',
            'engine_number' => 'required|string|unique:l_t_o_compliances,engine_number',
            'chassis_number' => 'required|string|unique:l_t_o_compliances,chassis_number',
            'registration_expiry' => 'required|date|after:today',

            // Ang OR/CR photo/file
            'file' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        DB::beginTransaction();

        try {
            if($request->hasFile('file')) {
                $file = $request->file('file');
                $filename = time() . '_' . $file->getClientOriginalName();

                $path = $file->storeAs('lto_docs', $filename, 'private');

                
            }
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(LTOCompliance $lTOCompliance)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(LTOCompliance $lTOCompliance)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, LTOCompliance $lTOCompliance)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(LTOCompliance $lTOCompliance)
    {
        //
    }
}
