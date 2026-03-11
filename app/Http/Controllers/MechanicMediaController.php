<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\MechanicMedia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class MechanicMediaController extends Controller
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
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(MechanicMedia $mechanicMedia)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(MechanicMedia $mechanicMedia)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, MechanicMedia $mechanicMedia)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MechanicMedia $mechanicMedia)
    {
        if ($mechanicMedia->mechanic->user_id !== auth()->id()) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }
        DB::beginTransaction();
        try {
            if (Storage::disk('public')->exists($mechanicMedia->file_path)) {
                Storage::disk('public')->delete($mechanicMedia->file_path);
            }

            $mechanicMedia->delete();
            DB::commit();
            return response()->json([
                'message' => 'Mechanic Media Successfully Deleted'
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Mechanic Media Delete Failed: [{$mechanicMedia->id}]" . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Deleting Mechanic Data and Media Failed',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Server Error'
            ], 500);
        }
    }
}
